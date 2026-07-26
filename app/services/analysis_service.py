"""Оркестрация анализа: технические правила + AI/офлайн-анализ + сохранение."""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import datetime

from sqlalchemy import delete, select
from sqlalchemy.orm import Session

from app.core.constants import Direction, ThreadStatus
from app.core.logging_setup import get_logger
from app.models import AnalysisResult, CustomerRequest, Thread, utcnow
from app.services import heuristic_analyzer
from app.services.ai_analyzer import AIResult, OpenAIAnalyzer, validate_ai_result
from app.services.analysis_schema import PROMPT_VERSION, ThreadAnalysis
from app.services.rule_analyzer import compute_technical, facts_for_ai
from app.services.settings_service import corporate_identity, get_all_settings
from app.services.thread_context import build_context
from app.services.threading_service import recompute_thread
from app.services.working_time import WorkSchedule

logger = get_logger(__name__)


@dataclass
class AnalysisStats:
    threads_total: int = 0
    analyzed: int = 0
    skipped_unchanged: int = 0
    skipped_manual: int = 0
    ai_calls: int = 0
    offline_calls: int = 0
    tokens: int = 0
    errors: list[str] = field(default_factory=list)

    def as_dict(self) -> dict:
        return {
            "threads_total": self.threads_total,
            "analyzed": self.analyzed,
            "skipped_unchanged": self.skipped_unchanged,
            "skipped_manual": self.skipped_manual,
            "ai_calls": self.ai_calls,
            "offline_calls": self.offline_calls,
            "tokens": self.tokens,
            "errors": self.errors[:20],
        }


def _reconcile(thread: Thread, analysis: ThreadAnalysis) -> ThreadAnalysis:
    """Согласование результата AI с техническими фактами."""
    if thread.is_automated:
        analysis.status = ThreadStatus.NOT_RELEVANT
        analysis.responsible_party = "none"
        if not analysis.status_reason:
            analysis.status_reason = thread.exclusion_reason or "Автоматическое письмо."
        return analysis

    unanswered = (
        thread.last_message_direction == Direction.INCOMING
        and thread.incoming_count > 0
    )

    # Клиент уже ответил — «ожидаем клиента» противоречит фактам
    if unanswered and analysis.status == ThreadStatus.WAITING_FOR_CLIENT:
        analysis.status = ThreadStatus.CRITICAL
        analysis.status_reason = (
            "Последнее письмо в цепочке — от клиента, ответа менеджера на него нет. "
            + (analysis.status_reason or "")
        ).strip()
        analysis.responsible_party = "manager"

    if not thread.has_reply and analysis.status not in {
        ThreadStatus.NOT_RELEVANT,
        ThreadStatus.CRITICAL,
    }:
        analysis.status = ThreadStatus.CRITICAL
        analysis.status_reason = (
            "В цепочке нет ни одного ответа менеджера. " + (analysis.status_reason or "")
        ).strip()
        analysis.responsible_party = "manager"

    if thread.grouping_confidence < 0.85:
        analysis.needs_manual_review = True

    return analysis


def _save_requests(db: Session, thread: Thread, analysis: ThreadAnalysis, source: str) -> None:
    """Перезаписывает задачи клиента, сохраняя вручную исправленные."""
    manual = db.scalars(
        select(CustomerRequest).where(
            CustomerRequest.thread_id == thread.id,
            CustomerRequest.manual_override.is_(True),
        )
    ).all()
    manual_types = {r.request_type for r in manual}

    db.execute(
        delete(CustomerRequest).where(
            CustomerRequest.thread_id == thread.id,
            CustomerRequest.manual_override.is_(False),
        )
    )
    for item in analysis.customer_requests:
        if item.type in manual_types:
            continue
        db.add(
            CustomerRequest(
                thread_id=thread.id,
                request_type=item.type,
                description=item.description or "",
                fulfilled=item.fulfilled,
                evidence=json.dumps(
                    [e.model_dump() for e in item.evidence], ensure_ascii=False
                ),
                missing_information=item.missing_information,
                source=source,
            )
        )
    db.flush()


def analyze_thread(
    db: Session,
    thread: Thread,
    analyzer: OpenAIAnalyzer | None = None,
    schedule: WorkSchedule | None = None,
    settings_data: dict | None = None,
    force: bool = False,
    use_ai: bool | None = None,
    stats: AnalysisStats | None = None,
) -> Thread:
    """Анализирует одну цепочку."""
    settings_data = settings_data if settings_data is not None else get_all_settings(db)
    schedule = schedule or WorkSchedule.from_settings(settings_data)
    stats = stats or AnalysisStats()

    domains, emails = corporate_identity(db)
    recompute_thread(db, thread, domains, emails)
    compute_technical(db, thread, schedule, settings_data)

    if thread.manual_override and not force:
        stats.skipped_manual += 1
        db.flush()
        return thread

    context = build_context(db, thread)
    if not force and thread.analyzed_content_hash == context.content_hash:
        stats.skipped_unchanged += 1
        db.flush()
        return thread

    ai_enabled = settings_data.get("ai.enabled", True) if use_ai is None else use_ai
    test_mode = bool(settings_data.get("ai.test_mode", False))

    result: AIResult | None = None
    if ai_enabled and not test_mode and analyzer is not None and analyzer.available:
        result = analyzer.analyze(context, facts=facts_for_ai(thread))
        if result.source == "ai":
            stats.ai_calls += 1
            stats.tokens += result.total_tokens
        else:
            stats.errors.append(result.error or "неизвестная ошибка OpenAI")

    if result is None or result.source != "ai":
        offline_error = result.error if result else None
        analysis = heuristic_analyzer.analyze(context, sla_exceeded=bool(thread.sla_exceeded))
        result = AIResult(
            analysis=analysis,
            source="offline",
            model=None,
            error=offline_error,
        )
        stats.offline_calls += 1

    analysis, warnings = validate_ai_result(result.analysis)
    analysis = _reconcile(thread, analysis)

    # --- сохранение ---
    _save_requests(db, thread, analysis, source="ai" if result.source == "ai" else "rules")

    db.add(
        AnalysisResult(
            thread_id=thread.id,
            model=result.model,
            prompt_version=PROMPT_VERSION,
            content_hash=context.content_hash,
            result_json=result.raw_json or analysis.model_dump_json(),
            status=analysis.status,
            confidence=analysis.confidence,
            token_usage=result.total_tokens,
            prompt_tokens=result.prompt_tokens,
            completion_tokens=result.completion_tokens,
            source=result.source,
            error=(result.error or ("; ".join(warnings) if warnings else None)),
        )
    )

    thread.current_status = analysis.status
    thread.status_reason = analysis.status_reason
    thread.next_action = analysis.next_action
    thread.summary = analysis.thread_summary
    thread.responsible_party = analysis.responsible_party
    thread.ai_confidence = analysis.confidence
    thread.needs_review = bool(analysis.needs_manual_review)
    if thread.needs_review and thread.current_status not in {
        ThreadStatus.CRITICAL,
        ThreadStatus.NOT_RELEVANT,
    } and analysis.confidence < 0.5:
        thread.current_status = ThreadStatus.NEEDS_REVIEW
    thread.analyzed_content_hash = context.content_hash
    thread.last_analyzed_at = utcnow()
    thread.analysis_source = result.source
    db.flush()
    stats.analyzed += 1
    return thread


def analyze_threads(
    db: Session,
    thread_ids: list[int] | None = None,
    force: bool = False,
    limit: int | None = None,
    date_from: datetime | None = None,
    use_ai: bool | None = None,
) -> AnalysisStats:
    """Анализирует набор цепочек с учётом ограничения на расходы."""
    settings_data = get_all_settings(db)
    schedule = WorkSchedule.from_settings(settings_data)
    stats = AnalysisStats()

    query = select(Thread)
    if thread_ids:
        query = query.where(Thread.id.in_(thread_ids))
    if date_from is not None:
        query = query.where(Thread.last_message_at >= date_from)
    query = query.order_by(Thread.last_message_at.desc())

    threads = db.scalars(query).all()
    stats.threads_total = len(threads)

    max_ai = int(settings_data.get("ai.max_threads_per_run", 50) or 50)
    if limit:
        threads = threads[:limit]

    analyzer = OpenAIAnalyzer(model=str(settings_data.get("ai.model") or None) or None)
    for thread in threads:
        allow_ai = use_ai
        if allow_ai is None:
            allow_ai = bool(settings_data.get("ai.enabled", True)) and stats.ai_calls < max_ai
        try:
            analyze_thread(
                db,
                thread,
                analyzer=analyzer,
                schedule=schedule,
                settings_data=settings_data,
                force=force,
                use_ai=allow_ai,
                stats=stats,
            )
            db.commit()
        except Exception as exc:  # одна цепочка не должна ломать весь прогон
            db.rollback()
            logger.exception("Ошибка анализа цепочки id=%s", thread.id)
            stats.errors.append(f"Цепочка {thread.id}: {type(exc).__name__}")
    logger.info(
        "Анализ завершён: всего %s, проанализировано %s, AI-вызовов %s, токенов %s",
        stats.threads_total, stats.analyzed, stats.ai_calls, stats.tokens,
    )
    return stats


def recompute_all_technical(db: Session) -> int:
    """Пересчёт технических полей всех цепочек (например, при смене графика)."""
    settings_data = get_all_settings(db)
    schedule = WorkSchedule.from_settings(settings_data)
    domains, emails = corporate_identity(db)
    count = 0
    for thread in db.scalars(select(Thread)).all():
        recompute_thread(db, thread, domains, emails)
        compute_technical(db, thread, schedule, settings_data)
        count += 1
    db.commit()
    return count
