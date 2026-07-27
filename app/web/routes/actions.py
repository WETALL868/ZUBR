"""Действия: синхронизация, анализ, ручные корректировки, экспорт.

Ни одно действие не изменяет почтовый ящик.
"""

from __future__ import annotations

import json
from datetime import date, datetime, timedelta
from urllib.parse import quote

from fastapi import APIRouter, Depends, Form, Query, Request
from fastapi.responses import HTMLResponse, JSONResponse, RedirectResponse, StreamingResponse
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import settings
from app.core.db import get_db, session_scope
from app.core.constants import STATUS_RU
from app.core.logging_setup import get_logger
from app.models import CustomerRequest, MailFolder, ManualReview, Thread
from app.services.analysis_service import analyze_threads, recompute_all_technical
from app.services.demo_data import load_demo_data
from app.services.export_excel import build_workbook
from app.services.imap_client import ImapError, test_connection
from app.services.jobs import jobs
from app.services.reports import daily_report
from app.services.settings_service import get_all_settings, update_settings
from app.services.sync_service import sync_account
from app.services.working_time import WorkSchedule
from app.web.templating import set_schedule, templates

logger = get_logger(__name__)
router = APIRouter()


def _back(request: Request, fallback: str = "/") -> RedirectResponse:
    referer = request.headers.get("referer") or fallback
    return RedirectResponse(referer, status_code=303)


def _flash(url: str, message: str, kind: str = "ok") -> RedirectResponse:
    sep = "&" if "?" in url else "?"
    return RedirectResponse(f"{url}{sep}msg={quote(message)}&kind={kind}", status_code=303)


# ------------------------------------------------------------------
# Синхронизация почты
# ------------------------------------------------------------------

@router.post("/actions/sync")
def start_sync(
    request: Request,
    days: int = Form(7),
    run_analysis: str | None = Form(None),
) -> RedirectResponse:
    if settings.demo_mode:
        return _flash("/settings", "Демонстрационный режим: синхронизация с почтой отключена.", "warn")
    if not settings.imap_configured():
        return _flash(
            "/settings",
            "Подключение не настроено: заполните YANDEX_EMAIL и YANDEX_APP_PASSWORD в файле .env.",
            "error",
        )

    analyze_after = bool(run_analysis)

    def _work() -> dict:
        with session_scope() as db:
            stats = sync_account(db, days=days)
            result = stats.as_dict()
        if analyze_after:
            with session_scope() as db:
                analysis = analyze_threads(db, date_from=datetime.utcnow() - timedelta(days=days))
                result["analysis"] = analysis.as_dict()
        result["message"] = (
            f"Загружено новых писем: {result['messages_new']}, "
            f"ошибок: {result['messages_failed']}"
        )
        return result

    started = jobs.run("sync", _work, "Синхронизация почты…")
    if not started:
        return _flash("/settings", "Синхронизация уже выполняется.", "warn")
    return _flash("/settings", "Синхронизация запущена. Обновите страницу через минуту.", "ok")


@router.post("/actions/test-connection")
def check_connection() -> RedirectResponse:
    ok, message = test_connection(
        settings.yandex_imap_host,
        settings.yandex_imap_port,
        settings.yandex_email,
        settings.yandex_app_password,
    )
    return _flash("/settings", message, "ok" if ok else "error")


# ------------------------------------------------------------------
# Анализ
# ------------------------------------------------------------------

@router.post("/actions/analyze")
def start_analysis(
    request: Request,
    force: str | None = Form(None),
    days: int = Form(30),
    use_ai: str | None = Form(None),
) -> RedirectResponse:
    force_flag = bool(force)
    ai_flag: bool | None = None if use_ai is None else bool(use_ai)
    since = datetime.utcnow() - timedelta(days=max(1, days))

    def _work() -> dict:
        with session_scope() as db:
            stats = analyze_threads(db, force=force_flag, date_from=since, use_ai=ai_flag)
        result = stats.as_dict()
        result["message"] = (
            f"Проанализировано цепочек: {result['analyzed']} "
            f"(AI-вызовов: {result['ai_calls']}, токенов: {result['tokens']})"
        )
        return result

    started = jobs.run("analysis", _work, "Анализ переписки…")
    if not started:
        return _flash("/", "Анализ уже выполняется.", "warn")
    return _flash("/", "Анализ запущен. Обновите страницу через несколько секунд.", "ok")


@router.post("/threads/{thread_id}/reanalyze")
def reanalyze_thread(thread_id: int, request: Request, db: Session = Depends(get_db)) -> RedirectResponse:
    thread = db.get(Thread, thread_id)
    if thread is None:
        return _flash("/threads", "Обращение не найдено.", "error")
    thread.manual_override = False
    thread.analyzed_content_hash = None
    db.commit()
    stats = analyze_threads(db, thread_ids=[thread_id], force=True)
    return _flash(
        f"/threads/{thread_id}",
        "Обращение проанализировано повторно."
        if stats.analyzed
        else f"Не удалось выполнить анализ: {'; '.join(stats.errors) or 'неизвестная ошибка'}",
        "ok" if stats.analyzed else "error",
    )


# ------------------------------------------------------------------
# Ручные корректировки
# ------------------------------------------------------------------

@router.post("/threads/{thread_id}/status")
def change_status(
    thread_id: int,
    status: str = Form(...),
    comment: str = Form(""),
    assigned_manager: str = Form(""),
    db: Session = Depends(get_db),
) -> RedirectResponse:
    thread = db.get(Thread, thread_id)
    if thread is None:
        return _flash("/threads", "Обращение не найдено.", "error")
    if status not in STATUS_RU:
        return _flash(f"/threads/{thread_id}", "Неизвестный статус.", "error")

    db.add(
        ManualReview(
            thread_id=thread_id,
            previous_status=thread.current_status,
            new_status=status,
            field="current_status",
            comment=comment or None,
        )
    )
    thread.current_status = status
    thread.manual_override = True
    thread.needs_review = False
    thread.analysis_source = "manual"
    if assigned_manager.strip():
        thread.assigned_manager = assigned_manager.strip().lower()
    if comment.strip():
        thread.status_reason = (
            f"{thread.status_reason or ''}\n[Ручная корректировка] {comment.strip()}".strip()
        )
    db.commit()
    return _flash(f"/threads/{thread_id}", "Статус изменён вручную.", "ok")


@router.post("/threads/{thread_id}/requests/{request_id}/toggle")
def toggle_request(
    thread_id: int, request_id: int, db: Session = Depends(get_db)
) -> RedirectResponse:
    item = db.get(CustomerRequest, request_id)
    if item is None or item.thread_id != thread_id:
        return _flash(f"/threads/{thread_id}", "Задача не найдена.", "error")
    item.fulfilled = not item.fulfilled
    item.manual_override = True
    item.source = "manual"
    db.add(
        ManualReview(
            thread_id=thread_id,
            field=f"request:{item.request_type}",
            comment=f"Отметка «{'выполнено' if item.fulfilled else 'не выполнено'}» проставлена вручную",
        )
    )
    db.commit()
    return _flash(f"/threads/{thread_id}", "Задача обновлена.", "ok")


@router.post("/threads/{thread_id}/requests/add")
def add_request(
    thread_id: int,
    request_type: str = Form(...),
    description: str = Form(""),
    db: Session = Depends(get_db),
) -> RedirectResponse:
    thread = db.get(Thread, thread_id)
    if thread is None:
        return _flash("/threads", "Обращение не найдено.", "error")
    db.add(
        CustomerRequest(
            thread_id=thread_id,
            request_type=request_type,
            description=description or "Добавлено вручную",
            fulfilled=False,
            evidence=json.dumps([], ensure_ascii=False),
            source="manual",
            manual_override=True,
        )
    )
    db.add(
        ManualReview(
            thread_id=thread_id,
            field="request:add",
            comment=f"Добавлен запрос «{request_type}»",
        )
    )
    db.commit()
    return _flash(f"/threads/{thread_id}", "Задача добавлена.", "ok")


# ------------------------------------------------------------------
# Настройки
# ------------------------------------------------------------------

@router.post("/actions/settings")
async def save_settings(request: Request, db: Session = Depends(get_db)) -> RedirectResponse:
    form = await request.form()

    def as_list(name: str) -> list[str]:
        raw = str(form.get(name, "") or "")
        return [v.strip().lower() for v in raw.replace("\n", ",").split(",") if v.strip()]

    def as_int(name: str, default: int) -> int:
        try:
            return int(str(form.get(name, default)))
        except (TypeError, ValueError):
            return default

    def as_float(name: str, default: float) -> float:
        try:
            return float(str(form.get(name, default)).replace(",", "."))
        except (TypeError, ValueError):
            return default

    managers: list[dict] = []
    emails = form.getlist("manager_email")
    names = form.getlist("manager_name")
    for email, name in zip(emails, names):
        email = str(email).strip().lower()
        if email:
            managers.append({"email": email, "name": str(name).strip()})

    values = {
        "corporate.domains": [d.lstrip("@") for d in as_list("corporate_domains")],
        "corporate.emails": as_list("corporate_emails"),
        "corporate.managers": managers,
        "work.days": [int(d) for d in form.getlist("work_days") if str(d).isdigit()] or [1, 2, 3, 4, 5],
        "work.start": str(form.get("work_start", "09:00")),
        "work.end": str(form.get("work_end", "18:00")),
        "work.timezone": str(form.get("work_timezone", "Europe/Moscow")),
        "work.holidays": as_list("work_holidays"),
        "sla.warning_hours": as_float("sla_warning_hours", 2),
        "sla.critical_hours": as_float("sla_critical_hours", 4),
        "sla.vip_hours": as_float("sla_vip_hours", 1),
        "sla.claim_hours": as_float("sla_claim_hours", 1),
        "sla.invoice_hours": as_float("sla_invoice_hours", 3),
        "sla.count_weekends": bool(form.get("sla_count_weekends")),
        "sla.vip_clients": as_list("sla_vip_clients"),
        "exclusions.senders": as_list("exclusions_senders"),
        "exclusions.domains": as_list("exclusions_domains"),
        "exclusions.subject_keywords": as_list("exclusions_subjects"),
        "exclusions.use_headers": bool(form.get("exclusions_use_headers")),
        "ai.enabled": bool(form.get("ai_enabled")),
        "ai.test_mode": bool(form.get("ai_test_mode")),
        "ai.max_threads_per_run": as_int("ai_max_threads", 50),
        "ai.model": str(form.get("ai_model", settings.openai_model) or settings.openai_model),
        "attachments.save_local": bool(form.get("attachments_save_local")),
        "sync.default_days": as_int("sync_default_days", 7),
        "sync.max_messages_per_folder": as_int("sync_max_messages", 2000),
    }
    update_settings(db, values)

    # Папки для анализа
    enabled_folders = {int(f) for f in form.getlist("folder_enabled") if str(f).isdigit()}
    folders = db.scalars(select(MailFolder)).all()
    if folders:
        for folder in folders:
            folder.enabled = folder.id in enabled_folders
        db.commit()

    set_schedule(WorkSchedule.from_settings(get_all_settings(db)))
    recompute_all_technical(db)
    return _flash("/settings", "Настройки сохранены. Технические показатели пересчитаны.", "ok")


@router.post("/actions/exclude-sender")
def exclude_sender(
    request: Request,
    value: str = Form(...),
    kind: str = Form("sender"),
    db: Session = Depends(get_db),
) -> RedirectResponse:
    data = get_all_settings(db)
    key = "exclusions.senders" if kind == "sender" else "exclusions.domains"
    current = list(data.get(key) or [])
    value = value.strip().lower()
    if value and value not in current:
        current.append(value)
        update_settings(db, {key: current})
    return _flash("/settings", f"Добавлено в исключения: {value}", "ok")


# ------------------------------------------------------------------
# Демо-режим
# ------------------------------------------------------------------

@router.post("/actions/demo/load")
def load_demo(analyze: str | None = Form("1")) -> RedirectResponse:
    do_analyze = bool(analyze)

    def _work() -> dict:
        with session_scope() as db:
            result = load_demo_data(db)
        if do_analyze:
            with session_scope() as db:
                stats = analyze_threads(db, force=True)
                result["analysis"] = stats.as_dict()
        result["message"] = (
            f"Загружено демо-цепочек: {result['threads']}, писем: {result['messages']}"
        )
        return result

    started = jobs.run("demo", _work, "Загрузка демонстрационных данных…")
    if not started:
        return _flash("/demo", "Загрузка уже выполняется.", "warn")
    return _flash("/demo", "Демонстрационные данные загружаются…", "ok")


@router.post("/actions/demo/clear")
def clear_demo(db: Session = Depends(get_db)) -> RedirectResponse:
    """Удаляет демонстрационные переписки. Реальная почта не затрагивается."""
    from app.services.demo_data import clear_demo_data

    clear_demo_data(db)
    return _flash(
        "/",
        "Демонстрационные данные удалены. Реальная почта не затронута.",
        "ok",
    )


# ------------------------------------------------------------------
# Экспорт
# ------------------------------------------------------------------

@router.get("/export/excel")
def export_excel(day: str | None = None, db: Session = Depends(get_db)):
    target = _parse_day(day)
    buffer = build_workbook(db, target)
    filename = f"mail_analytics_{target.isoformat()}.xlsx"
    return StreamingResponse(
        buffer,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


@router.get("/export/html", response_class=HTMLResponse)
def export_html(request: Request, day: str | None = None, db: Session = Depends(get_db)):
    target = _parse_day(day)
    report = daily_report(db, target)
    all_threads = []
    for group in report["sections"].values():
        all_threads.extend(group)
    requests_map: dict[int, list[CustomerRequest]] = {}
    ids = [t.id for t in all_threads]
    if ids:
        for row in db.scalars(
            select(CustomerRequest).where(CustomerRequest.thread_id.in_(ids))
        ).all():
            requests_map.setdefault(row.thread_id, []).append(row)

    html = templates.get_template("export_report.html").render(
        request=request,
        report=report,
        target=target,
        requests_map=requests_map,
        generated_at=datetime.now(),
    )
    filename = f"mail_analytics_{target.isoformat()}.html"
    return HTMLResponse(
        html, headers={"Content-Disposition": f'attachment; filename="{filename}"'}
    )


def _parse_day(value: str | None) -> date:
    if not value:
        return date.today()
    try:
        return date.fromisoformat(value)
    except ValueError:
        return date.today()


# ------------------------------------------------------------------
# API состояния (для автообновления страницы)
# ------------------------------------------------------------------

@router.get("/api/jobs")
def jobs_status() -> JSONResponse:
    return JSONResponse(jobs.snapshot())
