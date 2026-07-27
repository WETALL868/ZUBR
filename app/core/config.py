"""Конфигурация приложения.

Секреты (пароли, API-ключи) читаются ТОЛЬКО из окружения/.env и никогда
не сохраняются в базу данных и не пишутся в логи.
"""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path

from pydantic import Field, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

BASE_DIR = Path(__file__).resolve().parent.parent.parent


def _split_list(value: str | list[str] | None) -> list[str]:
    if value is None:
        return []
    if isinstance(value, list):
        return [str(v).strip().lower() for v in value if str(v).strip()]
    return [part.strip().lower() for part in value.replace(";", ",").split(",") if part.strip()]


class Settings(BaseSettings):
    """Настройки, читаемые из .env."""

    model_config = SettingsConfigDict(
        env_file=str(BASE_DIR / ".env"),
        env_file_encoding="utf-8",
        extra="ignore",
        case_sensitive=False,
    )

    # --- IMAP ---
    yandex_imap_host: str = "imap.yandex.ru"
    yandex_imap_port: int = 993
    yandex_email: str = ""
    yandex_app_password: str = ""

    # --- OpenAI ---
    openai_api_key: str = ""
    openai_model: str = "gpt-4o-mini"
    openai_base_url: str = ""

    # --- База ---
    database_url: str = "sqlite:///./data/mail_assistant.db"

    # --- Режимы ---
    demo_mode: bool = False
    ai_enabled: bool = True
    ai_test_mode: bool = False
    ai_max_threads_per_run: int = 50
    read_only_mode: bool = True

    # --- Вложения ---
    save_attachments: bool = False
    attachments_dir: str = "./data/attachments"
    max_attachment_size_mb: int = 25

    # --- Веб ---
    app_host: str = "127.0.0.1"
    app_port: int = 8000
    open_browser: bool = True

    # --- Логи ---
    log_level: str = "INFO"
    log_dir: str = "./logs"

    # --- Организация ---
    corporate_domains: str = ""
    corporate_emails: str = ""
    timezone: str = "Europe/Moscow"

    @field_validator("read_only_mode")
    @classmethod
    def _force_read_only(cls, v: bool) -> bool:
        # Первая версия работает строго в режиме чтения. Отключить нельзя.
        return True

    # ---------- производные свойства ----------

    @property
    def corporate_domain_list(self) -> list[str]:
        return _split_list(self.corporate_domains)

    @property
    def corporate_email_list(self) -> list[str]:
        emails = _split_list(self.corporate_emails)
        if self.yandex_email:
            own = self.yandex_email.strip().lower()
            if own and own not in emails:
                emails.append(own)
        return emails

    @property
    def attachments_path(self) -> Path:
        p = Path(self.attachments_dir)
        return p if p.is_absolute() else (BASE_DIR / p).resolve()

    @property
    def log_path(self) -> Path:
        p = Path(self.log_dir)
        return p if p.is_absolute() else (BASE_DIR / p).resolve()

    @property
    def sqlite_file(self) -> Path | None:
        if not self.database_url.startswith("sqlite"):
            return None
        raw = self.database_url.split("///", 1)[-1]
        p = Path(raw)
        return p if p.is_absolute() else (BASE_DIR / p).resolve()

    # ---------- проверки ----------

    def imap_configured(self) -> bool:
        return bool(self.yandex_email and self.yandex_app_password and self.yandex_imap_host)

    def openai_configured(self) -> bool:
        return bool(self.openai_api_key)

    def validate_startup(self) -> list[str]:
        """Возвращает список предупреждений о конфигурации (не блокирует запуск)."""
        problems: list[str] = []
        if not self.demo_mode:
            if not self.yandex_email:
                problems.append(
                    "Почта не подключена. Откройте раздел «Подключение почты» "
                    "и укажите адрес и пароль приложения."
                )
            elif "@" not in self.yandex_email:
                problems.append(f"YANDEX_EMAIL выглядит некорректно: {self.yandex_email}")
            if not self.yandex_app_password:
                problems.append(
                    "Не указан пароль приложения Яндекса — задайте его в разделе "
                    "«Подключение почты»."
                )
            elif len(self.yandex_app_password) < 8:
                problems.append(
                    "YANDEX_APP_PASSWORD слишком короткий — похоже, это не пароль приложения."
                )
        if self.ai_enabled and not self.ai_test_mode and not self.openai_api_key:
            problems.append(
                "Ключ OpenAI не указан — анализ выполняется встроенными правилами. "
                "Это бесплатно; ключ можно добавить в разделе «Подключение почты»."
            )
        if not self.corporate_domain_list and not self.corporate_email_list:
            problems.append(
                "Не указан домен вашей компании — программа не сможет отличить письма "
                "сотрудников от клиентских. Задайте его в разделе «Подключение почты»."
            )
        return problems


@lru_cache
def get_settings() -> Settings:
    return Settings()


settings = get_settings()
