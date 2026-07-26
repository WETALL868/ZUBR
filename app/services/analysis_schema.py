"""Схема результата анализа переписки (общая для AI и офлайн-анализатора)."""

from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, Field, field_validator

from app.core.constants import ALL_REQUEST_TYPES, ThreadStatus

PROMPT_VERSION = "1.0"

VALID_STATUSES = [
    ThreadStatus.CRITICAL,
    ThreadStatus.INCOMPLETE,
    ThreadStatus.WAITING_FOR_CLIENT,
    ThreadStatus.COMPLETED,
    ThreadStatus.NOT_RELEVANT,
    ThreadStatus.NEEDS_REVIEW,
]


class Evidence(BaseModel):
    message_id: str = ""
    quote: str = ""

    @field_validator("quote")
    @classmethod
    def _trim(cls, v: str) -> str:
        return (v or "")[:400]


class CustomerRequestItem(BaseModel):
    type: str = "other"
    description: str = ""
    fulfilled: bool = False
    evidence: list[Evidence] = Field(default_factory=list)
    missing_information: str | None = None

    @field_validator("type")
    @classmethod
    def _known_type(cls, v: str) -> str:
        v = (v or "other").strip().lower()
        return v if v in ALL_REQUEST_TYPES else "other"


class ThreadAnalysis(BaseModel):
    thread_summary: str = ""
    customer_requests: list[CustomerRequestItem] = Field(default_factory=list)
    status: str = ThreadStatus.NEEDS_REVIEW
    status_reason: str = ""
    next_action: str = ""
    responsible_party: Literal["manager", "client", "none"] = "manager"
    confidence: float = 0.5
    needs_manual_review: bool = False

    @field_validator("status")
    @classmethod
    def _known_status(cls, v: str) -> str:
        v = (v or "").strip().upper()
        return v if v in VALID_STATUSES else ThreadStatus.NEEDS_REVIEW

    @field_validator("confidence", mode="before")
    @classmethod
    def _clamp(cls, v: float) -> float:
        try:
            v = float(v)
        except (TypeError, ValueError):
            return 0.5
        return max(0.0, min(1.0, v))


# JSON Schema для structured output OpenAI (strict-режим требует
# перечисления всех полей в required и additionalProperties=false)
OPENAI_JSON_SCHEMA: dict[str, Any] = {
    "name": "thread_analysis",
    "strict": True,
    "schema": {
        "type": "object",
        "additionalProperties": False,
        "required": [
            "thread_summary",
            "customer_requests",
            "status",
            "status_reason",
            "next_action",
            "responsible_party",
            "confidence",
            "needs_manual_review",
        ],
        "properties": {
            "thread_summary": {
                "type": "string",
                "description": "Краткое содержание обращения на русском языке, 1-2 предложения.",
            },
            "customer_requests": {
                "type": "array",
                "description": "Каждая отдельная задача клиента. Пустой массив, если запросов нет.",
                "items": {
                    "type": "object",
                    "additionalProperties": False,
                    "required": [
                        "type",
                        "description",
                        "fulfilled",
                        "evidence",
                        "missing_information",
                    ],
                    "properties": {
                        "type": {"type": "string", "enum": ALL_REQUEST_TYPES},
                        "description": {
                            "type": "string",
                            "description": "Что именно попросил клиент, на русском языке.",
                        },
                        "fulfilled": {
                            "type": "boolean",
                            "description": "true только если клиент реально получил запрошенный результат.",
                        },
                        "evidence": {
                            "type": "array",
                            "description": "Подтверждения из переписки. Обязательны при fulfilled=true.",
                            "items": {
                                "type": "object",
                                "additionalProperties": False,
                                "required": ["message_id", "quote"],
                                "properties": {
                                    "message_id": {
                                        "type": "string",
                                        "description": "Идентификатор письма из входных данных (например M3).",
                                    },
                                    "quote": {
                                        "type": "string",
                                        "description": "Дословная короткая цитата из письма.",
                                    },
                                },
                            },
                        },
                        "missing_information": {
                            "type": ["string", "null"],
                            "description": "Чего не хватает, если запрос не выполнен.",
                        },
                    },
                },
            },
            "status": {"type": "string", "enum": list(VALID_STATUSES)},
            "status_reason": {
                "type": "string",
                "description": "Объяснение статуса на русском языке с опорой на факты переписки.",
            },
            "next_action": {
                "type": "string",
                "description": "Конкретное рекомендуемое следующее действие на русском языке.",
            },
            "responsible_party": {"type": "string", "enum": ["manager", "client", "none"]},
            "confidence": {"type": "number", "minimum": 0, "maximum": 1},
            "needs_manual_review": {"type": "boolean"},
        },
    },
}
