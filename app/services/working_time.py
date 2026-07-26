"""Расчёт фактического и рабочего времени ожидания."""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import date, datetime, time, timedelta, timezone
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError


@dataclass
class WorkSchedule:
    days: list[int] = field(default_factory=lambda: [1, 2, 3, 4, 5])  # 1=пн ... 7=вс
    start: str = "09:00"
    end: str = "18:00"
    timezone_name: str = "Europe/Moscow"
    holidays: list[str] = field(default_factory=list)

    @property
    def tz(self):
        try:
            return ZoneInfo(self.timezone_name)
        except (ZoneInfoNotFoundError, ValueError, KeyError):
            return timezone(timedelta(hours=3))  # Москва как запасной вариант

    @property
    def start_time(self) -> time:
        return _parse_time(self.start, time(9, 0))

    @property
    def end_time(self) -> time:
        return _parse_time(self.end, time(18, 0))

    @property
    def holiday_dates(self) -> set[date]:
        out: set[date] = set()
        for item in self.holidays or []:
            try:
                out.add(date.fromisoformat(str(item).strip()))
            except ValueError:
                continue
        return out

    @classmethod
    def from_settings(cls, data: dict) -> "WorkSchedule":
        return cls(
            days=list(data.get("work.days") or [1, 2, 3, 4, 5]),
            start=str(data.get("work.start") or "09:00"),
            end=str(data.get("work.end") or "18:00"),
            timezone_name=str(data.get("work.timezone") or "Europe/Moscow"),
            holidays=list(data.get("work.holidays") or []),
        )


def _parse_time(value: str, default: time) -> time:
    try:
        hh, mm = str(value).split(":")[:2]
        return time(int(hh), int(mm))
    except (ValueError, TypeError):
        return default


def to_local(dt: datetime, schedule: WorkSchedule) -> datetime:
    """naive UTC -> локальное время рабочего графика."""
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(schedule.tz)


def is_working_day(day: date, schedule: WorkSchedule) -> bool:
    if day in schedule.holiday_dates:
        return False
    return day.isoweekday() in (schedule.days or [1, 2, 3, 4, 5])


def elapsed_seconds(start: datetime | None, end: datetime | None = None) -> int:
    """Фактически прошедшее время в секундах (оба значения — naive UTC)."""
    if start is None:
        return 0
    end = end or datetime.now(timezone.utc).replace(tzinfo=None)
    delta = (end - start).total_seconds()
    return max(0, int(delta))


def business_seconds(
    start: datetime | None,
    end: datetime | None = None,
    schedule: WorkSchedule | None = None,
) -> int:
    """Рабочее время между двумя моментами (naive UTC на входе)."""
    if start is None:
        return 0
    schedule = schedule or WorkSchedule()
    end = end or datetime.now(timezone.utc).replace(tzinfo=None)
    if end <= start:
        return 0

    local_start = to_local(start, schedule)
    local_end = to_local(end, schedule)

    work_start, work_end = schedule.start_time, schedule.end_time
    if work_end <= work_start:  # некорректный график — считаем сутки рабочими
        return int((local_end - local_start).total_seconds())

    total = 0
    day = local_start.date()
    last_day = local_end.date()
    guard = 0
    while day <= last_day and guard < 3660:  # не более ~10 лет
        guard += 1
        if is_working_day(day, schedule):
            day_start = datetime.combine(day, work_start, tzinfo=schedule.tz)
            day_end = datetime.combine(day, work_end, tzinfo=schedule.tz)
            segment_start = max(day_start, local_start)
            segment_end = min(day_end, local_end)
            if segment_end > segment_start:
                total += int((segment_end - segment_start).total_seconds())
        day += timedelta(days=1)
    return total


def format_duration(seconds: int | None) -> str:
    """Человекочитаемая длительность на русском."""
    if seconds is None:
        return "—"
    seconds = int(max(0, seconds))
    if seconds < 60:
        return f"{seconds} сек"
    minutes = seconds // 60
    if minutes < 60:
        return f"{minutes} мин"
    hours, minutes = divmod(minutes, 60)
    if hours < 24:
        return f"{hours} ч {minutes:02d} мин" if minutes else f"{hours} ч"
    days, hours = divmod(hours, 24)
    return f"{days} дн {hours} ч" if hours else f"{days} дн"


def sla_hours_for(request_types: list[str], client_email: str | None, settings_data: dict) -> float:
    """Допустимое время ответа (в рабочих часах) для конкретного обращения."""
    default = float(settings_data.get("sla.critical_hours", 4) or 4)
    candidates = [default]

    vip_list = [str(v).lower() for v in settings_data.get("sla.vip_clients") or []]
    email = (client_email or "").lower()
    if email and any(v and (v == email or email.endswith("@" + v.lstrip("@"))) for v in vip_list):
        candidates.append(float(settings_data.get("sla.vip_hours", 1) or 1))

    types = set(request_types or [])
    if {"claim", "return", "warranty"} & types:
        candidates.append(float(settings_data.get("sla.claim_hours", 1) or 1))
    if "invoice" in types:
        candidates.append(float(settings_data.get("sla.invoice_hours", 3) or 3))

    return min(candidates)
