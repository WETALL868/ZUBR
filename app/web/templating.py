"""Настройка Jinja2 и вспомогательные фильтры для шаблонов."""

from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path

from fastapi.templating import Jinja2Templates

from app.core.constants import (
    ATTACHMENT_CATEGORY_RU,
    DIRECTION_RU,
    REQUEST_TYPE_RU,
    RESPONSIBLE_PARTY_RU,
    STATUS_COLOR,
    STATUS_RU,
)
from app.services.working_time import WorkSchedule, format_duration

TEMPLATES_DIR = Path(__file__).resolve().parent / "templates"
templates = Jinja2Templates(directory=str(TEMPLATES_DIR))

_schedule = WorkSchedule()


def set_schedule(schedule: WorkSchedule) -> None:
    global _schedule
    _schedule = schedule


def local_dt(value: datetime | None, fmt: str = "%d.%m.%Y %H:%M") -> str:
    """naive UTC -> локальное время в формате рабочего графика."""
    if value is None:
        return "—"
    aware = value.replace(tzinfo=timezone.utc)
    return aware.astimezone(_schedule.tz).strftime(fmt)


def local_time(value: datetime | None) -> str:
    return local_dt(value, "%H:%M")


def local_date(value: datetime | None) -> str:
    return local_dt(value, "%d.%m.%Y")


def duration(value: int | None) -> str:
    return format_duration(value)


def status_ru(value: str | None) -> str:
    return STATUS_RU.get(value or "", value or "—")


def status_color(value: str | None) -> str:
    return STATUS_COLOR.get(value or "", "gray")


def request_ru(value: str | None) -> str:
    return REQUEST_TYPE_RU.get(value or "", value or "—")


def direction_ru(value: str | None) -> str:
    return DIRECTION_RU.get(value or "", value or "—")


def attachment_ru(value: str | None) -> str:
    return ATTACHMENT_CATEGORY_RU.get(value or "", value or "Файл")


def party_ru(value: str | None) -> str:
    return RESPONSIBLE_PARTY_RU.get(value or "", "—")


def percent(value: float | None) -> str:
    if value is None:
        return "—"
    return f"{round(value * 100)}%"


def from_json(value: str | None) -> list | dict:
    if not value:
        return []
    try:
        return json.loads(value)
    except (json.JSONDecodeError, TypeError):
        return []


def short(value: str | None, limit: int = 90) -> str:
    text = (value or "").strip().replace("\n", " ")
    return text if len(text) <= limit else text[: limit - 1] + "…"


templates.env.filters.update(
    {
        "local_dt": local_dt,
        "local_time": local_time,
        "local_date": local_date,
        "duration": duration,
        "status_ru": status_ru,
        "status_color": status_color,
        "request_ru": request_ru,
        "direction_ru": direction_ru,
        "attachment_ru": attachment_ru,
        "party_ru": party_ru,
        "percent": percent,
        "from_json": from_json,
        "short": short,
    }
)
templates.env.globals.update(
    {
        "STATUS_RU": STATUS_RU,
        "STATUS_COLOR": STATUS_COLOR,
        "REQUEST_TYPE_RU": REQUEST_TYPE_RU,
    }
)
