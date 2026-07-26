"""Нормализация темы и группировка писем в цепочки."""

from __future__ import annotations

import pytest

from app.services.subject_utils import normalize_subject, strip_prefixes, subject_similarity
from app.services.threading_service import (
    GENERIC_SUBJECTS,
    normalize_message_id,
    parse_references,
)


@pytest.mark.parametrize(
    "raw,expected",
    [
        ("Re: Запрос счёта", "запрос счёта"),
        ("RE: RE: Fwd: Запрос счёта", "запрос счёта"),
        ("Re[2]: Запрос счёта", "запрос счёта"),
        ("FW: Ответ: Пересылка: Запрос счёта", "запрос счёта"),
        ("Ответ: цена на кабель", "цена на кабель"),
        ("Re:Re:Re: Fwd: Re: Сроки изготовления", "сроки изготовления"),
        ("  Запрос   счёта  ", "запрос счёта"),
        ("Запрос счёта.", "запрос счёта"),
        ("", ""),
        (None, ""),
    ],
)
def test_normalize_subject(raw, expected):
    assert normalize_subject(raw) == expected


def test_strip_prefixes_keeps_body():
    assert strip_prefixes("Re: Fwd: Важное письмо") == "Важное письмо"
    assert strip_prefixes("Обычная тема") == "Обычная тема"


def test_subject_similarity():
    assert subject_similarity("запрос счёта на насос", "запрос счёта на насос") == 1.0
    assert subject_similarity("запрос счёта", "погода в москве") == 0.0
    assert 0 < subject_similarity("запрос счёта на насос", "запрос счёта на трубу") < 1


def test_parse_references():
    raw = "<a@x.ru> <b@y.ru>\n <c@z.ru>"
    assert parse_references(raw) == ["a@x.ru", "b@y.ru", "c@z.ru"]
    assert parse_references(None) == []
    assert parse_references("") == []


def test_normalize_message_id():
    assert normalize_message_id("<abc@example.com>") == "abc@example.com"
    assert normalize_message_id("abc@example.com") == "abc@example.com"
    assert normalize_message_id(None) is None


def test_generic_subjects_not_merged():
    """Общие темы не должны служить основанием для объединения обращений."""
    for subject in ["Запрос", "Вопрос", "Здравствуйте", "Счет"]:
        assert normalize_subject(subject) in GENERIC_SUBJECTS
