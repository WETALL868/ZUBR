"""Экспорт аналитики в Excel."""

from __future__ import annotations

from datetime import date, datetime
from io import BytesIO

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.utils import get_column_letter
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.constants import REQUEST_TYPE_RU, STATUS_RU, ThreadStatus
from app.models import CustomerRequest, Thread
from app.services.reports import daily_report, manager_statistics
from app.services.working_time import format_duration

HEADER_FILL = PatternFill("solid", fgColor="1F3A5F")
HEADER_FONT = Font(color="FFFFFF", bold=True, size=11)

STATUS_FILL = {
    ThreadStatus.CRITICAL: PatternFill("solid", fgColor="FFD6D6"),
    ThreadStatus.INCOMPLETE: PatternFill("solid", fgColor="FFE8CC"),
    ThreadStatus.WAITING_FOR_CLIENT: PatternFill("solid", fgColor="FFF6CC"),
    ThreadStatus.COMPLETED: PatternFill("solid", fgColor="DDF3DD"),
    ThreadStatus.NOT_RELEVANT: PatternFill("solid", fgColor="EFEFEF"),
    ThreadStatus.NEEDS_REVIEW: PatternFill("solid", fgColor="EADDF5"),
}


def _write_header(ws, headers: list[str]) -> None:
    ws.append(headers)
    for cell in ws[1]:
        cell.fill = HEADER_FILL
        cell.font = HEADER_FONT
        cell.alignment = Alignment(vertical="center", wrap_text=True)
    ws.freeze_panes = "A2"


def _autosize(ws, max_width: int = 60) -> None:
    for column in ws.columns:
        length = 0
        letter = get_column_letter(column[0].column)
        for cell in column:
            value = str(cell.value) if cell.value is not None else ""
            length = max(length, min(len(value), max_width))
        ws.column_dimensions[letter].width = max(10, min(length + 2, max_width))


def _requests_text(db: Session, thread_id: int) -> str:
    items = db.scalars(
        select(CustomerRequest).where(CustomerRequest.thread_id == thread_id)
    ).all()
    if not items:
        return "—"
    return "; ".join(
        f"{REQUEST_TYPE_RU.get(i.request_type, i.request_type)} — "
        f"{'выполнено' if i.fulfilled else 'НЕ выполнено'}"
        for i in items
    )


def _fmt(dt: datetime | None) -> str:
    return dt.strftime("%d.%m.%Y %H:%M") if dt else "—"


def build_workbook(db: Session, target: date, date_from: date | None = None) -> BytesIO:
    """Формирует книгу Excel с листами: сводка, критические, все обращения, менеджеры, типы."""
    report = daily_report(db, target)
    metrics = report["metrics"]
    period_from = date_from or target

    wb = Workbook()

    # ---------- Сводка ----------
    ws = wb.active
    ws.title = "Сводка"
    ws.append([f"Аналитика клиентской почты за {target.strftime('%d.%m.%Y')}"])
    ws["A1"].font = Font(bold=True, size=14)
    ws.append([])
    rows = [
        ("Входящих писем", metrics["incoming_messages"]),
        ("Новых обращений", metrics["new_threads"]),
        ("Обращений в работе за день", metrics["active_threads"]),
        ("Полностью выполнено", metrics["completed"]),
        ("Ответ есть, запрос не выполнен", metrics["incomplete"]),
        ("Критические (без ответа)", metrics["critical"]),
        ("Ожидается ответ клиента", metrics["waiting_client"]),
        ("Прочитано без ответа", metrics["read_unanswered"]),
        ("Не прочитано и без ответа", metrics["unread_unanswered"]),
        ("Требует ручной проверки", metrics["needs_review"]),
        ("Нарушен срок ответа", metrics["sla_exceeded"]),
        ("Не относится к клиентам", metrics["not_relevant"]),
        ("Последняя синхронизация", _fmt(metrics["last_sync_at"])),
    ]
    for label, value in rows:
        ws.append([label, value])
    for row in ws.iter_rows(min_row=3, max_row=2 + len(rows), min_col=1, max_col=1):
        for cell in row:
            cell.font = Font(bold=True)
    _autosize(ws)

    # ---------- Критические обращения ----------
    ws = wb.create_sheet("Критические")
    _write_header(
        ws,
        ["Клиент", "Компания", "Тема", "Запрос клиента", "Время ожидания",
         "Ответственный", "Требуемое действие"],
    )
    for thread in report["sections"]["critical"]:
        ws.append(
            [
                thread.client_email or "—",
                thread.company_name or "—",
                thread.subject or "—",
                _requests_text(db, thread.id),
                format_duration(thread.waiting_seconds),
                thread.assigned_manager or "не определён",
                thread.next_action or "Ответить клиенту",
            ]
        )
    _autosize(ws)

    # ---------- Ответ есть, запрос не выполнен ----------
    ws = wb.create_sheet("Запрос не выполнен")
    _write_header(
        ws,
        ["Клиент", "Компания", "Тема", "Что не выполнено", "Объяснение",
         "Ответственный", "Следующее действие"],
    )
    for thread in report["sections"]["answered_incomplete"]:
        unfulfilled = db.scalars(
            select(CustomerRequest).where(
                CustomerRequest.thread_id == thread.id,
                CustomerRequest.fulfilled.is_(False),
            )
        ).all()
        ws.append(
            [
                thread.client_email or "—",
                thread.company_name or "—",
                thread.subject or "—",
                "; ".join(REQUEST_TYPE_RU.get(i.request_type, i.request_type) for i in unfulfilled) or "—",
                thread.status_reason or "—",
                thread.assigned_manager or "не определён",
                thread.next_action or "—",
            ]
        )
    _autosize(ws)

    # ---------- Все обращения ----------
    ws = wb.create_sheet("Все обращения")
    _write_header(
        ws,
        ["Статус", "Дата первого письма", "Дата последнего письма", "Клиент", "Компания",
         "Тема", "Запросы клиента", "Ответственный", "Время без ответа",
         "Рабочее время без ответа", "Вложения", "Уверенность AI", "Ручная проверка",
         "Причина статуса", "Рекомендуемое действие"],
    )
    threads = db.scalars(
        select(Thread).order_by(Thread.last_message_at.desc())
    ).all()
    for thread in threads:
        ws.append(
            [
                STATUS_RU.get(thread.current_status, thread.current_status),
                _fmt(thread.first_message_at),
                _fmt(thread.last_message_at),
                thread.client_email or "—",
                thread.company_name or "—",
                thread.subject or "—",
                _requests_text(db, thread.id),
                thread.assigned_manager or "не определён",
                format_duration(thread.waiting_seconds),
                format_duration(thread.waiting_business_seconds),
                "да" if thread.has_attachments else "нет",
                round(thread.ai_confidence, 2) if thread.ai_confidence is not None else "—",
                "да" if thread.needs_review else "нет",
                thread.status_reason or "—",
                thread.next_action or "—",
            ]
        )
        fill = STATUS_FILL.get(thread.current_status)
        if fill:
            ws.cell(row=ws.max_row, column=1).fill = fill
    _autosize(ws)

    # ---------- По менеджерам ----------
    ws = wb.create_sheet("По менеджерам")
    _write_header(
        ws,
        ["Менеджер", "Обращений", "С ответом", "Завершено", "Не выполнено",
         "Критических", "Ожидают клиента", "Без ответа",
         "Среднее время первого ответа (рабочее)", "Среднее время закрытия",
         "Ручных исправлений", "Доля завершённых, %"],
    )
    for row in manager_statistics(db, period_from, target):
        ws.append(
            [
                row["name"],
                row["threads"],
                row["processed"],
                row["completed"],
                row["incomplete"],
                row["critical"],
                row["waiting_client"],
                row["no_reply"],
                row["avg_first_response_text"],
                row["avg_close_time_text"],
                row["manual_fixes"],
                row["completion_rate"],
            ]
        )
    ws.append([])
    ws.append(["Показатели носят аналитический характер и не являются оценкой сотрудника."])
    _autosize(ws)

    # ---------- По типам запросов ----------
    ws = wb.create_sheet("По типам запросов")
    _write_header(ws, ["Тип запроса", "Всего", "Выполнено", "Не выполнено", "Доля выполнения, %"])
    for row in report["categories"]:
        share = round(row["fulfilled"] / row["total"] * 100) if row["total"] else 0
        ws.append([row["label"], row["total"], row["fulfilled"], row["unfulfilled"], share])
    _autosize(ws)

    buffer = BytesIO()
    wb.save(buffer)
    buffer.seek(0)
    return buffer
