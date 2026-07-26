"""Определение автоматических писем, рассылок и спама.

Такие письма не попадают в клиентскую аналитику (статус NOT_RELEVANT).
"""

from __future__ import annotations

import re
from typing import Any

from app.services.email_utils import email_domain

# Локальные части адресов, характерные для автоматических отправителей
_AUTO_LOCAL_PARTS = (
    "noreply", "no-reply", "no_reply", "donotreply", "do-not-reply", "notreply",
    "mailer-daemon", "postmaster", "bounce", "notification", "notifications",
    "notify", "alert", "alerts", "robot", "auto", "automail", "mailer",
    "newsletter", "news", "info-noreply", "support-noreply", "delivery",
)

# Домены типовых автоматических источников
_AUTO_DOMAIN_HINTS = (
    "sberbank.ru", "vtb.ru", "alfabank.ru", "tinkoff.ru", "tbank.ru", "raiffeisen.ru",
    "ozon.ru", "wildberries.ru", "market.yandex.ru", "avito.ru", "sbermarket.ru",
    "cdek.ru", "dellin.ru", "pecom.ru", "pochta.ru", "boxberry.ru", "dpd.ru",
    "mailchimp", "sendpulse", "unisender", "mailgun", "sendgrid", "amazonses",
    "notifications", "noreply", "no-reply", "bitrix24", "amocrm", "megaplan",
    "hh.ru", "superjob.ru", "nalog.ru", "diadoc.ru", "kontur.ru",
)

_SUBJECT_PATTERNS = [
    r"\bunsubscribe\b", r"отписа(ться|ние)", r"\bnewsletter\b",
    r"рассылк", r"акци[яи]\b", r"скидк", r"распродаж", r"промокод",
    r"вебинар", r"приглашаем\s+на", r"дайджест", r"подборка\s+вакансий",
    r"автоответ", r"out\s+of\s+office", r"auto[-\s]?reply",
    r"undeliverable", r"delivery\s+status\s+notification", r"mail\s+delivery\s+failed",
    r"письмо\s+не\s+доставлено", r"чек\s+№", r"кассовый\s+чек",
    r"код\s+подтверждения", r"верификац", r"подтвердите\s+(email|адрес|регистрац)",
    r"уведомление\s+о\s+платеже", r"выписка\s+по\s+счету", r"выписка\s+по\s+счёту",
]
_SUBJECT_RE = [re.compile(p, re.IGNORECASE) for p in _SUBJECT_PATTERNS]

# Служебные заголовки, однозначно указывающие на автоматическое письмо
_AUTO_HEADER_KEYS = (
    "auto-submitted", "precedence", "list-unsubscribe", "list-id",
    "x-autoreply", "x-autorespond", "x-mailer-daemon", "x-campaign",
    "x-mailchimp-id", "feedback-id", "x-report-abuse",
)


def classify_message(
    sender_email: str,
    subject: str,
    headers: dict[str, Any] | None,
    folder_type: str | None,
    exclusion_settings: dict[str, Any] | None = None,
) -> tuple[bool, str | None]:
    """Возвращает (is_automated, причина)."""
    exclusion_settings = exclusion_settings or {}
    sender = (sender_email or "").lower()
    local = sender.split("@", 1)[0] if "@" in sender else sender
    domain = email_domain(sender)
    subject = subject or ""
    headers = {k.lower(): v for k, v in (headers or {}).items()}

    # 1. Папка спама
    if folder_type == "spam":
        return True, "Письмо из папки «Спам»"

    # 2. Пользовательские исключения
    for banned in exclusion_settings.get("exclusions.senders") or []:
        if banned and banned.lower() in sender:
            return True, f"Отправитель в списке исключений ({banned})"
    for banned in exclusion_settings.get("exclusions.domains") or []:
        b = (banned or "").lower().lstrip("@")
        if not b:
            continue
        if b in domain or b in local:
            return True, f"Домен/адрес в списке исключений ({banned})"

    # 3. Служебные заголовки
    if exclusion_settings.get("exclusions.use_headers", True):
        auto_submitted = str(headers.get("auto-submitted", "")).lower()
        if auto_submitted and auto_submitted != "no":
            return True, "Заголовок Auto-Submitted"
        precedence = str(headers.get("precedence", "")).lower()
        if precedence in {"bulk", "junk", "list", "auto_reply"}:
            return True, f"Заголовок Precedence: {precedence}"
        if headers.get("list-unsubscribe"):
            return True, "Заголовок List-Unsubscribe (массовая рассылка)"
        if headers.get("list-id"):
            return True, "Заголовок List-Id (рассылка)"
        for key in ("x-autoreply", "x-autorespond", "x-campaign", "feedback-id"):
            if headers.get(key):
                return True, f"Служебный заголовок {key}"

    # 4. Адрес отправителя
    for part in _AUTO_LOCAL_PARTS:
        if local == part or local.startswith(part + ".") or local.startswith(part + "-") or part in local.split("."):
            return True, f"Служебный адрес отправителя ({local})"
    for hint in _AUTO_DOMAIN_HINTS:
        if hint in domain:
            return True, f"Автоматический источник ({domain})"

    # 5. Тема письма
    for pattern in _SUBJECT_RE:
        if pattern.search(subject):
            return True, "Тема письма похожа на рассылку/уведомление"
    for keyword in exclusion_settings.get("exclusions.subject_keywords") or []:
        if keyword and keyword.lower() in subject.lower():
            return True, f"Ключевое слово в теме ({keyword})"

    return False, None
