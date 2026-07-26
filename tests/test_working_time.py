"""Расчёт фактического и рабочего времени ожидания."""

from __future__ import annotations

from datetime import datetime

from app.services.working_time import (
    WorkSchedule,
    business_seconds,
    elapsed_seconds,
    format_duration,
    is_working_day,
    sla_hours_for,
)

# График: пн-пт 09:00-18:00, Europe/Moscow (UTC+3)
SCHEDULE = WorkSchedule(days=[1, 2, 3, 4, 5], start="09:00", end="18:00", timezone_name="Europe/Moscow")


def utc(y, m, d, hh, mm=0):
    return datetime(y, m, d, hh, mm)


def test_elapsed_seconds():
    assert elapsed_seconds(utc(2026, 7, 20, 10), utc(2026, 7, 20, 12)) == 7200
    assert elapsed_seconds(None) == 0
    assert elapsed_seconds(utc(2026, 7, 20, 12), utc(2026, 7, 20, 10)) == 0


def test_business_seconds_within_one_day():
    # 10:00–13:00 по Москве = 07:00–10:00 UTC → 3 рабочих часа
    assert business_seconds(utc(2026, 7, 20, 7), utc(2026, 7, 20, 10), SCHEDULE) == 3 * 3600


def test_business_seconds_skips_night():
    # Понедельник 17:00 МСК → вторник 10:00 МСК = 1 ч + 1 ч = 2 рабочих часа
    start = utc(2026, 7, 20, 14)   # пн 17:00 МСК
    end = utc(2026, 7, 21, 7)      # вт 10:00 МСК
    assert business_seconds(start, end, SCHEDULE) == 2 * 3600


def test_business_seconds_skips_weekend():
    # Пятница 17:00 МСК → понедельник 10:00 МСК = 1 ч (пт) + 1 ч (пн) = 2 часа
    start = utc(2026, 7, 24, 14)   # пт 17:00 МСК
    end = utc(2026, 7, 27, 7)      # пн 10:00 МСК
    assert business_seconds(start, end, SCHEDULE) == 2 * 3600
    # Фактическое время при этом велико
    assert elapsed_seconds(start, end) > 60 * 3600


def test_business_seconds_holiday_excluded():
    schedule = WorkSchedule(
        days=[1, 2, 3, 4, 5], start="09:00", end="18:00",
        timezone_name="Europe/Moscow", holidays=["2026-07-21"],
    )
    start = utc(2026, 7, 20, 14)   # пн 17:00 МСК
    end = utc(2026, 7, 22, 7)      # ср 10:00 МСК, вторник — праздник
    assert business_seconds(start, end, schedule) == 2 * 3600


def test_business_seconds_zero_when_outside_hours():
    # Суббота целиком
    assert business_seconds(utc(2026, 7, 25, 6), utc(2026, 7, 25, 20), SCHEDULE) == 0


def test_is_working_day():
    assert is_working_day(datetime(2026, 7, 20).date(), SCHEDULE) is True   # понедельник
    assert is_working_day(datetime(2026, 7, 25).date(), SCHEDULE) is False  # суббота


def test_format_duration():
    assert format_duration(30) == "30 сек"
    assert format_duration(600) == "10 мин"
    assert format_duration(3600) == "1 ч"
    assert format_duration(3660) == "1 ч 01 мин"
    assert format_duration(90000) == "1 дн 1 ч"
    assert format_duration(None) == "—"


def test_sla_hours_selection():
    settings = {
        "sla.critical_hours": 4,
        "sla.vip_hours": 1,
        "sla.claim_hours": 1,
        "sla.invoice_hours": 3,
        "sla.vip_clients": ["vip.ru"],
    }
    assert sla_hours_for([], "client@other.ru", settings) == 4
    assert sla_hours_for(["invoice"], "client@other.ru", settings) == 3
    assert sla_hours_for(["claim"], "client@other.ru", settings) == 1
    assert sla_hours_for([], "boss@vip.ru", settings) == 1


def test_invalid_timezone_falls_back():
    schedule = WorkSchedule(timezone_name="Не/Существует")
    assert business_seconds(utc(2026, 7, 20, 7), utc(2026, 7, 20, 10), schedule) == 3 * 3600
