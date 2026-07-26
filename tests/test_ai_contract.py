"""Разбор и проверка ответа AI, защита от выдуманных выводов."""

from __future__ import annotations

import json

import pytest

from app.core.constants import ThreadStatus
from app.services.ai_analyzer import OpenAIAnalyzer, _describe_openai_error, validate_ai_result
from app.services.analysis_schema import OPENAI_JSON_SCHEMA, ThreadAnalysis

VALID_PAYLOAD = {
    "thread_summary": "Клиент запросил счёт, цену, наличие и доставку.",
    "customer_requests": [
        {
            "type": "price",
            "description": "Сообщить цену на 10 единиц товара",
            "fulfilled": True,
            "evidence": [{"message_id": "M2", "quote": "Цена составляет 500 рублей за штуку"}],
            "missing_information": None,
        },
        {
            "type": "invoice",
            "description": "Выставить счёт",
            "fulfilled": False,
            "evidence": [],
            "missing_information": "Счёт или ссылка на счёт отсутствует",
        },
    ],
    "status": "INCOMPLETE",
    "status_reason": "Цена указана, но счёт не отправлен.",
    "next_action": "Подготовить и отправить счёт.",
    "responsible_party": "manager",
    "confidence": 0.93,
    "needs_manual_review": False,
}


def test_parse_valid_payload():
    analysis = ThreadAnalysis.model_validate(VALID_PAYLOAD)
    assert analysis.status == ThreadStatus.INCOMPLETE
    assert len(analysis.customer_requests) == 2
    assert analysis.customer_requests[0].fulfilled is True
    assert analysis.confidence == pytest.approx(0.93)


def test_unknown_status_becomes_needs_review():
    payload = dict(VALID_PAYLOAD, status="СУПЕР_СТАТУС")
    assert ThreadAnalysis.model_validate(payload).status == ThreadStatus.NEEDS_REVIEW


def test_unknown_request_type_becomes_other():
    payload = json.loads(json.dumps(VALID_PAYLOAD))
    payload["customer_requests"][0]["type"] = "выдуманный_тип"
    assert ThreadAnalysis.model_validate(payload).customer_requests[0].type == "other"


def test_confidence_is_clamped():
    assert ThreadAnalysis.model_validate(dict(VALID_PAYLOAD, confidence=5)).confidence == 1.0
    assert ThreadAnalysis.model_validate(dict(VALID_PAYLOAD, confidence=-2)).confidence == 0.0
    assert ThreadAnalysis.model_validate(dict(VALID_PAYLOAD, confidence="abc")).confidence == 0.5


def test_empty_payload_does_not_crash():
    analysis = ThreadAnalysis.model_validate({})
    assert analysis.status == ThreadStatus.NEEDS_REVIEW
    assert analysis.customer_requests == []


def test_fulfilled_without_evidence_is_rejected():
    """Защита от выдуманных выводов: без подтверждения задача не считается выполненной."""
    payload = json.loads(json.dumps(VALID_PAYLOAD))
    payload["customer_requests"][0]["evidence"] = []
    payload["status"] = "COMPLETED"
    payload["customer_requests"][1]["fulfilled"] = True
    payload["customer_requests"][1]["evidence"] = []

    analysis, warnings = validate_ai_result(ThreadAnalysis.model_validate(payload))
    assert len(warnings) == 2
    assert all(not item.fulfilled for item in analysis.customer_requests)
    assert analysis.needs_manual_review is True
    assert analysis.status == ThreadStatus.INCOMPLETE
    assert analysis.confidence <= 0.5


def test_valid_result_passes_validation_unchanged():
    analysis, warnings = validate_ai_result(ThreadAnalysis.model_validate(VALID_PAYLOAD))
    assert warnings == []
    assert analysis.customer_requests[0].fulfilled is True


def test_json_schema_is_strict():
    schema = OPENAI_JSON_SCHEMA["schema"]
    assert OPENAI_JSON_SCHEMA["strict"] is True
    assert schema["additionalProperties"] is False
    assert set(schema["required"]) == set(schema["properties"].keys())
    item = schema["properties"]["customer_requests"]["items"]
    assert item["additionalProperties"] is False
    assert set(item["required"]) == set(item["properties"].keys())


def test_analyzer_without_key_returns_error():
    analyzer = OpenAIAnalyzer(api_key="", model="gpt-4o-mini")
    assert analyzer.available is False


def test_error_descriptions_are_readable():
    class RateLimitError(Exception):
        pass

    message = _describe_openai_error(RateLimitError("429 too many requests"))
    assert "лимит" in message.lower()


def test_error_message_is_truncated():
    class APIConnectionError(Exception):
        pass

    message = _describe_openai_error(APIConnectionError("x" * 5000))
    assert len(message) < 400
