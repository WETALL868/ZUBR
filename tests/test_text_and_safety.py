"""Очистка текста, безопасный режим и разбор повреждённых писем."""

from __future__ import annotations

from email import message_from_bytes

import pytest

from app.services.mail_actions import MailActionsDisabled, is_write_allowed
from app.services.message_parser import parse_message
from app.services.text_cleaner import clean_text, html_to_text, strip_quoted_history, truncate


# ---------- очистка текста ----------

def test_html_to_text_removes_markup():
    html = "<html><head><style>p{color:red}</style></head><body><p>Цена 500 руб.</p><br><p>Спасибо</p></body></html>"
    text = html_to_text(html)
    assert "Цена 500 руб." in text
    assert "color:red" not in text
    assert "<p>" not in text


def test_quoted_history_removed_but_request_kept():
    body = (
        "Добрый день! Прошу выставить счёт на 10 штук.\n\n"
        "21.07.2026, Иванов Сергей писал:\n"
        "> Здравствуйте, отправляю прайс-лист\n"
        "> с уважением"
    )
    cleaned, had_quote = strip_quoted_history(body)
    assert "Прошу выставить счёт" in cleaned
    assert "отправляю прайс-лист" not in cleaned
    assert had_quote is True


def test_clean_text_keeps_key_facts():
    body = (
        "Здравствуйте!\n\n"
        "Цена 12 500 руб. за шт., артикул АИР100S4, срок 5 рабочих дней, "
        "доставка до Красноярска.\n\n"
        "С уважением,\nАнна Петрова\nООО «Компания»\nтел. +7 000 000-00-00\n"
        "Конфиденциальная информация: настоящее письмо предназначено только адресату."
    )
    cleaned = clean_text(body)
    for fact in ["12 500", "АИР100S4", "5 рабочих дней", "Красноярска"]:
        assert fact in cleaned, fact
    assert "Конфиденциальная информация" not in cleaned


def test_clean_text_handles_empty():
    assert clean_text(None) == ""
    assert clean_text("") == ""
    assert clean_text("", "<p>текст из HTML</p>") == "текст из HTML"


def test_truncate_keeps_head_and_tail():
    text = "начало " + "x" * 5000 + " конец"
    result = truncate(text, 500)
    assert result.startswith("начало")
    assert result.endswith("конец")
    assert len(result) < 700


# ---------- разбор писем ----------

def test_parse_message_with_attachment():
    raw = (
        b"From: Client <client@zavod.ru>\r\n"
        b"To: manager@company.ru\r\n"
        b"Subject: =?utf-8?B?0JfQsNC/0YDQvtGB?=\r\n"
        b"Date: Tue, 21 Jul 2026 09:15:00 +0300\r\n"
        b"Message-ID: <abc@zavod.ru>\r\n"
        b'Content-Type: multipart/mixed; boundary="BOUND"\r\n\r\n'
        b"--BOUND\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n"
        b"\xd0\xa2\xd0\xb5\xd0\xba\xd1\x81\xd1\x82\r\n"
        b"--BOUND\r\nContent-Type: application/pdf\r\n"
        b'Content-Disposition: attachment; filename="schet_1.pdf"\r\n\r\n'
        b"PDFDATA\r\n--BOUND--\r\n"
    )
    parsed = parse_message(message_from_bytes(raw))
    assert parsed.sender_email == "client@zavod.ru"
    assert parsed.subject == "Запрос"
    assert parsed.message_id == "abc@zavod.ru"
    assert len(parsed.attachments) == 1
    assert parsed.attachments[0].filename == "schet_1.pdf"
    assert parsed.attachments[0].category == "invoice"


def test_parse_message_with_windows1251():
    body = "Цена 500 рублей".encode("windows-1251")
    raw = (
        b"From: a@b.ru\r\nTo: c@d.ru\r\nSubject: test\r\n"
        b"Content-Type: text/plain; charset=windows-1251\r\n"
        b"Content-Transfer-Encoding: 8bit\r\n\r\n" + body
    )
    parsed = parse_message(message_from_bytes(raw))
    assert "500" in parsed.body_text


def test_parse_broken_message_does_not_raise():
    raw = b"\xff\xfe not a real email at all"
    parsed = parse_message(message_from_bytes(raw))
    assert parsed.sender_email == ""
    assert isinstance(parsed.body_text, str)


def test_parse_message_without_body():
    raw = b"From: a@b.ru\r\nTo: c@d.ru\r\nSubject: empty\r\n\r\n"
    parsed = parse_message(message_from_bytes(raw))
    assert parsed.body_text == ""
    assert parsed.clean_text == ""


def test_parse_message_without_date():
    raw = "From: a@b.ru\r\nTo: c@d.ru\r\nSubject: no date\r\n\r\nтекст".encode("utf-8")
    parsed = parse_message(message_from_bytes(raw))
    assert parsed.sent_at is None


# ---------- безопасный режим ----------

def test_write_actions_are_disabled():
    from app.services import mail_actions

    for action in (
        mail_actions.send_email,
        mail_actions.create_draft,
        mail_actions.mark_as_read,
        mail_actions.move_message,
        mail_actions.delete_message,
        mail_actions.set_flag,
    ):
        with pytest.raises(MailActionsDisabled):
            action()


def test_is_write_allowed_is_false():
    assert is_write_allowed() is False


def test_imap_module_has_no_mutating_commands():
    """В IMAP-клиенте не должно быть команд, изменяющих почтовый ящик."""
    from pathlib import Path

    source = Path("app/services/imap_client.py").read_text(encoding="utf-8")
    for forbidden in ['"STORE"', '"COPY"', '"MOVE"', '"EXPUNGE"', '"APPEND"', ".store(", ".copy("]:
        assert forbidden not in source, f"Найдена изменяющая команда: {forbidden}"
    assert "readonly=True" in source
    assert "BODY.PEEK" in source


def test_config_forces_read_only():
    from app.core.config import Settings

    settings = Settings(read_only_mode=False)
    assert settings.read_only_mode is True
