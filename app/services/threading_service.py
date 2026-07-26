"""Группировка писем в цепочки (клиентские обращения).

Признаки, используемые по убыванию надёжности:
1. Message-ID / In-Reply-To / References
2. Нормализованная тема + совпадение участников + временная близость
3. Иначе — новая цепочка

Похожая тема сама по себе НЕ является поводом объединять разные обращения.
"""

from __future__ import annotations

import json
import re
from collections import Counter
from datetime import timedelta

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.constants import Direction, ThreadStatus
from app.core.logging_setup import get_logger
from app.models import Message, Thread
from app.services.email_utils import guess_company_name, is_corporate
from app.services.subject_utils import normalize_subject

logger = get_logger(__name__)

# Темы, по которым нельзя объединять цепочки (слишком общие)
GENERIC_SUBJECTS = {
    "", "запрос", "вопрос", "здравствуйте", "добрый день", "доброе утро",
    "добрый вечер", "заявка", "письмо", "информация", "коммерческое предложение",
    "счет", "счёт", "оплата", "заказ", "прайс", "hello", "hi", "request", "info",
    "документы", "реквизиты", "уточнение", "срочно", "кп",
}

# Максимальный разрыв между письмами для объединения по теме
SUBJECT_MERGE_WINDOW = timedelta(days=30)

_MSGID_RE = re.compile(r"<([^<>]+)>")


def parse_references(raw: str | None) -> list[str]:
    """Извлекает Message-ID из заголовка References/In-Reply-To."""
    if not raw:
        return []
    ids = _MSGID_RE.findall(raw)
    if not ids:
        # Некоторые клиенты пишут без угловых скобок
        ids = [part.strip() for part in raw.split() if "@" in part]
    return [i.strip() for i in ids if i.strip()]


def normalize_message_id(value: str | None) -> str | None:
    if not value:
        return None
    found = _MSGID_RE.findall(value)
    if found:
        return found[0].strip()
    value = value.strip()
    return value or None


def _thread_by_reference(db: Session, refs: list[str], account_id: int) -> Thread | None:
    if not refs:
        return None
    stmt = (
        select(Message)
        .where(Message.account_id == account_id)
        .where(Message.message_id_header.in_(refs))
        .where(Message.thread_id.is_not(None))
    )
    for candidate in db.scalars(stmt).all():
        if candidate.thread_id:
            thread = db.get(Thread, candidate.thread_id)
            if thread is not None:
                return thread
    return None


def _thread_by_reference_backlink(db: Session, message_id: str | None, account_id: int) -> Thread | None:
    """Если ранее загруженное письмо ссылается на текущее (обратный порядок загрузки)."""
    if not message_id:
        return None
    like = f"%{message_id}%"
    stmt = (
        select(Message)
        .where(Message.account_id == account_id)
        .where(Message.thread_id.is_not(None))
        .where((Message.in_reply_to.like(like)) | (Message.references.like(like)))
        .limit(5)
    )
    for candidate in db.scalars(stmt).all():
        if candidate.thread_id:
            thread = db.get(Thread, candidate.thread_id)
            if thread is not None:
                return thread
    return None


def _participants(message: Message) -> set[str]:
    people = {(message.sender_email or "").lower()}
    for field in (message.recipients, message.cc):
        try:
            for addr in json.loads(field or "[]"):
                if isinstance(addr, dict):
                    people.add((addr.get("email") or "").lower())
                elif isinstance(addr, str):
                    people.add(addr.lower())
        except (json.JSONDecodeError, TypeError):
            continue
    return {p for p in people if p}


def _thread_by_subject(
    db: Session,
    message: Message,
    account_id: int,
    domains: set[str],
    emails: set[str],
) -> tuple[Thread | None, float]:
    """Поиск цепочки по нормализованной теме + участникам + времени."""
    norm = message.normalized_subject
    if not norm or norm in GENERIC_SUBJECTS or len(norm) < 8:
        return None, 0.0

    external = {p for p in _participants(message) if not is_corporate(p, domains, emails)}
    if not external:
        return None, 0.0

    stmt = (
        select(Thread)
        .where(Thread.account_id == account_id)
        .where(Thread.normalized_subject == norm)
        .order_by(Thread.last_message_at.desc())
        .limit(10)
    )
    sent_at = message.sent_at
    for thread in db.scalars(stmt).all():
        if thread.client_email and thread.client_email.lower() in external:
            if sent_at and thread.last_message_at:
                gap = abs(sent_at - thread.last_message_at)
                if gap > SUBJECT_MERGE_WINDOW:
                    continue
            # Тема совпала точно, клиент тот же, письма рядом по времени
            return thread, 0.8
    return None, 0.0


def assign_thread(
    db: Session,
    message: Message,
    domains: set[str],
    emails: set[str],
) -> Thread:
    """Определяет (или создаёт) цепочку для письма."""
    refs = parse_references(message.references) + parse_references(message.in_reply_to)
    refs = list(dict.fromkeys(refs))

    thread = _thread_by_reference(db, refs, message.account_id)
    confidence = 1.0

    if thread is None:
        thread = _thread_by_reference_backlink(db, message.message_id_header, message.account_id)

    if thread is None:
        thread, confidence = _thread_by_subject(db, message, message.account_id, domains, emails)

    if thread is None:
        thread = Thread(
            account_id=message.account_id,
            thread_key=message.message_id_header or f"msg-{message.id}",
            normalized_subject=message.normalized_subject or "",
            subject=message.subject or "",
            grouping_confidence=1.0 if message.message_id_header else 0.6,
            current_status=ThreadStatus.NEW,
            tech_status=ThreadStatus.NEW,
        )
        db.add(thread)
        db.flush()
    else:
        if confidence and confidence < thread.grouping_confidence:
            thread.grouping_confidence = confidence
            if confidence < 0.85:
                thread.needs_review = True

    message.thread_id = thread.id
    return thread


def recompute_thread(
    db: Session,
    thread: Thread,
    domains: set[str],
    emails: set[str],
) -> Thread:
    """Пересчитывает агрегаты цепочки по её письмам."""
    messages = db.scalars(
        select(Message).where(Message.thread_id == thread.id).order_by(Message.sent_at)
    ).all()
    messages = [m for m in messages if m.sent_at is not None] + [m for m in messages if m.sent_at is None]

    if not messages:
        return thread

    incoming = [m for m in messages if m.direction == Direction.INCOMING]
    outgoing = [m for m in messages if m.direction == Direction.OUTGOING]

    thread.message_count = len(messages)
    thread.incoming_count = len(incoming)
    thread.outgoing_count = len(outgoing)
    thread.first_message_at = messages[0].sent_at
    thread.last_message_at = max((m.sent_at for m in messages if m.sent_at), default=None)
    last_msg = max(
        (m for m in messages if m.sent_at), key=lambda m: m.sent_at, default=messages[-1]
    )
    thread.last_message_direction = last_msg.direction
    thread.last_incoming_at = max((m.sent_at for m in incoming if m.sent_at), default=None)
    thread.last_outgoing_at = max((m.sent_at for m in outgoing if m.sent_at), default=None)

    # Тема: берём из первого письма клиента, иначе из первого письма цепочки
    subject_source = incoming[0] if incoming else messages[0]
    thread.subject = subject_source.subject or thread.subject
    thread.normalized_subject = subject_source.normalized_subject or thread.normalized_subject

    # Клиент
    client_email, client_name = _detect_client(messages, domains, emails)
    if client_email:
        thread.client_email = client_email
        if client_name:
            thread.client_name = client_name
        thread.company_name = guess_company_name(client_email, client_name) or thread.company_name

    # Менеджеры
    responders = [m for m in outgoing if m.sender_email]
    if responders:
        thread.first_responder = responders[0].sender_email
        thread.last_responder = responders[-1].sender_email
        counts = Counter(m.sender_email for m in responders)
        thread.assigned_manager = counts.most_common(1)[0][0]
    # Первый ответ менеджера после первого письма клиента
    if incoming and responders:
        first_in = incoming[0].sent_at
        after = [m.sent_at for m in responders if m.sent_at and first_in and m.sent_at >= first_in]
        thread.first_response_at = min(after) if after else None
    else:
        thread.first_response_at = None

    thread.has_attachments = any(m.has_attachments for m in messages)
    thread.has_outgoing_attachments = any(m.has_attachments for m in outgoing)
    thread.has_reply = bool(outgoing)

    # Повторное обращение: два и более входящих подряд без ответа между ними
    thread.is_repeat_request = _has_repeat_request(messages)

    # Автоматическое письмо: все письма цепочки автоматические
    if messages and all(m.is_automated for m in messages if m.direction != Direction.OUTGOING):
        automated_sources = [m for m in messages if m.direction != Direction.OUTGOING]
        if automated_sources:
            thread.is_automated = True
            thread.exclusion_reason = automated_sources[0].exclusion_reason
    else:
        thread.is_automated = False
        thread.exclusion_reason = None

    if not thread.thread_key:
        thread.thread_key = messages[0].message_id_header or f"thread-{thread.id}"

    db.flush()
    return thread


def _detect_client(messages, domains: set[str], emails: set[str]) -> tuple[str | None, str | None]:
    for msg in messages:
        if msg.direction == Direction.INCOMING and msg.sender_email:
            return msg.sender_email, msg.sender_name
    # Цепочка началась с письма менеджера — клиент среди получателей
    for msg in messages:
        if msg.direction != Direction.OUTGOING:
            continue
        try:
            recipients = json.loads(msg.recipients or "[]")
        except (json.JSONDecodeError, TypeError):
            recipients = []
        for addr in recipients:
            email = (addr.get("email") if isinstance(addr, dict) else str(addr)).lower()
            if email and not is_corporate(email, domains, emails):
                name = addr.get("name") if isinstance(addr, dict) else None
                return email, name
    return None, None


def _has_repeat_request(messages) -> bool:
    """Клиент написал повторно, не получив ответа."""
    streak = 0
    for msg in messages:
        if msg.direction == Direction.INCOMING:
            streak += 1
            if streak >= 2:
                return True
        elif msg.direction == Direction.OUTGOING:
            streak = 0
    return False
