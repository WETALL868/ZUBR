"""Распознавание запросов клиента и проверка их выполнения."""

from __future__ import annotations

import pytest

from app.core.constants import RequestType
from app.services import request_rules as rules
from app.services.attachments import (
    categorize,
    is_dangerous,
    looks_like_invoice,
    looks_like_offer,
    safe_filename,
)


def find_types(text: str) -> set[str]:
    return {p.request_type for p in rules.REQUEST_PATTERNS if p.search(text)}


# ---------- распознавание ----------

def test_detect_invoice_variants():
    for text in [
        "Прошу выставить счёт на 10 штук",
        "Выставите, пожалуйста, счет",
        "Нужен счёт на оплату",
        "Пришлите счет на 5 шт",
    ]:
        assert RequestType.INVOICE in find_types(text), text


def test_detect_five_tasks_in_one_message():
    text = (
        "Прошу сообщить цену, наличие, срок поставки и выставить счёт "
        "на 10 штук с доставкой до Красноярска."
    )
    found = find_types(text)
    assert RequestType.PRICE in found
    assert RequestType.AVAILABILITY in found
    assert RequestType.DELIVERY_TIME in found
    assert RequestType.INVOICE in found
    assert RequestType.DELIVERY_COST in found


def test_negation_is_not_a_request():
    """«Реквизиты не нужны» — это не запрос реквизитов."""
    assert RequestType.REQUISITES not in find_types("Реквизиты не нужны, мы у вас уже покупали")


def test_mention_without_request_verb_ignored():
    """Упоминание чертежа без просьбы не считается запросом документации."""
    assert RequestType.TECHNICAL_SPECIFICATION not in find_types(
        "Какой срок изготовления корпуса по чертежу?"
    )
    assert RequestType.TECHNICAL_SPECIFICATION in find_types(
        "Пришлите, пожалуйста, чертёж корпуса"
    )


# ---------- расплывчатые формулировки ----------

@pytest.mark.parametrize(
    "text",
    [
        "Уточняем у поставщика, сообщим дополнительно",
        "Скоро ответим",
        "Можем подобрать аналог",
        "Свяжемся с вами позже",
    ],
)
def test_vague_answers(text):
    assert rules.is_vague(text) is True


def test_specific_answer_is_not_vague():
    assert rules.is_vague("Цена 450 руб. за штуку, срок поставки 5 рабочих дней") is False


# ---------- выполнение ----------

def test_price_pattern():
    assert rules.PRICE_RE.search("цена 12 500 руб. за штуку")
    assert rules.PRICE_RE.search("стоимость 780 рублей")
    assert not rules.PRICE_RE.search("цену уточняем")


def test_availability_pattern():
    assert rules.AVAILABILITY_RE.search("товар в наличии на складе")
    assert rules.AVAILABILITY_RE.search("нет в наличии, под заказ")
    assert rules.AVAILABILITY_RE.search("на складе 40 шт")
    assert not rules.AVAILABILITY_RE.search("сейчас посмотрим по складу")


def test_delivery_time_pattern():
    assert rules.DELIVERY_TIME_RE.search("срок поставки 5 рабочих дней")
    assert rules.DELIVERY_TIME_RE.search("отгрузим 15.08")
    assert not rules.DELIVERY_TIME_RE.search("постараемся быстро")


def test_invoice_sent_pattern():
    assert rules.INVOICE_SENT_RE.search("Счёт на оплату № 1043 направляю во вложении")
    assert rules.INVOICE_SENT_RE.search("счет во вложении")
    assert not rules.INVOICE_SENT_RE.search("цена 500 рублей, товар в наличии")


def test_analogue_requires_model():
    text = "Предлагаем замену: датчик ОВЕН ПД100-ДИ0.25-111-0.5, полный аналог"
    assert rules.ANALOGUE_OFFER_RE.search(text)
    assert rules.MODEL_CODE_RE.search(text)
    vague = "Можем подобрать аналог"
    assert rules.is_vague(vague)


def test_manager_requests_from_client():
    reasons = rules.manager_requests_from_client(
        "Для выставления счёта пришлите, пожалуйста, реквизиты вашей организации."
    )
    assert reasons and "реквизит" in reasons[0].lower()
    assert rules.manager_requests_from_client("Счёт во вложении.") == []


# ---------- вложения ----------

def test_attachment_categorization():
    assert categorize("Счет_1043.pdf") == "invoice"
    assert categorize("КП_светильники.pdf") == "commercial_offer"
    assert categorize("Договор_поставки.docx") == "contract"
    assert categorize("photo.jpg") == "image"
    assert categorize("readme.txt") == "other"


def test_invoice_detection_needs_name_and_format():
    """Расширение само по себе не делает файл счётом."""
    assert looks_like_invoice("Счет_1043.pdf") is True
    assert looks_like_invoice("invoice_2026.xlsx") is True
    assert looks_like_invoice("prezentaciya.pdf") is False
    assert looks_like_invoice("счет.jpg") is False  # не документный формат
    assert looks_like_offer("КП_на_насосы.pdf") is True


def test_safe_filename_blocks_traversal():
    assert "/" not in safe_filename("../../etc/passwd")
    assert ".." not in safe_filename("../../etc/passwd")
    assert "\\" not in safe_filename(r"C:\Windows\system32\evil.txt")
    assert safe_filename("") == "attachment"


def test_dangerous_extensions():
    assert is_dangerous("update.exe") is True
    assert is_dangerous("script.bat") is True
    assert is_dangerous("Счет.pdf") is False


def test_denial_is_not_fulfillment():
    """«Счёт пока не отправляли» не должно засчитываться как отправленный счёт."""
    for text in [
        "Счёт пока не отправляли",
        "КП ещё не готово",
        "Счёт сформировать не смогли",
    ]:
        assert rules.DENIAL.search(text), text
    assert not rules.DENIAL.search("Счёт на оплату № 1043 направляю во вложении")
