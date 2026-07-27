"""HTML-страницы приложения."""

from __future__ import annotations

from datetime import date, datetime, timedelta

from fastapi import APIRouter, Depends, Query, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.core.config import settings
from app.core.constants import ALL_REQUEST_TYPES, REQUEST_TYPE_RU, STATUS_RU, ThreadStatus
from app.core.db import get_db
from app.models import (
    AnalysisResult,
    Attachment,
    CustomerRequest,
    MailAccount,
    MailFolder,
    ManualReview,
    Message,
    SyncRun,
    Thread,
)
from app.services.demo_data import expected_results
from app.services.jobs import jobs
from app.services.reports import (
    ThreadFilters,
    daily_report,
    dashboard_metrics,
    manager_statistics,
    query_threads,
)
from app.services.settings_service import get_all_settings
from app.services.thread_context import build_context
from app.services.working_time import WorkSchedule
from app.web.templating import templates

router = APIRouter()


def _schedule(db: Session) -> WorkSchedule:
    return WorkSchedule.from_settings(get_all_settings(db))


def _today(db: Session) -> date:
    schedule = _schedule(db)
    return datetime.now(schedule.tz).date()


def _parse_date(value: str | None, default: date) -> date:
    if not value:
        return default
    try:
        return date.fromisoformat(value)
    except ValueError:
        return default


def demo_state(db: Session) -> dict:
    """Сведения о демонстрационных данных: их наличие видно на каждой странице."""
    account = db.scalar(select(MailAccount).where(MailAccount.is_demo.is_(True)))
    if account is None:
        return {"demo_account_id": None, "demo_threads": 0, "real_threads": 0}
    demo_threads = db.scalar(
        select(func.count(Thread.id)).where(Thread.account_id == account.id)
    ) or 0
    real_threads = db.scalar(
        select(func.count(Thread.id)).where(Thread.account_id != account.id)
    ) or 0
    return {
        "demo_account_id": account.id,
        "demo_threads": demo_threads,
        "real_threads": real_threads,
    }


def _base_context(request: Request, db: Session) -> dict:
    # Предупреждения пересчитываются на каждый запрос: после настройки
    # через мастер они должны исчезать без перезапуска программы.
    warnings = settings.validate_startup()
    if not (get_all_settings(db).get("corporate.domains") or []):
        pass  # текст уже есть в validate_startup

    context = {
        "request": request,
        "read_only": settings.read_only_mode,
        "demo_mode": settings.demo_mode,
        "ai_enabled": bool(get_all_settings(db).get("ai.enabled")),
        "openai_configured": settings.openai_configured(),
        "imap_configured": settings.imap_configured(),
        "warnings": warnings,
        "jobs": jobs.snapshot(),
        "today": _today(db),
    }
    context.update(demo_state(db))
    return context


# ------------------------------------------------------------------
# Главная панель
# ------------------------------------------------------------------

@router.get("/", response_class=HTMLResponse)
def dashboard(
    request: Request,
    day: str | None = Query(None),
    db: Session = Depends(get_db),
) -> HTMLResponse:
    schedule = _schedule(db)
    target = _parse_date(day, _today(db))
    metrics = dashboard_metrics(db, target, schedule)

    filters = ThreadFilters(sort="priority")
    critical = query_threads(
        db, ThreadFilters(status=[ThreadStatus.CRITICAL], sort="waiting"), schedule
    )[:10]
    incomplete = query_threads(
        db, ThreadFilters(status=[ThreadStatus.INCOMPLETE], sort="waiting"), schedule
    )[:10]
    read_unanswered = query_threads(
        db, ThreadFilters(read_unanswered=True, sort="waiting"), schedule
    )[:10]

    context = _base_context(request, db)
    context.update(
        {
            "title": "Главная панель",
            "metrics": metrics,
            "target": target,
            "critical": critical,
            "incomplete": incomplete,
            "read_unanswered": read_unanswered,
            "prev_day": target - timedelta(days=1),
            "next_day": target + timedelta(days=1),
            "requests_map": _requests_map(db, critical + incomplete + read_unanswered),
        }
    )
    return templates.TemplateResponse(request, "dashboard.html", context)


def _requests_map(db: Session, threads: list[Thread]) -> dict[int, list[CustomerRequest]]:
    ids = [t.id for t in threads]
    if not ids:
        return {}
    rows = db.scalars(
        select(CustomerRequest).where(CustomerRequest.thread_id.in_(ids))
    ).all()
    result: dict[int, list[CustomerRequest]] = {}
    for row in rows:
        result.setdefault(row.thread_id, []).append(row)
    return result


# ------------------------------------------------------------------
# Список обращений
# ------------------------------------------------------------------

@router.get("/threads", response_class=HTMLResponse)
def thread_list(
    request: Request,
    date_from: str | None = None,
    date_to: str | None = None,
    status: list[str] = Query(default=[]),
    manager: str | None = None,
    client: str | None = None,
    request_type: str | None = None,
    has_reply: str | None = None,
    has_attachments: str | None = None,
    is_seen: str | None = None,
    needs_review: str | None = None,
    read_unanswered: str | None = None,
    search: str | None = None,
    sort: str = "priority",
    show_not_relevant: str | None = None,
    db: Session = Depends(get_db),
) -> HTMLResponse:
    schedule = _schedule(db)

    def tri(value: str | None) -> bool | None:
        if value in (None, "", "any"):
            return None
        return value in ("1", "yes", "true", "да")

    filters = ThreadFilters(
        date_from=_parse_date(date_from, None) if date_from else None,
        date_to=_parse_date(date_to, None) if date_to else None,
        status=[s for s in status if s],
        manager=manager or None,
        client=client or None,
        request_type=request_type or None,
        has_reply=tri(has_reply),
        has_attachments=tri(has_attachments),
        is_seen=tri(is_seen),
        needs_review=tri(needs_review),
        read_unanswered=bool(read_unanswered),
        search=search or None,
        sort=sort,
        include_not_relevant=bool(show_not_relevant),
    )
    threads = query_threads(db, filters, schedule)

    managers = [
        row[0]
        for row in db.execute(
            select(Thread.assigned_manager).where(Thread.assigned_manager.is_not(None)).distinct()
        ).all()
    ]

    context = _base_context(request, db)
    context.update(
        {
            "title": "Обращения",
            "threads": threads,
            "requests_map": _requests_map(db, threads),
            "filters": filters,
            "managers": sorted(managers),
            "statuses": [(s, STATUS_RU[s]) for s in STATUS_RU if s != ThreadStatus.NEW],
            "request_types": [(t, REQUEST_TYPE_RU[t]) for t in ALL_REQUEST_TYPES],
            "raw_query": dict(request.query_params),
        }
    )
    return templates.TemplateResponse(request, "threads.html", context)


# ------------------------------------------------------------------
# Карточка обращения
# ------------------------------------------------------------------

@router.get("/threads/{thread_id}", response_class=HTMLResponse)
def thread_detail(
    request: Request, thread_id: int, db: Session = Depends(get_db)
) -> HTMLResponse:
    thread = db.get(Thread, thread_id)
    if thread is None:
        return templates.TemplateResponse(
            request,
            "error.html",
            {"title": "Обращение не найдено", "message": "Такого обращения нет.", "code": 404},
            status_code=404,
        )

    messages = db.scalars(
        select(Message).where(Message.thread_id == thread_id).order_by(Message.sent_at, Message.id)
    ).all()
    attachments: dict[int, list[Attachment]] = {}
    for att in db.scalars(
        select(Attachment).where(Attachment.message_id.in_([m.id for m in messages] or [0]))
    ).all():
        attachments.setdefault(att.message_id, []).append(att)

    requests_ = db.scalars(
        select(CustomerRequest).where(CustomerRequest.thread_id == thread_id)
    ).all()
    analyses = db.scalars(
        select(AnalysisResult)
        .where(AnalysisResult.thread_id == thread_id)
        .order_by(AnalysisResult.id.desc())
        .limit(5)
    ).all()
    reviews = db.scalars(
        select(ManualReview)
        .where(ManualReview.thread_id == thread_id)
        .order_by(ManualReview.id.desc())
    ).all()

    context = _base_context(request, db)
    context.update(
        {
            "title": thread.subject or "Обращение",
            "thread": thread,
            "messages": messages,
            "attachments": attachments,
            "requests": requests_,
            "analyses": analyses,
            "reviews": reviews,
            "statuses": [(s, STATUS_RU[s]) for s in STATUS_RU if s != ThreadStatus.NEW],
            "request_types": [(t, REQUEST_TYPE_RU[t]) for t in ALL_REQUEST_TYPES],
        }
    )
    return templates.TemplateResponse(request, "thread_detail.html", context)


# ------------------------------------------------------------------
# Ежедневный отчёт
# ------------------------------------------------------------------

@router.get("/report", response_class=HTMLResponse)
def report_page(
    request: Request, day: str | None = None, db: Session = Depends(get_db)
) -> HTMLResponse:
    target = _parse_date(day, _today(db))
    report = daily_report(db, target)

    all_threads = []
    for group in report["sections"].values():
        all_threads.extend(group)

    context = _base_context(request, db)
    context.update(
        {
            "title": f"Отчёт за {target.strftime('%d.%m.%Y')}",
            "report": report,
            "target": target,
            "prev_day": target - timedelta(days=1),
            "next_day": target + timedelta(days=1),
            "requests_map": _requests_map(db, all_threads),
        }
    )
    return templates.TemplateResponse(request, "report.html", context)


# ------------------------------------------------------------------
# Менеджеры
# ------------------------------------------------------------------

@router.get("/managers", response_class=HTMLResponse)
def managers_page(
    request: Request,
    date_from: str | None = None,
    date_to: str | None = None,
    db: Session = Depends(get_db),
) -> HTMLResponse:
    today = _today(db)
    start = _parse_date(date_from, today - timedelta(days=7))
    end = _parse_date(date_to, today)
    rows = manager_statistics(db, start, end)

    context = _base_context(request, db)
    context.update(
        {
            "title": "Аналитика по менеджерам",
            "rows": rows,
            "date_from": start,
            "date_to": end,
        }
    )
    return templates.TemplateResponse(request, "managers.html", context)


# ------------------------------------------------------------------
# Настройки
# ------------------------------------------------------------------

@router.get("/settings", response_class=HTMLResponse)
def settings_page(request: Request, db: Session = Depends(get_db)) -> HTMLResponse:
    data = get_all_settings(db)
    accounts = db.scalars(select(MailAccount)).all()
    folders = db.scalars(select(MailFolder).order_by(MailFolder.folder_type)).all()
    runs = db.scalars(select(SyncRun).order_by(SyncRun.id.desc()).limit(10)).all()

    ai_calls = db.scalar(
        select(func.count(AnalysisResult.id)).where(AnalysisResult.source == "ai")
    ) or 0
    ai_tokens = db.scalar(
        select(func.coalesce(func.sum(AnalysisResult.token_usage), 0))
    ) or 0

    context = _base_context(request, db)
    context.update(
        {
            "title": "Настройки",
            "data": data,
            "accounts": accounts,
            "folders": folders,
            "runs": runs,
            "ai_calls": ai_calls,
            "ai_tokens": ai_tokens,
            "env": {
                "imap_host": settings.yandex_imap_host,
                "imap_port": settings.yandex_imap_port,
                "email": settings.yandex_email or "(не задан)",
                "password_set": bool(settings.yandex_app_password),
                "openai_key_set": bool(settings.openai_api_key),
                "openai_model": settings.openai_model,
                "database": str(settings.sqlite_file or settings.database_url),
                "attachments_dir": str(settings.attachments_path),
            },
        }
    )
    return templates.TemplateResponse(request, "settings.html", context)


# ------------------------------------------------------------------
# Демонстрационный режим
# ------------------------------------------------------------------

@router.get("/demo", response_class=HTMLResponse)
def demo_page(request: Request, db: Session = Depends(get_db)) -> HTMLResponse:
    scenarios = expected_results()
    demo_account = db.scalar(select(MailAccount).where(MailAccount.is_demo.is_(True)))
    actual: dict[str, dict] = {}
    if demo_account is not None:
        threads = db.scalars(
            select(Thread).where(Thread.account_id == demo_account.id)
        ).all()
        by_client = {t.client_email: t for t in threads}
        from app.services.demo_data import SCENARIOS

        for scenario in SCENARIOS:
            thread = by_client.get(scenario.messages[0].sender)
            if thread is None:
                continue
            reqs = db.scalars(
                select(CustomerRequest).where(CustomerRequest.thread_id == thread.id)
            ).all()
            actual[scenario.code] = {
                "thread": thread,
                "status": thread.current_status,
                "match": thread.current_status == scenario.expected_status,
                "requests": {r.request_type: r.fulfilled for r in reqs},
            }

    context = _base_context(request, db)
    context.update(
        {
            "title": "Демонстрационный режим",
            "scenarios": scenarios,
            "actual": actual,
            "loaded": demo_account is not None,
        }
    )
    return templates.TemplateResponse(request, "demo.html", context)


@router.get("/setup", response_class=HTMLResponse)
def setup_page(request: Request, db: Session = Depends(get_db)) -> HTMLResponse:
    """Мастер первоначальной настройки: подключение почты без правки файлов."""
    from app.services.demo_data import DEMO_DOMAIN

    data = get_all_settings(db)
    # Домен демо-ящика не является настройкой пользователя и в мастере не показывается
    domains = [d for d in (data.get("corporate.domains") or []) if d and d != DEMO_DOMAIN]
    context = _base_context(request, db)
    context.update(
        {
            "title": "Подключение почты",
            "state": {
                "email": settings.yandex_email,
                "email_set": bool(settings.yandex_email),
                "password_set": bool(settings.yandex_app_password),
                "openai_set": bool(settings.openai_api_key),
                "domains": domains,
                "domains_set": bool(domains),
                "has_real_data": context["real_threads"] > 0,
            },
        }
    )
    return templates.TemplateResponse(request, "setup.html", context)


@router.get("/guide", response_class=HTMLResponse)
def guide_page(request: Request, db: Session = Depends(get_db)) -> HTMLResponse:
    context = _base_context(request, db)
    context["title"] = "Инструкция: работа с реальной почтой"
    return templates.TemplateResponse(request, "guide.html", context)


@router.get("/limitations", response_class=HTMLResponse)
def limitations_page(request: Request, db: Session = Depends(get_db)) -> HTMLResponse:
    context = _base_context(request, db)
    context["title"] = "Ограничения первой версии"
    return templates.TemplateResponse(request, "limitations.html", context)


@router.get("/favicon.ico", include_in_schema=False)
def favicon() -> RedirectResponse:
    return RedirectResponse("/static/favicon.svg")
