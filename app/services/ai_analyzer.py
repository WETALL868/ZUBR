"""Смысловой анализ переписки через OpenAI API.

Возвращает строго типизированный JSON (structured output / JSON Schema).
При любой ошибке API анализ не падает — используется офлайн-анализатор.
"""

from __future__ import annotations

import json
import time
from dataclasses import dataclass

from app.core.config import settings
from app.core.logging_setup import get_logger
from app.services.analysis_schema import (
    OPENAI_JSON_SCHEMA,
    PROMPT_VERSION,
    ThreadAnalysis,
)
from app.services.thread_context import ThreadContext

logger = get_logger(__name__)

SYSTEM_PROMPT = """Ты — аналитик клиентской переписки B2B-компании. Твоя задача — определить,
получил ли клиент именно тот результат, который запрашивал.

ЖЁСТКИЕ ПРАВИЛА:
1. Разбивай обращение клиента на ОТДЕЛЬНЫЕ задачи. Одно письмо может содержать несколько задач
   (например: цена, наличие, срок поставки, счёт, доставка) — каждая проверяется отдельно.
2. Задача считается выполненной ТОЛЬКО если клиент реально получил запрошенный результат.
   - Просили счёт → счёт должен быть приложен файлом, отправлен по ссылке или явно указано,
     что счёт выставлен и направлен. Сообщение цены счёт НЕ заменяет.
   - Просили КП → должно быть приложено КП или явно сказано, что КП направлено.
   - Просили цену → должна быть конкретная цифра. «Уточняем» — не выполнено.
   - Просили наличие → должно быть явно: в наличии / нет в наличии / под заказ / ожидается / количество.
   - Просили срок → конкретный срок, дата или диапазон дней. «Скоро» — не выполнено.
   - Просили доставку → способ, стоимость или срок доставки. Просто «доставим» — не выполнено.
   - Просили аналог → конкретная модель или артикул. «Можем подобрать аналог» — не выполнено.
   - Просили документы → должны быть вложения или рабочая ссылка.
3. НИЧЕГО НЕ ВЫДУМЫВАЙ. Не придумывай товары, цены, количества, сроки, имена, номера счетов.
   Каждый вывод о выполнении подтверждай короткой ДОСЛОВНОЙ цитатой из письма и его идентификатором
   (M1, M2, ...). Если подтверждения нет — задача не выполнена.
4. Вложение не является счётом только из-за расширения файла. Учитывай ИМЯ файла.
   Содержимое файлов тебе недоступно — если по имени нельзя понять, ставь needs_manual_review=true.
5. Никогда не утверждай, когда письмо было прочитано — этих данных нет.
6. Если запрос непонятен, цепочка выглядит склеенной из разных обращений или данных не хватает —
   ставь статус NEEDS_REVIEW и needs_manual_review=true.

СТАТУСЫ:
- CRITICAL — входящее письмо требует ответа, но ответа нет; последнее письмо от клиента без реакции;
  клиент написал повторно, не получив ответа.
- INCOMPLETE — ответ есть, но хотя бы одна задача клиента не выполнена.
- WAITING_FOR_CLIENT — менеджер корректно запросил недостающие данные (реквизиты, количество, адрес,
  модель) или предложил вариант и ждёт решения клиента. Это НЕ ошибка менеджера.
- COMPLETED — все задачи клиента выполнены, незакрытых вопросов нет.
- NOT_RELEVANT — реклама, массовая рассылка, автоматическое уведомление, спам, письмо не требует ответа.
- NEEDS_REVIEW — модель не уверена, данных недостаточно, цепочка объединена неоднозначно.

Отвечай только на русском языке, кроме значений полей type и status."""

USER_TEMPLATE = """Проанализируй переписку и верни результат по заданной схеме.

{context}

Дополнительные факты (из метаданных, не из текста):
{facts}
"""


@dataclass
class AIResult:
    analysis: ThreadAnalysis
    source: str                # "ai" | "offline"
    model: str | None = None
    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0
    error: str | None = None
    raw_json: str | None = None


class OpenAIAnalyzer:
    """Обёртка над OpenAI API с обработкой ошибок и подсчётом токенов."""

    def __init__(self, api_key: str | None = None, model: str | None = None, base_url: str | None = None):
        self.api_key = api_key if api_key is not None else settings.openai_api_key
        self.model = model or settings.openai_model or "gpt-4o-mini"
        self.base_url = base_url if base_url is not None else (settings.openai_base_url or None)
        self._client = None

    @property
    def available(self) -> bool:
        return bool(self.api_key)

    def _get_client(self):
        if self._client is None:
            from openai import OpenAI  # импорт по требованию

            kwargs: dict = {"api_key": self.api_key, "timeout": 90.0, "max_retries": 2}
            if self.base_url:
                kwargs["base_url"] = self.base_url
            self._client = OpenAI(**kwargs)
        return self._client

    def analyze(self, context: ThreadContext, facts: str = "") -> AIResult:
        """Отправляет цепочку в OpenAI и возвращает разобранный результат."""
        if not self.available:
            return AIResult(
                analysis=ThreadAnalysis(),
                source="error",
                error="OPENAI_API_KEY не задан",
            )

        prompt = USER_TEMPLATE.format(context=context.to_prompt(), facts=facts or "нет")
        started = time.monotonic()
        try:
            client = self._get_client()
            response = client.chat.completions.create(
                model=self.model,
                messages=[
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user", "content": prompt},
                ],
                response_format={"type": "json_schema", "json_schema": OPENAI_JSON_SCHEMA},
                temperature=0,
            )
        except Exception as exc:  # сеть, лимиты, авторизация, недоступная модель
            error = _describe_openai_error(exc)
            logger.warning("Ошибка OpenAI при анализе цепочки %s: %s", context.thread_id, error)
            return AIResult(analysis=ThreadAnalysis(), source="error", model=self.model, error=error)

        duration = time.monotonic() - started
        usage = getattr(response, "usage", None)
        prompt_tokens = getattr(usage, "prompt_tokens", 0) or 0
        completion_tokens = getattr(usage, "completion_tokens", 0) or 0
        total_tokens = getattr(usage, "total_tokens", 0) or (prompt_tokens + completion_tokens)

        try:
            content = response.choices[0].message.content or "{}"
        except (AttributeError, IndexError):
            return AIResult(
                analysis=ThreadAnalysis(),
                source="error",
                model=self.model,
                error="Пустой ответ OpenAI",
            )

        try:
            payload = json.loads(content)
        except json.JSONDecodeError as exc:
            logger.warning("OpenAI вернул некорректный JSON для цепочки %s: %s", context.thread_id, exc)
            return AIResult(
                analysis=ThreadAnalysis(),
                source="error",
                model=self.model,
                error=f"Некорректный JSON: {exc}",
                prompt_tokens=prompt_tokens,
                completion_tokens=completion_tokens,
                total_tokens=total_tokens,
                raw_json=content[:4000],
            )

        try:
            analysis = ThreadAnalysis.model_validate(payload)
        except Exception as exc:
            logger.warning("Ответ OpenAI не прошёл валидацию (цепочка %s): %s", context.thread_id, exc)
            return AIResult(
                analysis=ThreadAnalysis(),
                source="error",
                model=self.model,
                error=f"Ответ не соответствует схеме: {exc}",
                prompt_tokens=prompt_tokens,
                completion_tokens=completion_tokens,
                total_tokens=total_tokens,
                raw_json=content[:4000],
            )

        logger.info(
            "AI-анализ цепочки %s выполнен за %.1f с, токенов: %s",
            context.thread_id, duration, total_tokens,
        )
        return AIResult(
            analysis=analysis,
            source="ai",
            model=self.model,
            prompt_tokens=prompt_tokens,
            completion_tokens=completion_tokens,
            total_tokens=total_tokens,
            raw_json=json.dumps(payload, ensure_ascii=False),
        )


def _describe_openai_error(exc: Exception) -> str:
    """Понятное описание ошибки без утечки ключа."""
    name = type(exc).__name__
    text = str(exc)
    if len(text) > 300:
        text = text[:300] + "…"
    hints = {
        "AuthenticationError": "Неверный OPENAI_API_KEY",
        "RateLimitError": "Превышен лимит запросов или закончились средства на счёте OpenAI",
        "APITimeoutError": "Превышено время ожидания ответа OpenAI",
        "APIConnectionError": "Нет соединения с OpenAI (проверьте интернет и прокси)",
        "NotFoundError": "Модель недоступна для вашего ключа",
        "BadRequestError": "Запрос отклонён OpenAI",
        "PermissionDeniedError": "Доступ к модели запрещён",
    }
    hint = hints.get(name, name)
    return f"{hint}: {text}"


def validate_ai_result(analysis: ThreadAnalysis) -> tuple[ThreadAnalysis, list[str]]:
    """Защита от выдуманных выводов.

    Если задача помечена выполненной, но подтверждений нет — снимаем отметку
    и отправляем обращение на ручную проверку.
    """
    warnings: list[str] = []
    for item in analysis.customer_requests:
        if item.fulfilled and not item.evidence:
            item.fulfilled = False
            item.missing_information = (
                item.missing_information or "Модель не привела подтверждения выполнения"
            )
            warnings.append(f"Запрос «{item.type}» помечен выполненным без подтверждения")
    if warnings:
        analysis.needs_manual_review = True
        analysis.confidence = min(analysis.confidence, 0.5)
        # Пересчитываем статус, если после проверки остались невыполненные задачи
        if analysis.status == "COMPLETED" and any(
            not i.fulfilled for i in analysis.customer_requests
        ):
            analysis.status = "INCOMPLETE"
            analysis.status_reason = (
                (analysis.status_reason or "")
                + " Часть задач не подтверждена цитатами из переписки."
            ).strip()
    return analysis, warnings


PROMPT_VERSION_EXPORT = PROMPT_VERSION
