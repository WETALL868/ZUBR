"""Модуль действий с почтой — ОТКЛЮЧЁН в первой версии.

Здесь в будущем появятся операции, изменяющие состояние почтового ящика
(отправка писем, создание черновиков, пометка прочитанным, перемещение).
Сейчас любая попытка вызвать их приводит к исключению.

Отдельный модуль нужен, чтобы записывающие операции никогда не смешивались
с кодом чтения и анализа.
"""

from __future__ import annotations

from app.core.config import settings
from app.core.logging_setup import get_logger

logger = get_logger(__name__)

# Явный флаг. В первой версии всегда False и не выносится в .env как включаемый.
WRITE_ACTIONS_ENABLED = False


class MailActionsDisabled(RuntimeError):
    """Действие запрещено режимом только для чтения."""


def _forbidden(action: str) -> None:
    logger.warning("Заблокировано действие «%s»: программа работает в режиме чтения.", action)
    raise MailActionsDisabled(
        f"Действие «{action}» недоступно: программа работает строго в режиме чтения. "
        "Отправка, удаление, перемещение писем и изменение флагов отключены."
    )


def send_email(*args, **kwargs):
    _forbidden("отправка письма")


def create_draft(*args, **kwargs):
    _forbidden("создание черновика")


def mark_as_read(*args, **kwargs):
    _forbidden("пометка письма прочитанным")


def move_message(*args, **kwargs):
    _forbidden("перемещение письма")


def delete_message(*args, **kwargs):
    _forbidden("удаление письма")


def set_flag(*args, **kwargs):
    _forbidden("изменение флагов письма")


def is_write_allowed() -> bool:
    """Всегда False в первой версии."""
    return bool(WRITE_ACTIONS_ENABLED and not settings.read_only_mode)
