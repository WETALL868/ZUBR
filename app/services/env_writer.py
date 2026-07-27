"""Запись настроек подключения в файл .env из интерфейса программы.

Пароли и ключи по-прежнему хранятся только в .env — в базу данных они
не попадают и в журналы не пишутся.
"""

from __future__ import annotations

from pathlib import Path

from app.core.config import BASE_DIR, get_settings, settings
from app.core.logging_setup import get_logger

logger = get_logger(__name__)

ENV_PATH = BASE_DIR / ".env"
EXAMPLE_PATH = BASE_DIR / ".env.example"

# Значения этих ключей никогда не выводятся в логи
SECRET_KEYS = {"YANDEX_APP_PASSWORD", "OPENAI_API_KEY"}


def _read_lines() -> list[str]:
    if ENV_PATH.exists():
        return ENV_PATH.read_text(encoding="utf-8").splitlines()
    if EXAMPLE_PATH.exists():
        return EXAMPLE_PATH.read_text(encoding="utf-8").splitlines()
    return []


def update_env(values: dict[str, str]) -> None:
    """Обновляет ключи в .env, сохраняя остальные строки и комментарии."""
    lines = _read_lines()
    remaining = dict(values)

    for index, line in enumerate(lines):
        stripped = line.strip()
        if not stripped or stripped.startswith("#") or "=" not in stripped:
            continue
        key = stripped.split("=", 1)[0].strip()
        if key in remaining:
            lines[index] = f"{key}={remaining.pop(key)}"

    for key, value in remaining.items():
        lines.append(f"{key}={value}")

    ENV_PATH.write_text("\n".join(lines).rstrip() + "\n", encoding="utf-8")
    logger.info(
        "Файл .env обновлён, изменены ключи: %s",
        ", ".join(k if k not in SECRET_KEYS else f"{k} (значение скрыто)" for k in values),
    )


def apply_to_running_app(values: dict[str, str]) -> None:
    """Применяет настройки к запущенной программе, чтобы не требовать перезапуск."""
    mapping = {
        "YANDEX_EMAIL": "yandex_email",
        "YANDEX_APP_PASSWORD": "yandex_app_password",
        "YANDEX_IMAP_HOST": "yandex_imap_host",
        "OPENAI_API_KEY": "openai_api_key",
        "OPENAI_MODEL": "openai_model",
        "CORPORATE_DOMAINS": "corporate_domains",
    }
    for env_key, attr in mapping.items():
        if env_key in values:
            value = values[env_key]
            if attr == "yandex_imap_port":
                continue
            setattr(settings, attr, value)
    get_settings.cache_clear()


def save_connection_settings(
    email: str,
    password: str | None,
    corporate_domains: str = "",
    openai_key: str | None = None,
    openai_model: str | None = None,
) -> dict[str, str]:
    """Сохраняет настройки подключения и сразу применяет их."""
    values: dict[str, str] = {"YANDEX_EMAIL": email.strip()}
    if password:
        values["YANDEX_APP_PASSWORD"] = password.strip()
    if corporate_domains:
        values["CORPORATE_DOMAINS"] = corporate_domains.strip()
    if openai_key is not None:
        values["OPENAI_API_KEY"] = openai_key.strip()
    if openai_model:
        values["OPENAI_MODEL"] = openai_model.strip()

    update_env(values)
    apply_to_running_app(values)
    return values


def env_file_exists() -> bool:
    return ENV_PATH.exists()
