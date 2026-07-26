"""Сквозные тесты конвейера: демо-данные, статусы, дубликаты, ручные правки."""

from __future__ import annotations

from datetime import datetime, timedelta

import pytest
from sqlalchemy import select

from app.core.constants import RequestType, ThreadStatus
from app.models import CustomerRequest, MailAccount, Message, Thread
from app.services.analysis_service import analyze_threads
from app.services.demo_data import SCENARIOS, load_demo_data
from app.services.settings_service import get_all_settings
from app.services.threading_service import recompute_thread


@pytest.fixture
def loaded(db):
    load_demo_data(db)
    analyze_threads(db, force=True, use_ai=False)
    return db


def thread_for(db, scenario) -> Thread:
    sender = scenario.messages[0].sender
    return db.scalar(select(Thread).where(Thread.client_email == sender))


# ------------------------------------------------------------------
# Демонстрационные сценарии
# ------------------------------------------------------------------

@pytest.mark.parametrize("scenario", SCENARIOS, ids=[s.code for s in SCENARIOS])
def test_demo_scenario_status(loaded, scenario):
    thread = thread_for(loaded, scenario)
    assert thread is not None, f"Цепочка сценария {scenario.code} не создана"
    assert thread.current_status == scenario.expected_status, (
        f"Сценарий {scenario.code} «{scenario.title}»: "
        f"ожидался {scenario.expected_status}, получен {thread.current_status}. "
        f"Причина: {thread.status_reason}"
    )


@pytest.mark.parametrize(
    "scenario",
    [s for s in SCENARIOS if s.expected_requests],
    ids=[s.code for s in SCENARIOS if s.expected_requests],
)
def test_demo_scenario_requests(loaded, scenario):
    thread = thread_for(loaded, scenario)
    rows = loaded.scalars(
        select(CustomerRequest).where(CustomerRequest.thread_id == thread.id)
    ).all()
    actual = {r.request_type: r.fulfilled for r in rows}
    for rtype, expected in scenario.expected_requests.items():
        assert rtype in actual, (
            f"Сценарий {scenario.code}: запрос «{rtype}» не распознан (найдено: {actual})"
        )
        assert actual[rtype] is expected, (
            f"Сценарий {scenario.code}: «{rtype}» ожидался "
            f"{'выполненным' if expected else 'невыполненным'}"
        )


def test_invoice_not_closed_by_price_alone(loaded):
    """Ключевое правило: сообщение цены не закрывает запрос счёта."""
    scenario = next(s for s in SCENARIOS if s.code == "02")
    thread = thread_for(loaded, scenario)
    invoice = loaded.scalar(
        select(CustomerRequest).where(
            CustomerRequest.thread_id == thread.id,
            CustomerRequest.request_type == RequestType.INVOICE,
        )
    )
    assert invoice is not None
    assert invoice.fulfilled is False
    assert thread.current_status == ThreadStatus.INCOMPLETE


def test_evidence_present_for_fulfilled(loaded):
    """Каждая выполненная задача подтверждена цитатой или вложением."""
    rows = loaded.scalars(
        select(CustomerRequest).where(CustomerRequest.fulfilled.is_(True))
    ).all()
    assert rows
    for row in rows:
        assert row.evidence and row.evidence != "[]", (
            f"Задача {row.request_type} (цепочка {row.thread_id}) отмечена выполненной без подтверждения"
        )


def test_status_reason_always_present(loaded):
    threads = loaded.scalars(select(Thread)).all()
    for thread in threads:
        assert thread.status_reason, f"Цепочка {thread.id} без объяснения статуса"
        assert thread.next_action, f"Цепочка {thread.id} без рекомендуемого действия"


# ------------------------------------------------------------------
# Группировка
# ------------------------------------------------------------------

def test_multiple_re_prefixes_stay_one_thread(loaded):
    scenario = next(s for s in SCENARIOS if s.code == "19")
    thread = thread_for(loaded, scenario)
    assert thread.message_count == 4


def test_thread_started_before_target_day(loaded):
    scenario = next(s for s in SCENARIOS if s.code == "18")
    thread = thread_for(loaded, scenario)
    assert thread.message_count == 4
    assert thread.first_message_at < datetime.utcnow() - timedelta(days=5)


def test_ambiguous_thread_marked(loaded):
    scenario = next(s for s in SCENARIOS if s.code == "20")
    thread = thread_for(loaded, scenario)
    assert thread.needs_review is True
    assert thread.grouping_confidence < 1.0


def test_manager_from_another_address(loaded):
    """Ответ с другого корпоративного адреса засчитывается как ответ компании."""
    scenario = next(s for s in SCENARIOS if s.code == "17")
    thread = thread_for(loaded, scenario)
    assert thread.has_reply is True
    assert thread.assigned_manager == "sidorov@zubr-demo.ru"


def test_repeat_request_detected(loaded):
    scenario = next(s for s in SCENARIOS if s.code == "14")
    thread = thread_for(loaded, scenario)
    assert thread.is_repeat_request is True
    assert thread.current_status == ThreadStatus.CRITICAL


def test_read_unanswered_flags(loaded):
    read = next(s for s in SCENARIOS if s.code == "03")
    unread = next(s for s in SCENARIOS if s.code == "04")
    assert thread_for(loaded, read).is_read_unanswered is True
    assert thread_for(loaded, unread).has_unread_incoming is True
    assert thread_for(loaded, unread).is_read_unanswered is False


# ------------------------------------------------------------------
# Повторная загрузка и кэш анализа
# ------------------------------------------------------------------

def test_reload_does_not_duplicate_messages(db):
    load_demo_data(db)
    first = db.scalar(select(Message).order_by(Message.id))
    count_before = len(db.scalars(select(Message)).all())
    load_demo_data(db)  # повторная загрузка
    count_after = len(db.scalars(select(Message)).all())
    assert count_after == count_before
    assert first is not None


def test_repeated_analysis_is_skipped(loaded):
    """Одна и та же цепочка не анализируется повторно без изменений."""
    stats = analyze_threads(loaded, use_ai=False)
    assert stats.skipped_unchanged == stats.threads_total
    assert stats.analyzed == 0


def test_forced_analysis_runs_again(loaded):
    stats = analyze_threads(loaded, force=True, use_ai=False)
    assert stats.analyzed == stats.threads_total


# ------------------------------------------------------------------
# Ручные корректировки
# ------------------------------------------------------------------

def test_manual_status_is_preserved(loaded):
    thread = loaded.scalar(select(Thread))
    thread.current_status = ThreadStatus.COMPLETED
    thread.manual_override = True
    loaded.commit()

    analyze_threads(loaded, thread_ids=[thread.id], use_ai=False)
    loaded.refresh(thread)
    assert thread.current_status == ThreadStatus.COMPLETED
    assert thread.manual_override is True


def test_manual_request_override_survives_reanalysis(loaded):
    row = loaded.scalar(select(CustomerRequest).where(CustomerRequest.fulfilled.is_(False)))
    row.fulfilled = True
    row.manual_override = True
    thread_id, rtype = row.thread_id, row.request_type
    loaded.commit()

    analyze_threads(loaded, thread_ids=[thread_id], force=True, use_ai=False)
    refreshed = loaded.scalar(
        select(CustomerRequest).where(
            CustomerRequest.thread_id == thread_id,
            CustomerRequest.request_type == rtype,
        )
    )
    assert refreshed.fulfilled is True
    assert refreshed.manual_override is True


# ------------------------------------------------------------------
# Прочее
# ------------------------------------------------------------------

def test_demo_account_is_separate(loaded):
    account = loaded.scalar(select(MailAccount).where(MailAccount.is_demo.is_(True)))
    assert account is not None
    assert account.email == "demo@example.local"


def test_recompute_thread_is_idempotent(loaded):
    settings_data = get_all_settings(loaded)
    domains = set(settings_data["corporate.domains"])
    emails = set(settings_data["corporate.emails"])
    thread = loaded.scalar(select(Thread))
    before = (thread.message_count, thread.incoming_count, thread.outgoing_count)
    recompute_thread(loaded, thread, domains, emails)
    recompute_thread(loaded, thread, domains, emails)
    assert (thread.message_count, thread.incoming_count, thread.outgoing_count) == before
