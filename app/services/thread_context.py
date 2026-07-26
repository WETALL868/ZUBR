"""Формирование очищенного контекста переписки для анализа."""

from __future__ import annotations

import hashlib
import json
from dataclasses import dataclass, field
from datetime import datetime

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.constants import ATTACHMENT_CATEGORY_RU, Direction
from app.models import Attachment, Message, Thread
from app.services.text_cleaner import MAX_THREAD_CHARS, clean_text, truncate


@dataclass
class AttachmentInfo:
    filename: str
    mime_type: str | None
    size: int | None
    category: str

    def as_text(self) -> str:
        size_kb = f"{round((self.size or 0) / 1024)} КБ" if self.size else "размер неизвестен"
        cat = ATTACHMENT_CATEGORY_RU.get(self.category, self.category)
        return f"{self.filename} ({self.mime_type or 'тип неизвестен'}, {size_kb}, категория: {cat})"


@dataclass
class MessageContext:
    ref: str                # M1, M2, ... — идентификатор для ссылок AI
    db_id: int
    direction: str
    sender: str
    sender_email: str
    recipients: str
    sent_at: datetime | None
    subject: str
    text: str
    attachments: list[AttachmentInfo] = field(default_factory=list)
    is_seen: bool = False
    is_automated: bool = False


@dataclass
class ThreadContext:
    thread_id: int
    subject: str
    client_email: str | None
    client_name: str | None
    messages: list[MessageContext]
    content_hash: str

    @property
    def incoming(self) -> list[MessageContext]:
        return [m for m in self.messages if m.direction == Direction.INCOMING]

    @property
    def outgoing(self) -> list[MessageContext]:
        return [m for m in self.messages if m.direction == Direction.OUTGOING]

    def client_text(self) -> str:
        return "\n\n".join(m.text for m in self.incoming if m.text)

    def manager_text(self) -> str:
        return "\n\n".join(m.text for m in self.outgoing if m.text)

    def to_prompt(self) -> str:
        """Текстовое представление переписки для языковой модели."""
        lines = [
            f"ТЕМА: {self.subject or '(без темы)'}",
            f"КЛИЕНТ: {self.client_name or ''} <{self.client_email or 'неизвестно'}>",
            f"ВСЕГО ПИСЕМ: {len(self.messages)}",
            "",
            "ПЕРЕПИСКА (хронологически):",
        ]
        for msg in self.messages:
            role = {
                Direction.INCOMING: "КЛИЕНТ",
                Direction.OUTGOING: "МЕНЕДЖЕР",
                Direction.INTERNAL: "ВНУТРЕННЕЕ",
            }.get(msg.direction, "НЕИЗВЕСТНО")
            when = msg.sent_at.strftime("%d.%m.%Y %H:%M") if msg.sent_at else "дата неизвестна"
            lines.append("")
            lines.append(f"--- [{msg.ref}] {role} | {msg.sender} | {when} ---")
            if msg.subject:
                lines.append(f"Тема: {msg.subject}")
            if msg.attachments:
                lines.append("Вложения: " + "; ".join(a.as_text() for a in msg.attachments))
            else:
                lines.append("Вложения: нет")
            lines.append(msg.text or "(пустое тело письма)")
        text = "\n".join(lines)
        return truncate(text, MAX_THREAD_CHARS)


def build_context(db: Session, thread: Thread) -> ThreadContext:
    """Собирает очищенный контекст цепочки из базы."""
    messages = db.scalars(
        select(Message).where(Message.thread_id == thread.id).order_by(Message.sent_at, Message.id)
    ).all()

    contexts: list[MessageContext] = []
    for idx, msg in enumerate(messages, start=1):
        text = msg.clean_text
        if text is None:
            text = clean_text(msg.body_text, msg.body_html)
        atts = db.scalars(
            select(Attachment).where(Attachment.message_id == msg.id)
        ).all()
        contexts.append(
            MessageContext(
                ref=f"M{idx}",
                db_id=msg.id,
                direction=msg.direction,
                sender=(msg.sender_name or msg.sender_email or "неизвестно"),
                sender_email=msg.sender_email or "",
                recipients=_recipients_text(msg.recipients),
                sent_at=msg.sent_at,
                subject=msg.subject or "",
                text=truncate(text or ""),
                attachments=[
                    AttachmentInfo(
                        filename=a.filename or "без имени",
                        mime_type=a.mime_type,
                        size=a.size,
                        category=a.attachment_category,
                    )
                    for a in atts
                    if not a.is_inline
                ],
                is_seen=msg.is_seen,
                is_automated=msg.is_automated,
            )
        )

    return ThreadContext(
        thread_id=thread.id,
        subject=thread.subject or "",
        client_email=thread.client_email,
        client_name=thread.client_name,
        messages=contexts,
        content_hash=compute_content_hash(contexts),
    )


def _recipients_text(raw: str | None) -> str:
    try:
        items = json.loads(raw or "[]")
    except (json.JSONDecodeError, TypeError):
        return ""
    out = []
    for item in items:
        if isinstance(item, dict):
            out.append(item.get("email", ""))
        else:
            out.append(str(item))
    return ", ".join(x for x in out if x)


def compute_content_hash(messages: list[MessageContext]) -> str:
    """Хеш содержимого цепочки: меняется при появлении нового письма/вложения."""
    parts = []
    for msg in messages:
        atts = "|".join(sorted(a.filename for a in msg.attachments))
        parts.append(f"{msg.db_id}:{msg.direction}:{(msg.text or '')[:2000]}:{atts}")
    payload = "\n".join(parts)
    return hashlib.sha256(payload.encode("utf-8", errors="replace")).hexdigest()
