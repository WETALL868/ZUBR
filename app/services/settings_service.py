"""Бизнес-настройки приложения, хранимые в таблице app_settings.

Секреты сюда не попадают — они читаются только из .env.
Значения по умолчанию берутся из констант ниже и частично из .env.
"""

from __future__ import annotations

import json
from typing import Any

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import settings as env_settings
from app.models import AppSetting

# Ключи и значения по умолчанию
DEFAULTS: dict[str, Any] = {
    # Организация
    "corporate.domains": [],
    "corporate.emails": [],
    "corporate.managers": [],  # [{"email": "...", "name": "..."}]
    # Рабочее время
    "work.days": [1, 2, 3, 4, 5],  # 1=понедельник ... 7=воскресенье
    "work.start": "09:00",
    "work.end": "18:00",
    "work.timezone": "Europe/Moscow",
    "work.holidays": [],  # ["2026-01-01", ...]
    # Сроки реакции (в рабочих часах)
    "sla.warning_hours": 2,
    "sla.critical_hours": 4,
    "sla.vip_hours": 1,
    "sla.claim_hours": 1,
    "sla.invoice_hours": 3,
    "sla.count_weekends": False,
    "sla.vip_clients": [],  # email или домен
    # Исключения
    "exclusions.senders": [],
    "exclusions.domains": [
        "noreply", "no-reply", "donotreply", "mailer-daemon",
    ],
    "exclusions.subject_keywords": [
        "рассылка", "новостная рассылка", "unsubscribe", "отписаться",
        "уведомление системы", "автоответ", "out of office",
    ],
    "exclusions.use_headers": True,
    # AI
    "ai.enabled": env_settings.ai_enabled,
    "ai.test_mode": env_settings.ai_test_mode,
    "ai.max_threads_per_run": env_settings.ai_max_threads_per_run,
    "ai.model": env_settings.openai_model,
    # Вложения
    "attachments.save_local": env_settings.save_attachments,
    # Синхронизация
    "sync.default_days": 7,
    "sync.max_messages_per_folder": 2000,
}

_LIST_KEYS = {
    "corporate.domains",
    "corporate.emails",
    "exclusions.senders",
    "exclusions.domains",
    "exclusions.subject_keywords",
    "sla.vip_clients",
    "work.holidays",
}


def get_setting(db: Session, key: str, default: Any = None) -> Any:
    row = db.get(AppSetting, key)
    if row is None or row.value is None:
        return DEFAULTS.get(key, default)
    try:
        return json.loads(row.value)
    except (json.JSONDecodeError, TypeError):
        return row.value


def set_setting(db: Session, key: str, value: Any) -> None:
    row = db.get(AppSetting, key)
    payload = json.dumps(value, ensure_ascii=False)
    if row is None:
        db.add(AppSetting(key=key, value=payload))
    else:
        row.value = payload
    db.flush()


def get_all_settings(db: Session) -> dict[str, Any]:
    """Все настройки: значения по умолчанию, перекрытые тем, что есть в базе."""
    result = dict(DEFAULTS)
    for row in db.scalars(select(AppSetting)).all():
        try:
            result[row.key] = json.loads(row.value) if row.value is not None else None
        except (json.JSONDecodeError, TypeError):
            result[row.key] = row.value
    # Корпоративные данные из .env добавляются к тем, что заданы в интерфейсе
    domains = set(result.get("corporate.domains") or []) | set(env_settings.corporate_domain_list)
    emails = set(result.get("corporate.emails") or []) | set(env_settings.corporate_email_list)
    result["corporate.domains"] = sorted(d.lower().lstrip("@") for d in domains if d)
    result["corporate.emails"] = sorted(e.lower() for e in emails if e)
    return result


def update_settings(db: Session, values: dict[str, Any]) -> None:
    for key, value in values.items():
        if key in _LIST_KEYS and isinstance(value, str):
            value = [v.strip().lower() for v in value.replace("\n", ",").split(",") if v.strip()]
        set_setting(db, key, value)
    db.commit()


def corporate_identity(db: Session) -> tuple[set[str], set[str]]:
    """Возвращает (домены, адреса) компании в нижнем регистре."""
    data = get_all_settings(db)
    domains = {d.lower().lstrip("@") for d in data.get("corporate.domains") or [] if d}
    emails = {e.lower() for e in data.get("corporate.emails") or [] if e}
    for mgr in data.get("corporate.managers") or []:
        email = (mgr or {}).get("email", "").strip().lower()
        if email:
            emails.add(email)
    return domains, emails


def manager_names(db: Session) -> dict[str, str]:
    """email -> отображаемое имя менеджера."""
    data = get_all_settings(db)
    out: dict[str, str] = {}
    for mgr in data.get("corporate.managers") or []:
        email = (mgr or {}).get("email", "").strip().lower()
        name = (mgr or {}).get("name", "").strip()
        if email:
            out[email] = name or email
    return out
