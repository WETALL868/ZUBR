"""Нормализация темы письма."""

from __future__ import annotations

import re

# Служебные префиксы, в т.ч. с номерами вида Re[2]:
_PREFIXES = [
    "re", "rе",           # латиница и кириллическая «е»
    "fwd", "fw",
    "ответ", "отв",
    "пересылка", "перенаправлено", "переслано",
    "aw", "antw", "sv", "vs", "rif", "res", "enc", "réf",
]

_PREFIX_RE = re.compile(
    r"^\s*(?:\[[^\]]{0,40}\]\s*)?(?:(?:%s)\s*(?:\[\d+\]|\(\d+\))?\s*:\s*)" % "|".join(_PREFIXES),
    re.IGNORECASE,
)

_WHITESPACE_RE = re.compile(r"\s+")
_TRACKING_RE = re.compile(r"\[(?:ticket|тикет|заявка)\s*[#№]?\s*\d+\]", re.IGNORECASE)


def strip_prefixes(subject: str) -> str:
    """Удаляет все служебные префиксы (Re:, Fwd:, Ответ: и т.п.), в том числе повторяющиеся."""
    if not subject:
        return ""
    result = subject
    for _ in range(12):  # защита от бесконечного цикла
        new = _PREFIX_RE.sub("", result, count=1)
        if new == result:
            break
        result = new
    return result.strip()


def normalize_subject(subject: str | None) -> str:
    """Приводит тему к каноническому виду для сравнения цепочек."""
    if not subject:
        return ""
    text = strip_prefixes(subject)
    text = _TRACKING_RE.sub(" ", text)
    text = text.replace(" ", " ")
    text = _WHITESPACE_RE.sub(" ", text).strip().lower()
    # Убираем хвостовую пунктуацию, не влияющую на смысл
    text = text.strip(" .,;:!-—–")
    return text


def subject_similarity(a: str, b: str) -> float:
    """Простая мера схожести нормализованных тем (0..1) по совпадению слов."""
    if not a or not b:
        return 0.0
    if a == b:
        return 1.0
    tokens_a = {t for t in re.split(r"[^\w]+", a, flags=re.UNICODE) if len(t) > 2}
    tokens_b = {t for t in re.split(r"[^\w]+", b, flags=re.UNICODE) if len(t) > 2}
    if not tokens_a or not tokens_b:
        return 0.0
    inter = tokens_a & tokens_b
    union = tokens_a | tokens_b
    return len(inter) / len(union)
