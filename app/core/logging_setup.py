"""Настройка логирования с ротацией.

В логи запрещено писать: тексты писем, пароли, токены, вложения,
персональные данные без необходимости. Допустимо: внутренние ID, UID,
результаты синхронизации, коды ошибок, длительности, счётчики.
"""

from __future__ import annotations

import logging
import logging.handlers
import re
from pathlib import Path

from app.core.config import settings

_CONFIGURED = False

# Маскирование потенциальных секретов, если они случайно попали в сообщение.
_SECRET_PATTERNS = [
    re.compile(r"(?i)(password|passwd|pwd|token|api[_-]?key|authorization)\s*[=:]\s*\S+"),
    re.compile(r"(?i)\bsk-[A-Za-z0-9_\-]{8,}"),
]


class SecretsFilter(logging.Filter):
    """Грубая защита от попадания секретов в лог-файл."""

    def filter(self, record: logging.LogRecord) -> bool:
        try:
            msg = record.getMessage()
        except Exception:  # pragma: no cover - защитный код
            return True
        masked = msg
        for pattern in _SECRET_PATTERNS:
            masked = pattern.sub("***скрыто***", masked)
        if masked != msg:
            record.msg = masked
            record.args = ()
        return True


def setup_logging() -> None:
    global _CONFIGURED
    if _CONFIGURED:
        return

    log_dir: Path = settings.log_path
    log_dir.mkdir(parents=True, exist_ok=True)

    level = getattr(logging, settings.log_level.upper(), logging.INFO)
    fmt = logging.Formatter(
        "%(asctime)s | %(levelname)-7s | %(name)-28s | %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    root = logging.getLogger()
    root.setLevel(level)

    file_handler = logging.handlers.RotatingFileHandler(
        log_dir / "app.log", maxBytes=5 * 1024 * 1024, backupCount=5, encoding="utf-8"
    )
    file_handler.setFormatter(fmt)
    file_handler.addFilter(SecretsFilter())

    console = logging.StreamHandler()
    console.setFormatter(fmt)
    console.addFilter(SecretsFilter())

    for handler in list(root.handlers):
        root.removeHandler(handler)
    root.addHandler(file_handler)
    root.addHandler(console)

    # Библиотеки не должны засорять лог.
    for noisy in ("httpx", "httpcore", "openai", "urllib3", "asyncio", "multipart"):
        logging.getLogger(noisy).setLevel(logging.WARNING)

    _CONFIGURED = True


def get_logger(name: str) -> logging.Logger:
    setup_logging()
    return logging.getLogger(name)
