"""Разбор письма из формата RFC822 в структуру для сохранения в базу."""

from __future__ import annotations

import hashlib
import json
from dataclasses import dataclass, field
from datetime import datetime
from email.message import Message as EmailMessage

from app.core.logging_setup import get_logger
from app.services.attachments import categorize
from app.services.email_utils import (
    decode_mime_words,
    first_address,
    parse_addresses,
    parse_date_header,
)
from app.services.subject_utils import normalize_subject
from app.services.text_cleaner import clean_text, html_to_text
from app.services.threading_service import normalize_message_id

logger = get_logger(__name__)

SERVICE_HEADERS = (
    "auto-submitted", "precedence", "list-unsubscribe", "list-id",
    "x-autoreply", "x-autorespond", "x-campaign", "feedback-id",
    "x-mailer", "return-path", "x-spam-flag",
)


@dataclass
class ParsedAttachment:
    filename: str
    mime_type: str | None
    size: int
    is_inline: bool
    category: str
    payload: bytes | None = None


@dataclass
class ParsedMessage:
    message_id: str | None
    in_reply_to: str | None
    references: str | None
    subject: str
    normalized_subject: str
    sender_name: str
    sender_email: str
    recipients: list[dict]
    cc: list[dict]
    sent_at: datetime | None
    body_text: str
    body_html: str
    clean_text: str
    attachments: list[ParsedAttachment] = field(default_factory=list)
    headers: dict[str, str] = field(default_factory=dict)
    content_hash: str = ""
    parse_error: str | None = None

    @property
    def recipient_emails(self) -> list[str]:
        return [r["email"] for r in self.recipients + self.cc if r.get("email")]


def _decode_payload(part: EmailMessage) -> str:
    """Достаёт текст части письма с учётом кодировки и повреждений."""
    try:
        payload = part.get_payload(decode=True)
    except Exception:
        return ""
    if payload is None:
        return ""
    charset = part.get_content_charset() or "utf-8"
    for candidate in (charset, "utf-8", "windows-1251", "koi8-r", "cp866", "latin-1"):
        try:
            return payload.decode(candidate)
        except (LookupError, UnicodeDecodeError):
            continue
    return payload.decode("utf-8", errors="replace")


def parse_message(
    msg: EmailMessage,
    load_attachment_payload: bool = False,
    max_attachment_bytes: int = 25 * 1024 * 1024,
) -> ParsedMessage:
    """Разбирает письмо. Никогда не выбрасывает исключение — ошибки в parse_error."""
    errors: list[str] = []

    try:
        subject = decode_mime_words(msg.get("Subject"))
    except Exception:
        subject, errors = "", errors + ["subject"]

    sender = first_address(msg.get("From"))
    recipients = parse_addresses(msg.get("To"))
    cc = parse_addresses(msg.get("Cc"))
    if not recipients:
        recipients = parse_addresses(msg.get("Delivered-To")) or parse_addresses(
            msg.get("X-Original-To")
        )

    sent_at = parse_date_header(msg.get("Date"))

    body_text_parts: list[str] = []
    body_html_parts: list[str] = []
    attachments: list[ParsedAttachment] = []

    try:
        for part in msg.walk():
            if part.is_multipart():
                continue
            content_type = (part.get_content_type() or "").lower()
            disposition = (part.get("Content-Disposition") or "").lower()
            filename = decode_mime_words(part.get_filename() or "")

            is_attachment = "attachment" in disposition or bool(filename)
            is_inline = "inline" in disposition and bool(part.get("Content-ID"))

            if is_attachment or (is_inline and content_type.startswith("image/")):
                try:
                    payload = part.get_payload(decode=True) or b""
                except Exception:
                    payload = b""
                    errors.append("attachment-decode")
                size = len(payload)
                attachments.append(
                    ParsedAttachment(
                        filename=filename or f"без_имени.{content_type.split('/')[-1][:8]}",
                        mime_type=content_type or None,
                        size=size,
                        is_inline=is_inline,
                        category=categorize(filename, content_type, is_inline),
                        payload=payload
                        if (load_attachment_payload and 0 < size <= max_attachment_bytes)
                        else None,
                    )
                )
                continue

            if content_type == "text/plain":
                body_text_parts.append(_decode_payload(part))
            elif content_type == "text/html":
                body_html_parts.append(_decode_payload(part))
    except Exception as exc:  # повреждённая структура MIME
        errors.append(f"mime:{type(exc).__name__}")
        logger.warning("Повреждённая структура письма: %s", type(exc).__name__)

    body_text = "\n".join(p for p in body_text_parts if p).strip()
    body_html = "\n".join(p for p in body_html_parts if p).strip()
    if not body_text and body_html:
        body_text = html_to_text(body_html).strip()

    cleaned = clean_text(body_text, body_html)

    headers = {}
    for key in SERVICE_HEADERS:
        value = msg.get(key)
        if value:
            headers[key] = str(value)[:500]

    content_hash = hashlib.sha256(
        f"{normalize_message_id(msg.get('Message-ID')) or ''}|{subject}|"
        f"{sender.get('email', '')}|{sent_at}|{body_text[:5000]}".encode("utf-8", errors="replace")
    ).hexdigest()

    return ParsedMessage(
        message_id=normalize_message_id(msg.get("Message-ID")),
        in_reply_to=(msg.get("In-Reply-To") or None),
        references=(msg.get("References") or None),
        subject=subject or "",
        normalized_subject=normalize_subject(subject),
        sender_name=sender.get("name", ""),
        sender_email=sender.get("email", ""),
        recipients=recipients,
        cc=cc,
        sent_at=sent_at,
        body_text=body_text,
        body_html=body_html,
        clean_text=cleaned,
        attachments=attachments,
        headers=headers,
        content_hash=content_hash,
        parse_error=",".join(errors)[:500] if errors else None,
    )


def recipients_json(items: list[dict]) -> str:
    return json.dumps(items, ensure_ascii=False)
