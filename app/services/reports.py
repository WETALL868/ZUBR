"""Аналитика: показатели панели, ежедневный отчёт, статистика по менеджерам."""

from __future__ import annotations

from collections import Counter, defaultdict
from dataclasses import dataclass, field
from datetime import date, datetime, time, timedelta

from sqlalchemy import func, or_, select
from sqlalchemy.orm import Session

from app.core.constants import (
    REQUEST_TYPE_RU,
    STATUS_PRIORITY,
    STATUS_RU,
    Direction,
    ThreadStatus,
)
from app.models import CustomerRequest, MailAccount, Message, SyncRun, Thread
from app.services.settings_service import get_all_settings, manager_names
from app.services.working_time import WorkSchedule, format_duration


# ------------------------------------------------------------------
# Период
# ------------------------------------------------------------------

def day_bounds_utc(target: date, schedule: WorkSchedule) -> tuple[datetime, datetime]:
    """Границы локального дня в naive UTC."""
    tz = schedule.tz
    start_local = datetime.combine(target, time.min, tzinfo=tz)
    end_local = start_local + timedelta(days=1)
    start_utc = datetime(*start_local.utctimetuple()[:6])
    end_utc = datetime(*end_local.utctimetuple()[:6])
    return start_utc, end_utc


def period_bounds_utc(
    date_from: date, date_to: date, schedule: WorkSchedule
) -> tuple[datetime, datetime]:
    start, _ = day_bounds_utc(date_from, schedule)
    _, end = day_bounds_utc(date_to, schedule)
    return start, end


# ------------------------------------------------------------------
# Фильтры списка обращений
# ------------------------------------------------------------------

@dataclass
class ThreadFilters:
    date_from: date | None = None
    date_to: date | None = None
    status: list[str] = field(default_factory=list)
    manager: str | None = None
    client: str | None = None
    request_type: str | None = None
    has_reply: bool | None = None
    has_attachments: bool | None = None
    is_seen: bool | None = None
    needs_review: bool | None = None
    read_unanswered: bool | None = None
    search: str | None = None
    sort: str = "priority"
    include_not_relevant: bool = False


def search_variants(term: str) -> list[str]:
    """Варианты поискового запроса: «счет» и «счёт» должны находиться одинаково."""
    base = (term or "").strip()
    if not base:
        return []
    variants = {base, base.replace("ё", "е"), base.replace("е", "ё")}
    return [f"%{v}%" for v in variants]


def query_threads(db: Session, filters: ThreadFilters, schedule: WorkSchedule) -> list[Thread]:
    query = select(Thread)

    if filters.date_from and filters.date_to:
        start, end = period_bounds_utc(filters.date_from, filters.date_to, schedule)
        query = query.where(Thread.last_message_at >= start, Thread.last_message_at < end)
    elif filters.date_from:
        start, _ = day_bounds_utc(filters.date_from, schedule)
        query = query.where(Thread.last_message_at >= start)

    if filters.status:
        query = query.where(Thread.current_status.in_(filters.status))
    elif not filters.include_not_relevant:
        query = query.where(Thread.current_status != ThreadStatus.NOT_RELEVANT)

    if filters.manager:
        query = query.where(Thread.assigned_manager == filters.manager)
    if filters.client:
        query = query.where(Thread.client_email.ilike(f"%{filters.client}%"))
    if filters.has_reply is not None:
        query = query.where(Thread.has_reply.is_(filters.has_reply))
    if filters.has_attachments is not None:
        query = query.where(Thread.has_attachments.is_(filters.has_attachments))
    if filters.needs_review is not None:
        query = query.where(Thread.needs_review.is_(filters.needs_review))
    if filters.read_unanswered:
        query = query.where(Thread.is_read_unanswered.is_(True))
    if filters.is_seen is False:
        query = query.where(Thread.has_unread_incoming.is_(True))
    if filters.is_seen is True:
        query = query.where(Thread.has_unread_incoming.is_(False))

    if filters.request_type:
        query = query.where(
            Thread.id.in_(
                select(CustomerRequest.thread_id).where(
                    CustomerRequest.request_type == filters.request_type
                )
            )
        )

    if filters.search:
        conditions = []
        for term in search_variants(filters.search):
            conditions.extend(
                [
                    Thread.subject.ilike(term),
                    Thread.normalized_subject.ilike(term),
                    Thread.client_email.ilike(term),
                    Thread.client_name.ilike(term),
                    Thread.company_name.ilike(term),
                    Thread.summary.ilike(term),
                    Thread.id.in_(
                        select(Message.thread_id).where(
                            or_(Message.body_text.ilike(term), Message.subject.ilike(term))
                        )
                    ),
                ]
            )
        query = query.where(or_(*conditions))

    threads = list(db.scalars(query).all())
    return sort_threads(threads, filters.sort)


def sort_threads(threads: list[Thread], sort: str) -> list[Thread]:
    if sort == "waiting":
        return sorted(threads, key=lambda t: t.waiting_seconds or 0, reverse=True)
    if sort == "date":
        return sorted(threads, key=lambda t: t.last_message_at or datetime.min, reverse=True)
    if sort == "client":
        return sorted(threads, key=lambda t: (t.client_email or "").lower())
    if sort == "manager":
        return sorted(threads, key=lambda t: (t.assigned_manager or "яяя").lower())
    # priority: сначала критические, внутри — по времени ожидания
    return sorted(
        threads,
        key=lambda t: (
            STATUS_PRIORITY.get(t.current_status, 9),
            -(t.waiting_seconds or 0),
        ),
    )


# ------------------------------------------------------------------
# Показатели панели
# ------------------------------------------------------------------

def dashboard_metrics(db: Session, target: date, schedule: WorkSchedule) -> dict:
    start, end = day_bounds_utc(target, schedule)

    incoming_today = db.scalar(
        select(func.count(Message.id)).where(
            Message.direction == Direction.INCOMING,
            Message.sent_at >= start,
            Message.sent_at < end,
        )
    ) or 0
    unread_incoming = db.scalar(
        select(func.count(Message.id)).where(
            Message.direction == Direction.INCOMING,
            Message.is_seen.is_(False),
            Message.sent_at >= start,
            Message.sent_at < end,
        )
    ) or 0
    new_threads = db.scalar(
        select(func.count(Thread.id)).where(
            Thread.first_message_at >= start, Thread.first_message_at < end
        )
    ) or 0

    active = list(
        db.scalars(
            select(Thread).where(Thread.last_message_at >= start, Thread.last_message_at < end)
        ).all()
    )
    status_counts = Counter(t.current_status for t in active)

    # Обращения без ответа — независимо от дня последнего письма
    all_open = list(
        db.scalars(
            select(Thread).where(
                Thread.current_status.in_(
                    [ThreadStatus.CRITICAL, ThreadStatus.INCOMPLETE, ThreadStatus.NEEDS_REVIEW]
                )
            )
        ).all()
    )

    read_unanswered = [t for t in all_open if t.is_read_unanswered]
    unread_unanswered = [t for t in all_open if t.has_unread_incoming]

    # Открытые обращения независимо от даты последнего письма:
    # главная панель должна показывать текущее состояние, а не только выбранный день.
    waiting_all = db.scalar(
        select(func.count(Thread.id)).where(
            Thread.current_status == ThreadStatus.WAITING_FOR_CLIENT
        )
    ) or 0
    needs_review_all = sum(
        1 for t in all_open if t.current_status == ThreadStatus.NEEDS_REVIEW
    )

    last_sync = db.scalar(select(func.max(MailAccount.last_sync_at)))
    last_run = db.scalar(select(SyncRun).order_by(SyncRun.id.desc()).limit(1))

    return {
        "date": target,
        "incoming_messages": incoming_today,
        "unread_messages": unread_incoming,
        "new_threads": new_threads,
        "active_threads": len(active),
        "completed": status_counts.get(ThreadStatus.COMPLETED, 0),
        "incomplete": status_counts.get(ThreadStatus.INCOMPLETE, 0),
        "critical": status_counts.get(ThreadStatus.CRITICAL, 0),
        "waiting_client": status_counts.get(ThreadStatus.WAITING_FOR_CLIENT, 0),
        "needs_review": status_counts.get(ThreadStatus.NEEDS_REVIEW, 0),
        "not_relevant": status_counts.get(ThreadStatus.NOT_RELEVANT, 0),
        "read_unanswered": len(read_unanswered),
        "unread_unanswered": len(unread_unanswered),
        "total_critical_all": sum(
            1 for t in all_open if t.current_status == ThreadStatus.CRITICAL
        ),
        "total_incomplete_all": sum(
            1 for t in all_open if t.current_status == ThreadStatus.INCOMPLETE
        ),
        "total_waiting_all": waiting_all,
        "total_needs_review_all": needs_review_all,
        "sla_exceeded": sum(1 for t in all_open if t.sla_exceeded),
        "last_sync_at": last_sync,
        "last_sync_status": last_run.status if last_run else None,
        "last_sync_error": last_run.error if last_run else None,
    }


# ------------------------------------------------------------------
# Ежедневный отчёт
# ------------------------------------------------------------------

def daily_report(db: Session, target: date) -> dict:
    settings_data = get_all_settings(db)
    schedule = WorkSchedule.from_settings(settings_data)
    start, end = day_bounds_utc(target, schedule)

    metrics = dashboard_metrics(db, target, schedule)

    active = list(
        db.scalars(
            select(Thread)
            .where(Thread.last_message_at >= start, Thread.last_message_at < end)
            .order_by(Thread.last_message_at.desc())
        ).all()
    )
    relevant = [t for t in active if t.current_status != ThreadStatus.NOT_RELEVANT]

    def by_status(status: str) -> list[Thread]:
        return sort_threads([t for t in relevant if t.current_status == status], "waiting")

    # Ответ есть, но запрос не выполнен
    answered_incomplete = [
        t for t in relevant if t.current_status == ThreadStatus.INCOMPLETE and t.has_reply
    ]
    partially = []
    for thread in answered_incomplete:
        reqs = db.scalars(
            select(CustomerRequest).where(CustomerRequest.thread_id == thread.id)
        ).all()
        if reqs and any(r.fulfilled for r in reqs) and any(not r.fulfilled for r in reqs):
            partially.append(thread)

    # Запросы по категориям
    request_rows = db.execute(
        select(CustomerRequest.request_type, CustomerRequest.fulfilled, func.count(CustomerRequest.id))
        .join(Thread, Thread.id == CustomerRequest.thread_id)
        .where(Thread.last_message_at >= start, Thread.last_message_at < end)
        .group_by(CustomerRequest.request_type, CustomerRequest.fulfilled)
    ).all()
    categories: dict[str, dict] = defaultdict(lambda: {"total": 0, "fulfilled": 0, "unfulfilled": 0})
    for rtype, fulfilled, count in request_rows:
        entry = categories[rtype]
        entry["total"] += count
        if fulfilled:
            entry["fulfilled"] += count
        else:
            entry["unfulfilled"] += count
    category_list = [
        {
            "type": rtype,
            "label": REQUEST_TYPE_RU.get(rtype, rtype),
            **values,
        }
        for rtype, values in sorted(categories.items(), key=lambda kv: -kv[1]["total"])
    ]

    # Повторные обращения
    repeats = [t for t in relevant if t.is_repeat_request]

    # Самые долгие ожидания
    longest = sorted(
        [t for t in relevant if (t.waiting_seconds or 0) > 0],
        key=lambda t: t.waiting_seconds or 0,
        reverse=True,
    )[:15]

    return {
        "date": target,
        "metrics": metrics,
        "sections": {
            "critical": by_status(ThreadStatus.CRITICAL),
            "read_unanswered": sort_threads(
                [t for t in relevant if t.is_read_unanswered], "waiting"
            ),
            "answered_incomplete": sort_threads(answered_incomplete, "waiting"),
            "partially_done": partially,
            "waiting_client": by_status(ThreadStatus.WAITING_FOR_CLIENT),
            "completed": by_status(ThreadStatus.COMPLETED),
            "needs_review": by_status(ThreadStatus.NEEDS_REVIEW),
            "repeats": repeats,
            "longest_waiting": longest,
        },
        "categories": category_list,
        "managers": manager_statistics(db, target, target),
    }


# ------------------------------------------------------------------
# Статистика по менеджерам
# ------------------------------------------------------------------

def manager_statistics(db: Session, date_from: date, date_to: date) -> list[dict]:
    settings_data = get_all_settings(db)
    schedule = WorkSchedule.from_settings(settings_data)
    start, end = period_bounds_utc(date_from, date_to, schedule)
    names = manager_names(db)

    threads = list(
        db.scalars(
            select(Thread).where(
                Thread.last_message_at >= start,
                Thread.last_message_at < end,
                Thread.current_status != ThreadStatus.NOT_RELEVANT,
            )
        ).all()
    )

    grouped: dict[str, list[Thread]] = defaultdict(list)
    for thread in threads:
        key = thread.assigned_manager or "(не определён)"
        grouped[key].append(thread)

    from app.models import ManualReview

    rows: list[dict] = []
    for email, items in grouped.items():
        first_times = [t.first_response_business_seconds for t in items if t.first_response_business_seconds is not None]
        close_times = [
            (t.last_message_at - t.first_message_at).total_seconds()
            for t in items
            if t.current_status == ThreadStatus.COMPLETED and t.first_message_at and t.last_message_at
        ]
        completed = sum(1 for t in items if t.current_status == ThreadStatus.COMPLETED)
        incomplete = sum(1 for t in items if t.current_status == ThreadStatus.INCOMPLETE)
        no_reply = sum(1 for t in items if not t.has_reply)
        manual_fixes = db.scalar(
            select(func.count(ManualReview.id)).where(
                ManualReview.thread_id.in_([t.id for t in items] or [0])
            )
        ) or 0
        rows.append(
            {
                "email": email,
                "name": names.get(email, email),
                "threads": len(items),
                "processed": sum(1 for t in items if t.has_reply),
                "completed": completed,
                "incomplete": incomplete,
                "critical": sum(1 for t in items if t.current_status == ThreadStatus.CRITICAL),
                "waiting_client": sum(
                    1 for t in items if t.current_status == ThreadStatus.WAITING_FOR_CLIENT
                ),
                "no_reply": no_reply,
                "avg_first_response": int(sum(first_times) / len(first_times)) if first_times else None,
                "avg_first_response_text": format_duration(
                    int(sum(first_times) / len(first_times)) if first_times else None
                ),
                "avg_close_time": int(sum(close_times) / len(close_times)) if close_times else None,
                "avg_close_time_text": format_duration(
                    int(sum(close_times) / len(close_times)) if close_times else None
                ),
                "manual_fixes": manual_fixes,
                "completion_rate": round(completed / len(items) * 100) if items else 0,
            }
        )
    return sorted(rows, key=lambda r: -r["threads"])


def status_label(status: str) -> str:
    return STATUS_RU.get(status, status)
