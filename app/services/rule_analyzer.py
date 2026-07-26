"""Технический анализ цепочек без участия ИИ (этап 3).

Определяет: есть ли ответ, кто написал последним, есть ли вложения,
сколько прошло времени (фактического и рабочего), прочитано ли письмо
без ответа, нарушен ли срок реакции.
"""

from __future__ import annotations

from datetime import datetime, timezone

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.constants import Direction, ThreadStatus
from app.models import Message, Thread
from app.services.working_time import (
    WorkSchedule,
    business_seconds,
    elapsed_seconds,
    sla_hours_for,
)


def now_utc() -> datetime:
    return datetime.now(timezone.utc).replace(tzinfo=None)


def compute_technical(
    db: Session,
    thread: Thread,
    schedule: WorkSchedule,
    settings_data: dict,
    now: datetime | None = None,
) -> Thread:
    """Заполняет технические поля цепочки. Не использует ИИ."""
    now = now or now_utc()
    messages = db.scalars(
        select(Message).where(Message.thread_id == thread.id).order_by(Message.sent_at, Message.id)
    ).all()
    if not messages:
        return thread

    incoming = [m for m in messages if m.direction == Direction.INCOMING]
    outgoing = [m for m in messages if m.direction == Direction.OUTGOING]

    thread.has_reply = bool(outgoing)
    last = messages[-1]
    thread.last_message_direction = last.direction

    # --- есть ли ответ на последнее письмо клиента ---
    last_incoming = incoming[-1] if incoming else None
    reply_after_last_incoming = None
    if last_incoming and last_incoming.sent_at:
        later = [
            m for m in outgoing
            if m.sent_at and m.sent_at >= last_incoming.sent_at
        ]
        reply_after_last_incoming = min((m.sent_at for m in later), default=None)

    unanswered = bool(last_incoming) and reply_after_last_incoming is None

    # --- прочитано, но не отвечено ---
    thread.is_read_unanswered = bool(unanswered and last_incoming and last_incoming.is_seen)
    thread.has_unread_incoming = bool(unanswered and last_incoming and not last_incoming.is_seen)

    # --- время ожидания ---
    if unanswered and last_incoming and last_incoming.sent_at:
        thread.waiting_seconds = elapsed_seconds(last_incoming.sent_at, now)
        thread.waiting_business_seconds = business_seconds(last_incoming.sent_at, now, schedule)
    else:
        thread.waiting_seconds = 0
        thread.waiting_business_seconds = 0

    # --- время первого ответа ---
    if incoming and incoming[0].sent_at and thread.first_response_at:
        thread.first_response_seconds = elapsed_seconds(
            incoming[0].sent_at, thread.first_response_at
        )
        thread.first_response_business_seconds = business_seconds(
            incoming[0].sent_at, thread.first_response_at, schedule
        )
    else:
        thread.first_response_seconds = None
        thread.first_response_business_seconds = None

    # --- срок реакции ---
    request_types = [r.request_type for r in thread.requests] if thread.requests else []
    sla = sla_hours_for(request_types, thread.client_email, settings_data)
    thread.sla_hours = sla
    count_weekends = bool(settings_data.get("sla.count_weekends", False))
    elapsed_for_sla = thread.waiting_seconds if count_weekends else thread.waiting_business_seconds
    thread.sla_exceeded = bool(unanswered and elapsed_for_sla > sla * 3600)

    # --- технический статус ---
    thread.tech_status = _tech_status(thread, unanswered)
    return thread


def _tech_status(thread: Thread, unanswered: bool) -> str:
    if thread.is_automated:
        return ThreadStatus.NOT_RELEVANT
    if unanswered:
        return ThreadStatus.CRITICAL
    if thread.last_message_direction == Direction.OUTGOING:
        return ThreadStatus.WAITING_FOR_CLIENT
    if not thread.has_reply:
        return ThreadStatus.CRITICAL
    return ThreadStatus.NEW


def technical_summary(thread: Thread) -> dict:
    """Технические признаки для отображения и для передачи в AI как факты."""
    return {
        "есть ответ менеджера": "да" if thread.has_reply else "нет",
        "последнее письмо": {
            Direction.INCOMING: "от клиента",
            Direction.OUTGOING: "от менеджера",
            Direction.INTERNAL: "внутреннее",
        }.get(thread.last_message_direction, "неизвестно"),
        "вложения в ответах менеджера": "есть" if thread.has_outgoing_attachments else "нет",
        "письмо клиента отмечено прочитанным": (
            "да" if thread.is_read_unanswered else ("нет" if thread.has_unread_incoming else "—")
        ),
        "повторное обращение клиента": "да" if thread.is_repeat_request else "нет",
        "писем в цепочке": thread.message_count,
    }


def facts_for_ai(thread: Thread) -> str:
    """Метаданные, которые модель не должна выдумывать."""
    lines = [f"- {k}: {v}" for k, v in technical_summary(thread).items()]
    lines.append(
        "- точное время прочтения письма через IMAP недоступно, использовать его нельзя"
    )
    return "\n".join(lines)
