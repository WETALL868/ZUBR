"""Разбор адресов, определение направления письма, определение компании клиента."""

from __future__ import annotations

import re
from email.header import decode_header, make_header
from email.utils import getaddresses, parsedate_to_datetime
from datetime import datetime, timezone

from app.core.constants import Direction

_FREE_MAIL_DOMAINS = {
    "mail.ru", "yandex.ru", "ya.ru", "yandex.com", "gmail.com", "googlemail.com",
    "bk.ru", "inbox.ru", "list.ru", "rambler.ru", "outlook.com", "hotmail.com",
    "icloud.com", "me.com", "yahoo.com", "internet.ru", "vk.com", "proton.me",
    "protonmail.com", "qq.com", "163.com", "aol.com", "gmx.com", "zoho.com",
}


def decode_mime_words(value: str | None) -> str:
    """Декодирует MIME-заголовок (=?utf-8?B?...?=) в читаемый текст."""
    if not value:
        return ""
    try:
        return str(make_header(decode_header(value))).strip()
    except (UnicodeDecodeError, LookupError, ValueError):
        # Повреждённый или неизвестная кодировка — возвращаем как есть
        try:
            parts = decode_header(value)
            out = []
            for text, charset in parts:
                if isinstance(text, bytes):
                    out.append(text.decode(charset or "utf-8", errors="replace"))
                else:
                    out.append(text)
            return "".join(out).strip()
        except Exception:
            return str(value).strip()


def parse_addresses(raw: str | None) -> list[dict[str, str]]:
    """['Имя <a@b.ru>', ...] -> [{'name': 'Имя', 'email': 'a@b.ru'}]"""
    if not raw:
        return []
    decoded = decode_mime_words(raw)
    result: list[dict[str, str]] = []
    for name, addr in getaddresses([decoded]):
        addr = (addr or "").strip().lower()
        if not addr or "@" not in addr:
            continue
        result.append({"name": decode_mime_words(name).strip(' "\''), "email": addr})
    return result


def first_address(raw: str | None) -> dict[str, str]:
    addrs = parse_addresses(raw)
    return addrs[0] if addrs else {"name": "", "email": ""}


def email_domain(email: str | None) -> str:
    if not email or "@" not in email:
        return ""
    return email.rsplit("@", 1)[-1].strip().lower()


def is_corporate(email: str | None, domains: set[str], emails: set[str]) -> bool:
    if not email:
        return False
    e = email.strip().lower()
    if e in emails:
        return True
    domain = email_domain(e)
    if not domain:
        return False
    return any(domain == d or domain.endswith("." + d) for d in domains if d)


def detect_direction(
    sender_email: str,
    recipient_emails: list[str],
    domains: set[str],
    emails: set[str],
) -> str:
    """Направление письма относительно компании."""
    sender_corp = is_corporate(sender_email, domains, emails)
    recipients_corp = [is_corporate(r, domains, emails) for r in recipient_emails if r]

    if sender_corp:
        if recipients_corp and all(recipients_corp):
            return Direction.INTERNAL
        return Direction.OUTGOING
    if not sender_email:
        return Direction.UNKNOWN
    return Direction.INCOMING


def guess_company_name(email: str | None, display_name: str | None = None) -> str | None:
    """Название компании по домену. Для публичных почтовиков — None."""
    domain = email_domain(email)
    if not domain or domain in _FREE_MAIL_DOMAINS:
        return None
    base = domain.split(".")[0]
    if not base or len(base) < 2:
        return None
    return base.upper() if len(base) <= 4 else base.capitalize()


def parse_date_header(value: str | None) -> datetime | None:
    """Разбирает заголовок Date в naive UTC."""
    if not value:
        return None
    try:
        dt = parsedate_to_datetime(value)
    except (TypeError, ValueError, IndexError):
        return None
    if dt is None:
        return None
    if dt.tzinfo is None:
        return dt
    return dt.astimezone(timezone.utc).replace(tzinfo=None)


_NAME_CLEAN_RE = re.compile(r"[<>\"']")


def display_person(name: str | None, email: str | None) -> str:
    name = _NAME_CLEAN_RE.sub("", (name or "")).strip()
    if name:
        return name
    return email or "неизвестно"
