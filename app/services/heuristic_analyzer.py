"""Офлайн-анализ переписки без обращения к OpenAI.

Используется:
* в тестовом режиме (AI_TEST_MODE=true) и при AI_ENABLED=false;
* как запасной вариант при ошибке OpenAI;
* как проверка правдоподобности результата AI.

Все выводы делаются только по фактам переписки: цитаты берутся из текста писем,
имена файлов — из реальных вложений. Ничего не додумывается.
"""

from __future__ import annotations

import re

from app.core.constants import RequestType, ThreadStatus
from app.services import request_rules as rules
from app.services.analysis_schema import CustomerRequestItem, Evidence, ThreadAnalysis
from app.services.attachments import (
    is_document,
    looks_like_invoice,
    looks_like_offer,
)
from app.services.thread_context import MessageContext, ThreadContext

MIN_SUBSTANTIVE_LEN = 40

_INN_RE = re.compile(r"\b(инн|kpp|кпп|р/с|расчетный\s+счет|расчётный\s+счёт|бик)\b", re.IGNORECASE)
_WARRANTY_RE = re.compile(
    r"гаранти\w*\D{0,30}(\d+\s*(мес|год|лет|дн)|\d+\s*лет)|гарантийный\s+срок\D{0,20}\d",
    re.IGNORECASE,
)
_ORDER_STATUS_RE = re.compile(
    r"(отгруж\w+|отправлен\w*\s+\d{1,2}|передан\w*\s+в\s+тк|трек[\s-]?номер\D{0,10}\w+|"
    r"накладн\w+\s+№|заказ\s+(готов|собран|в\s+пути|прибыл)|прибудет\s+\d)",
    re.IGNORECASE,
)
_PAYMENT_STATUS_RE = re.compile(
    r"(оплата\s+(поступила|получена|не\s+поступила|прошла)|платеж\w*\s+(поступил|получен|не\s+найден)|"
    r"средства\s+(поступили|зачислены)|задолженност\w+\s+отсутств)",
    re.IGNORECASE,
)
_COMPATIBILITY_RE = re.compile(
    r"(совместим\w*|не\s+совместим\w*|подойдет|подойдёт|не\s+подойдет|не\s+подойдёт|"
    r"подходит|не\s+подходит|встанет\s+без|требуется\s+переходник)",
    re.IGNORECASE,
)
_CLAIM_RESOLVED_RE = re.compile(
    r"(оформ\w+\s+(замен|возврат|рекламац)|принял\w*\s+претензи|"
    r"направ\w+\s+(замен|нового|новый)|верн\w+\s+денежн|"
    r"акт\s+рекламации|организуем\s+(вывоз|забор)|компенсир)",
    re.IGNORECASE,
)
_RETURN_RESOLVED_RE = re.compile(
    r"(возврат\s+(оформлен|согласован|принят)|примем\s+товар|"
    r"направьте\s+товар\s+по\s+адресу|деньги\s+вернем|денежные\s+средства\s+будут\s+возвращены)",
    re.IGNORECASE,
)


# ------------------------------------------------------------------
# Распознавание запросов клиента
# ------------------------------------------------------------------

def detect_requests(context: ThreadContext) -> list[tuple[str, str, str, MessageContext]]:
    """[(тип, описание, цитата, письмо)] — по всем письмам клиента."""
    found: dict[str, tuple[str, str, MessageContext]] = {}
    for msg in context.incoming:
        text = msg.text or ""
        if not text.strip():
            continue
        for pattern in rules.REQUEST_PATTERNS:
            match = pattern.search(text)
            if not match:
                continue
            if pattern.request_type in found:
                continue
            quote = rules.find_quote(text, match)
            found[pattern.request_type] = (pattern.description, quote, msg)

    # «Консультация» не показывается, если есть более конкретные запросы
    if len(found) > 1 and RequestType.CONSULTATION in found:
        found.pop(RequestType.CONSULTATION)

    # Подбор товара поглощается подбором аналога
    if RequestType.ANALOGUE_SELECTION in found and RequestType.PRODUCT_SELECTION in found:
        found.pop(RequestType.PRODUCT_SELECTION)

    return [(rtype, desc, quote, msg) for rtype, (desc, quote, msg) in found.items()]


# ------------------------------------------------------------------
# Проверка выполнения
# ------------------------------------------------------------------

def _documents(msg: MessageContext) -> list:
    return [a for a in msg.attachments if is_document(a.filename)]


def _check(
    request_type: str,
    manager_messages: list[MessageContext],
) -> tuple[bool, list[Evidence], str | None, bool]:
    """Возвращает (выполнено, подтверждения, чего не хватает, нужна_ручная_проверка)."""
    evidence: list[Evidence] = []
    needs_review = False

    def text_hit(pattern: re.Pattern, msg: MessageContext) -> str | None:
        match = pattern.search(msg.text or "")
        if not match:
            return None
        fragment = match.group(0)
        # Расплывчатая формулировка или отрицание рядом с совпадением
        # не считаются выполнением запроса
        window_start = max(0, (msg.text or "").lower().find(fragment.lower()) - 80)
        window = (msg.text or "")[window_start: window_start + len(fragment) + 160]
        if rules.is_vague(window) or rules.DENIAL.search(window):
            return None
        return fragment

    # ---------- Счёт ----------
    if request_type == RequestType.INVOICE:
        for msg in manager_messages:
            for att in msg.attachments:
                if looks_like_invoice(att.filename):
                    evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        for msg in manager_messages:
            hit = text_hit(rules.INVOICE_SENT_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            # Заявлено, что счёт отправлен, но файла нет — требуется проверка
            return True, evidence, None, True
        return False, [], "Счёт не приложен и в переписке нет подтверждения его отправки", False

    # ---------- Коммерческое предложение ----------
    if request_type == RequestType.COMMERCIAL_OFFER:
        for msg in manager_messages:
            for att in msg.attachments:
                if looks_like_offer(att.filename):
                    evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        for msg in manager_messages:
            hit = text_hit(rules.OFFER_SENT_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, True
        return False, [], "Коммерческое предложение не приложено и не отправлено", False

    # ---------- Цена ----------
    if request_type == RequestType.PRICE:
        for msg in manager_messages:
            for pattern in (rules.PRICE_RE, rules.PRICE_CONTEXT_RE):
                hit = text_hit(pattern, msg)
                if hit:
                    evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
                    break
            for att in msg.attachments:
                if looks_like_invoice(att.filename) or looks_like_offer(att.filename):
                    evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение с ценой: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        return False, [], "Конкретная цена в ответе не указана", False

    # ---------- Наличие ----------
    if request_type == RequestType.AVAILABILITY:
        for msg in manager_messages:
            hit = text_hit(rules.AVAILABILITY_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Наличие товара не подтверждено и не опровергнуто", False

    # ---------- Срок поставки ----------
    if request_type == RequestType.DELIVERY_TIME:
        for msg in manager_messages:
            hit = text_hit(rules.DELIVERY_TIME_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Конкретный срок поставки не указан", False

    # ---------- Доставка ----------
    if request_type == RequestType.DELIVERY_COST:
        for msg in manager_messages:
            hit = text_hit(rules.DELIVERY_INFO_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Условия и стоимость доставки не рассчитаны", False

    # ---------- Аналог ----------
    if request_type == RequestType.ANALOGUE_SELECTION:
        for msg in manager_messages:
            text = msg.text or ""
            if rules.is_vague(text) and not rules.ANALOGUE_OFFER_RE.search(text):
                continue
            offer = rules.ANALOGUE_OFFER_RE.search(text)
            model = rules.MODEL_CODE_RE.search(text)
            if offer and model:
                evidence.append(
                    Evidence(message_id=msg.ref, quote=rules.find_quote(text, model.group(0)))
                )
            elif model and not rules.is_vague(text):
                evidence.append(
                    Evidence(message_id=msg.ref, quote=rules.find_quote(text, model.group(0)))
                )
                needs_review = True
        if evidence:
            return True, evidence, None, needs_review
        return False, [], "Конкретная модель или артикул аналога не предложены", False

    # ---------- Подбор товара ----------
    if request_type == RequestType.PRODUCT_SELECTION:
        for msg in manager_messages:
            text = msg.text or ""
            model = rules.MODEL_CODE_RE.search(text)
            if model and not rules.is_vague(text):
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(text, model.group(0))))
            for att in msg.attachments:
                if looks_like_offer(att.filename):
                    evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        return False, [], "Конкретный товар не предложен", False

    # ---------- Документы / характеристики / договор ----------
    if request_type in {
        RequestType.DOCUMENTS,
        RequestType.TECHNICAL_SPECIFICATION,
        RequestType.CONTRACT,
    }:
        for msg in manager_messages:
            for att in _documents(msg):
                evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        for msg in manager_messages:
            hit = text_hit(rules.ATTACHMENT_MENTION_RE, msg)
            if hit and "http" in (msg.text or "").lower():
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, True
        return False, [], "Запрошенные документы не приложены", False

    # ---------- Реквизиты ----------
    if request_type == RequestType.REQUISITES:
        for msg in manager_messages:
            if _INN_RE.search(msg.text or ""):
                hit = _INN_RE.search(msg.text or "")
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit.group(0))))
            for att in _documents(msg):
                if "реквизит" in att.filename.lower() or "карточк" in att.filename.lower():
                    evidence.append(Evidence(message_id=msg.ref, quote=f"Вложение: {att.filename}"))
        if evidence:
            return True, evidence, None, False
        return False, [], "Реквизиты не отправлены", False

    # ---------- Гарантия ----------
    if request_type == RequestType.WARRANTY:
        for msg in manager_messages:
            hit = text_hit(_WARRANTY_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Условия гарантии не сообщены", False

    # ---------- Статус заказа ----------
    if request_type == RequestType.ORDER_STATUS:
        for msg in manager_messages:
            hit = text_hit(_ORDER_STATUS_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Статус заказа не сообщён", False

    # ---------- Статус оплаты ----------
    if request_type == RequestType.PAYMENT_STATUS:
        for msg in manager_messages:
            hit = text_hit(_PAYMENT_STATUS_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Статус оплаты не подтверждён", False

    # ---------- Совместимость ----------
    if request_type == RequestType.COMPATIBILITY:
        for msg in manager_messages:
            hit = text_hit(_COMPATIBILITY_RE, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, False
        return False, [], "Вопрос о совместимости не закрыт", False

    # ---------- Претензия / возврат ----------
    if request_type in {RequestType.CLAIM, RequestType.RETURN}:
        pattern = _CLAIM_RESOLVED_RE if request_type == RequestType.CLAIM else _RETURN_RESOLVED_RE
        for msg in manager_messages:
            hit = text_hit(pattern, msg)
            if hit:
                evidence.append(Evidence(message_id=msg.ref, quote=rules.find_quote(msg.text, hit)))
        if evidence:
            return True, evidence, None, True
        return False, [], "Решение по обращению не зафиксировано", True

    # ---------- Консультация и прочее ----------
    for msg in manager_messages:
        text = (msg.text or "").strip()
        if len(text) >= MIN_SUBSTANTIVE_LEN and not rules.is_vague(text):
            evidence.append(Evidence(message_id=msg.ref, quote=text[:160]))
            break
    if evidence:
        return True, evidence, None, True
    return False, [], "Содержательного ответа нет", False


# ------------------------------------------------------------------
# Итоговый анализ цепочки
# ------------------------------------------------------------------

def analyze(context: ThreadContext, sla_exceeded: bool = False) -> ThreadAnalysis:
    """Полный офлайн-анализ цепочки."""
    messages = context.messages
    if not messages:
        return ThreadAnalysis(
            thread_summary="Цепочка не содержит писем.",
            status=ThreadStatus.NEEDS_REVIEW,
            status_reason="Нет писем для анализа.",
            next_action="Проверить синхронизацию почты.",
            responsible_party="none",
            confidence=0.3,
            needs_manual_review=True,
        )

    # Автоматические письма
    non_outgoing = [m for m in messages if m.direction != "outgoing"]
    if non_outgoing and all(m.is_automated for m in non_outgoing):
        return ThreadAnalysis(
            thread_summary="Автоматическое письмо или рассылка — не клиентское обращение.",
            status=ThreadStatus.NOT_RELEVANT,
            status_reason="Отправитель или заголовки письма указывают на автоматическую рассылку.",
            next_action="Действий не требуется.",
            responsible_party="none",
            confidence=0.9,
            needs_manual_review=False,
        )

    incoming = context.incoming
    outgoing = context.outgoing

    detected = detect_requests(context)
    items: list[CustomerRequestItem] = []
    needs_review = False

    for rtype, description, quote, source_msg in detected:
        # Ответы, отправленные после письма клиента с этим запросом
        relevant = [
            m for m in outgoing
            if not (m.sent_at and source_msg.sent_at) or m.sent_at >= source_msg.sent_at
        ]
        fulfilled, evidence, missing, review = _check(rtype, relevant)
        needs_review = needs_review or review
        items.append(
            CustomerRequestItem(
                type=rtype,
                description=f"{description}: «{quote[:120]}»" if quote else description,
                fulfilled=fulfilled,
                evidence=evidence,
                missing_information=missing,
            )
        )

    manager_questions: list[str] = []
    if outgoing:
        manager_questions = rules.manager_requests_from_client(outgoing[-1].text or "")

    last = messages[-1]
    unfulfilled = [i for i in items if not i.fulfilled]
    fulfilled_items = [i for i in items if i.fulfilled]

    # ----- определение статуса -----
    if not outgoing:
        status = ThreadStatus.CRITICAL
        reason = "Ответ менеджера в цепочке отсутствует."
        action = "Ответить клиенту на исходный запрос."
        party = "manager"
        confidence = 0.9
    elif last.direction == "incoming":
        status = ThreadStatus.CRITICAL
        reason = "Последнее письмо в цепочке — от клиента, реакции менеджера на него нет."
        action = "Ответить на последнее письмо клиента."
        party = "manager"
        confidence = 0.85
    elif manager_questions and not unfulfilled:
        status = ThreadStatus.WAITING_FOR_CLIENT
        reason = "; ".join(manager_questions) + ". Ожидается ответ клиента."
        action = "Дождаться ответа клиента; при отсутствии — напомнить."
        party = "client"
        confidence = 0.75
    elif manager_questions and unfulfilled:
        # Менеджер корректно запросил недостающие данные — это не его ошибка
        status = ThreadStatus.WAITING_FOR_CLIENT
        reason = (
            "; ".join(manager_questions)
            + ". Оставшиеся задачи невозможно закрыть без ответа клиента: "
            + ", ".join(i.description.split(":")[0] for i in unfulfilled)
            + "."
        )
        action = "Дождаться данных от клиента, затем закрыть оставшиеся задачи."
        party = "client"
        confidence = 0.65
        needs_review = True
    elif unfulfilled:
        status = ThreadStatus.INCOMPLETE
        reason = _build_reason(items)
        action = "Выполнить незакрытые задачи: " + ", ".join(
            i.description.split(":")[0] for i in unfulfilled
        )
        party = "manager"
        confidence = 0.8
    elif items:
        status = ThreadStatus.COMPLETED
        reason = _build_reason(items)
        action = "Действий не требуется."
        party = "none"
        confidence = 0.8
    else:
        # Запросы не распознаны, но ответ есть
        status = ThreadStatus.NEEDS_REVIEW
        reason = "Не удалось однозначно определить запрос клиента по тексту переписки."
        action = "Проверить переписку вручную."
        party = "manager"
        confidence = 0.4
        needs_review = True

    if sla_exceeded and status in {ThreadStatus.INCOMPLETE, ThreadStatus.NEEDS_REVIEW}:
        reason += " Превышен допустимый срок ответа."

    summary_parts = []
    if incoming:
        summary_parts.append(f"Обращение от {context.client_name or context.client_email or 'клиента'}")
    if items:
        summary_parts.append("запросы: " + ", ".join(_ru_type(i.type) for i in items))
    if fulfilled_items:
        summary_parts.append(f"выполнено {len(fulfilled_items)} из {len(items)}")
    summary = ". ".join(summary_parts) or (context.subject or "Переписка без распознанных запросов")

    return ThreadAnalysis(
        thread_summary=summary[:500],
        customer_requests=items,
        status=status,
        status_reason=reason[:1000],
        next_action=action[:500],
        responsible_party=party,
        confidence=confidence,
        needs_manual_review=needs_review,
    )


def _ru_type(request_type: str) -> str:
    from app.core.constants import REQUEST_TYPE_RU

    return REQUEST_TYPE_RU.get(request_type, request_type)


def _build_reason(items: list[CustomerRequestItem]) -> str:
    done = [f"{_ru_type(i.type).lower()} — предоставлено" for i in items if i.fulfilled]
    missing = [
        f"{_ru_type(i.type).lower()} — {(i.missing_information or 'не выполнено').lower()}"
        for i in items
        if not i.fulfilled
    ]
    parts = []
    if done:
        parts.append("Выполнено: " + "; ".join(done) + ".")
    if missing:
        parts.append("Не выполнено: " + "; ".join(missing) + ".")
    return " ".join(parts) or "Задачи клиента не выявлены."
