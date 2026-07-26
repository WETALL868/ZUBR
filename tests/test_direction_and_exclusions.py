"""Определение направления письма и работа исключений."""

from __future__ import annotations

import pytest

from app.core.constants import Direction
from app.services.email_utils import (
    decode_mime_words,
    detect_direction,
    email_domain,
    guess_company_name,
    is_corporate,
    parse_addresses,
    parse_date_header,
)
from app.services.exclusions import classify_message

DOMAINS = {"mycompany.ru"}
EMAILS = {"sales@yandex.ru"}


def test_direction_incoming():
    assert detect_direction("client@other.ru", ["manager@mycompany.ru"], DOMAINS, EMAILS) == Direction.INCOMING


def test_direction_outgoing():
    assert detect_direction("manager@mycompany.ru", ["client@other.ru"], DOMAINS, EMAILS) == Direction.OUTGOING


def test_direction_internal():
    assert detect_direction(
        "a@mycompany.ru", ["b@mycompany.ru"], DOMAINS, EMAILS
    ) == Direction.INTERNAL


def test_direction_by_explicit_email():
    """Корпоративный адрес на общем домене распознаётся как сотрудник."""
    assert detect_direction("sales@yandex.ru", ["client@other.ru"], DOMAINS, EMAILS) == Direction.OUTGOING
    assert detect_direction("someone@yandex.ru", ["sales@yandex.ru"], DOMAINS, EMAILS) == Direction.INCOMING


def test_subdomain_is_corporate():
    assert is_corporate("a@mail.mycompany.ru", DOMAINS, EMAILS) is True
    assert is_corporate("a@notmycompany.ru", DOMAINS, EMAILS) is False


def test_email_domain_and_company():
    assert email_domain("A@Example.RU") == "example.ru"
    assert guess_company_name("buyer@stroymash.ru") == "Stroymash"
    assert guess_company_name("person@mail.ru") is None  # публичный почтовик


def test_parse_addresses_mime():
    result = parse_addresses("=?utf-8?B?0J/QtdGC0YDQvtCy0LA=?= <petrova@mycompany.ru>")
    assert result[0]["email"] == "petrova@mycompany.ru"
    assert result[0]["name"] == "Петрова"


def test_decode_broken_header_does_not_raise():
    assert isinstance(decode_mime_words("=?unknown-charset?B?abc?="), str)


def test_parse_date_header():
    dt = parse_date_header("Tue, 21 Jul 2026 09:15:00 +0300")
    assert dt is not None and dt.hour == 6  # приведено к UTC
    assert parse_date_header("некорректная дата") is None
    assert parse_date_header(None) is None


@pytest.mark.parametrize(
    "sender,subject,headers,expected",
    [
        ("noreply@bank.ru", "Уведомление", {}, True),
        ("no-reply@shop.com", "Заказ", {}, True),
        ("news@promo.com", "Скидки до 40%", {}, True),
        ("client@zavod.ru", "Запрос счёта", {}, False),
        ("client@zavod.ru", "Запрос", {"Auto-Submitted": "auto-generated"}, True),
        ("client@zavod.ru", "Запрос", {"Precedence": "bulk"}, True),
        ("client@zavod.ru", "Запрос", {"List-Unsubscribe": "<mailto:x@y.z>"}, True),
        ("client@zavod.ru", "Запрос", {"Auto-Submitted": "no"}, False),
        ("info@cdek.ru", "Ваш заказ в пути", {}, True),
    ],
)
def test_classify_message(sender, subject, headers, expected):
    is_auto, reason = classify_message(sender, subject, headers, "inbox", {})
    assert is_auto is expected
    if expected:
        assert reason


def test_spam_folder_excluded():
    is_auto, reason = classify_message("client@zavod.ru", "Запрос счёта", {}, "spam", {})
    assert is_auto is True
    assert "Спам" in reason


def test_user_exclusions():
    settings = {"exclusions.senders": ["partner@x.ru"], "exclusions.domains": ["adsite.com"]}
    assert classify_message("partner@x.ru", "Тема", {}, "inbox", settings)[0] is True
    assert classify_message("any@adsite.com", "Тема", {}, "inbox", settings)[0] is True
    assert classify_message("client@zavod.ru", "Тема", {}, "inbox", settings)[0] is False
