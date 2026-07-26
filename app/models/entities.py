"""Модели данных (SQLAlchemy 2.0)."""

from __future__ import annotations

from datetime import datetime, timezone

from sqlalchemy import (
    Boolean,
    DateTime,
    Float,
    ForeignKey,
    Index,
    Integer,
    String,
    Text,
    UniqueConstraint,
)
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.core.constants import Direction, ThreadStatus
from app.core.db import Base


def utcnow() -> datetime:
    return datetime.now(timezone.utc).replace(tzinfo=None)


class MailAccount(Base):
    """Почтовый ящик. Пароль в базе НЕ хранится — только ссылка на источник секрета."""

    __tablename__ = "mail_accounts"

    id: Mapped[int] = mapped_column(primary_key=True)
    email: Mapped[str] = mapped_column(String(320), unique=True, index=True)
    display_name: Mapped[str | None] = mapped_column(String(255))
    imap_host: Mapped[str] = mapped_column(String(255), default="imap.yandex.ru")
    imap_port: Mapped[int] = mapped_column(Integer, default=993)
    # Откуда берётся пароль: env:YANDEX_APP_PASSWORD (сейчас) или keyring:/oauth: (в будущем)
    secret_ref: Mapped[str] = mapped_column(String(128), default="env:YANDEX_APP_PASSWORD")
    auth_type: Mapped[str] = mapped_column(String(32), default="app_password")
    enabled: Mapped[bool] = mapped_column(Boolean, default=True)
    is_demo: Mapped[bool] = mapped_column(Boolean, default=False)
    last_sync_at: Mapped[datetime | None] = mapped_column(DateTime)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)
    updated_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow, onupdate=utcnow)

    folders: Mapped[list["MailFolder"]] = relationship(
        back_populates="account", cascade="all, delete-orphan"
    )


class MailFolder(Base):
    __tablename__ = "mail_folders"
    __table_args__ = (UniqueConstraint("account_id", "folder_name", name="uq_folder_account_name"),)

    id: Mapped[int] = mapped_column(primary_key=True)
    account_id: Mapped[int] = mapped_column(ForeignKey("mail_accounts.id", ondelete="CASCADE"))
    folder_name: Mapped[str] = mapped_column(String(512))
    display_name: Mapped[str | None] = mapped_column(String(512))
    folder_type: Mapped[str] = mapped_column(String(32), default="custom")
    enabled: Mapped[bool] = mapped_column(Boolean, default=False)
    last_uid: Mapped[int] = mapped_column(Integer, default=0)
    uid_validity: Mapped[int | None] = mapped_column(Integer)
    last_sync_at: Mapped[datetime | None] = mapped_column(DateTime)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    account: Mapped[MailAccount] = relationship(back_populates="folders")


class Thread(Base):
    """Клиентское обращение — цепочка переписки."""

    __tablename__ = "threads"

    id: Mapped[int] = mapped_column(primary_key=True)
    account_id: Mapped[int | None] = mapped_column(ForeignKey("mail_accounts.id", ondelete="CASCADE"))
    thread_key: Mapped[str] = mapped_column(String(512), index=True)
    normalized_subject: Mapped[str] = mapped_column(String(998), default="")
    subject: Mapped[str] = mapped_column(String(998), default="")
    client_email: Mapped[str | None] = mapped_column(String(320), index=True)
    client_name: Mapped[str | None] = mapped_column(String(255))
    company_name: Mapped[str | None] = mapped_column(String(255))

    first_message_at: Mapped[datetime | None] = mapped_column(DateTime, index=True)
    last_message_at: Mapped[datetime | None] = mapped_column(DateTime, index=True)
    last_message_direction: Mapped[str] = mapped_column(String(16), default=Direction.UNKNOWN)
    last_incoming_at: Mapped[datetime | None] = mapped_column(DateTime)
    last_outgoing_at: Mapped[datetime | None] = mapped_column(DateTime)
    first_response_at: Mapped[datetime | None] = mapped_column(DateTime)

    assigned_manager: Mapped[str | None] = mapped_column(String(320))
    first_responder: Mapped[str | None] = mapped_column(String(320))
    last_responder: Mapped[str | None] = mapped_column(String(320))

    message_count: Mapped[int] = mapped_column(Integer, default=0)
    incoming_count: Mapped[int] = mapped_column(Integer, default=0)
    outgoing_count: Mapped[int] = mapped_column(Integer, default=0)
    has_attachments: Mapped[bool] = mapped_column(Boolean, default=False)
    has_outgoing_attachments: Mapped[bool] = mapped_column(Boolean, default=False)

    # Технические (без AI) признаки
    tech_status: Mapped[str] = mapped_column(String(32), default=ThreadStatus.NEW)
    has_reply: Mapped[bool] = mapped_column(Boolean, default=False)
    is_read_unanswered: Mapped[bool] = mapped_column(Boolean, default=False)
    has_unread_incoming: Mapped[bool] = mapped_column(Boolean, default=False)
    waiting_seconds: Mapped[int] = mapped_column(Integer, default=0)
    waiting_business_seconds: Mapped[int] = mapped_column(Integer, default=0)
    first_response_seconds: Mapped[int | None] = mapped_column(Integer)
    first_response_business_seconds: Mapped[int | None] = mapped_column(Integer)
    is_repeat_request: Mapped[bool] = mapped_column(Boolean, default=False)
    sla_hours: Mapped[float | None] = mapped_column(Float)
    sla_exceeded: Mapped[bool] = mapped_column(Boolean, default=False)

    # Итоговый статус (AI + правила + ручная корректировка)
    current_status: Mapped[str] = mapped_column(String(32), default=ThreadStatus.NEW, index=True)
    status_reason: Mapped[str | None] = mapped_column(Text)
    next_action: Mapped[str | None] = mapped_column(Text)
    summary: Mapped[str | None] = mapped_column(Text)
    responsible_party: Mapped[str | None] = mapped_column(String(32))
    ai_confidence: Mapped[float | None] = mapped_column(Float)
    needs_review: Mapped[bool] = mapped_column(Boolean, default=False)
    grouping_confidence: Mapped[float] = mapped_column(Float, default=1.0)
    is_automated: Mapped[bool] = mapped_column(Boolean, default=False)
    exclusion_reason: Mapped[str | None] = mapped_column(String(255))
    manual_override: Mapped[bool] = mapped_column(Boolean, default=False)
    analyzed_content_hash: Mapped[str | None] = mapped_column(String(64))
    last_analyzed_at: Mapped[datetime | None] = mapped_column(DateTime)
    analysis_source: Mapped[str | None] = mapped_column(String(32))  # rules | ai | offline | manual

    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)
    updated_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow, onupdate=utcnow)

    messages: Mapped[list["Message"]] = relationship(
        back_populates="thread", order_by="Message.sent_at"
    )
    requests: Mapped[list["CustomerRequest"]] = relationship(
        back_populates="thread", cascade="all, delete-orphan"
    )
    analyses: Mapped[list["AnalysisResult"]] = relationship(
        back_populates="thread", cascade="all, delete-orphan"
    )
    reviews: Mapped[list["ManualReview"]] = relationship(
        back_populates="thread", cascade="all, delete-orphan"
    )


class Message(Base):
    __tablename__ = "messages"
    __table_args__ = (
        UniqueConstraint("account_id", "folder_id", "imap_uid", name="uq_message_folder_uid"),
        Index("ix_messages_msgid", "message_id_header"),
        Index("ix_messages_sent_at", "sent_at"),
        Index("ix_messages_thread", "thread_id"),
    )

    id: Mapped[int] = mapped_column(primary_key=True)
    account_id: Mapped[int] = mapped_column(ForeignKey("mail_accounts.id", ondelete="CASCADE"))
    folder_id: Mapped[int | None] = mapped_column(ForeignKey("mail_folders.id", ondelete="SET NULL"))
    imap_uid: Mapped[int | None] = mapped_column(Integer)

    message_id_header: Mapped[str | None] = mapped_column(String(998))
    in_reply_to: Mapped[str | None] = mapped_column(String(998))
    references: Mapped[str | None] = mapped_column(Text)
    thread_id: Mapped[int | None] = mapped_column(ForeignKey("threads.id", ondelete="SET NULL"))

    subject: Mapped[str] = mapped_column(String(998), default="")
    normalized_subject: Mapped[str] = mapped_column(String(998), default="")
    sender_name: Mapped[str | None] = mapped_column(String(255))
    sender_email: Mapped[str] = mapped_column(String(320), default="", index=True)
    recipients: Mapped[str | None] = mapped_column(Text)  # JSON-список
    cc: Mapped[str | None] = mapped_column(Text)          # JSON-список
    direction: Mapped[str] = mapped_column(String(16), default=Direction.UNKNOWN)

    sent_at: Mapped[datetime | None] = mapped_column(DateTime)
    received_at: Mapped[datetime | None] = mapped_column(DateTime)
    is_seen: Mapped[bool] = mapped_column(Boolean, default=False)
    is_answered_flag: Mapped[bool] = mapped_column(Boolean, default=False)

    body_text: Mapped[str | None] = mapped_column(Text)
    body_html: Mapped[str | None] = mapped_column(Text)
    clean_text: Mapped[str | None] = mapped_column(Text)
    content_hash: Mapped[str] = mapped_column(String(64), index=True, default="")
    has_attachments: Mapped[bool] = mapped_column(Boolean, default=False)

    is_automated: Mapped[bool] = mapped_column(Boolean, default=False)
    exclusion_reason: Mapped[str | None] = mapped_column(String(255))
    auto_headers: Mapped[str | None] = mapped_column(Text)  # JSON служебных заголовков
    size_bytes: Mapped[int | None] = mapped_column(Integer)
    parse_error: Mapped[str | None] = mapped_column(String(512))

    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    thread: Mapped[Thread | None] = relationship(back_populates="messages")
    attachments: Mapped[list["Attachment"]] = relationship(
        back_populates="message", cascade="all, delete-orphan"
    )


class Attachment(Base):
    __tablename__ = "attachments"

    id: Mapped[int] = mapped_column(primary_key=True)
    message_id: Mapped[int] = mapped_column(ForeignKey("messages.id", ondelete="CASCADE"))
    filename: Mapped[str] = mapped_column(String(512), default="")
    mime_type: Mapped[str | None] = mapped_column(String(255))
    size: Mapped[int | None] = mapped_column(Integer)
    local_path: Mapped[str | None] = mapped_column(String(1024))
    attachment_category: Mapped[str] = mapped_column(String(32), default="other")
    is_inline: Mapped[bool] = mapped_column(Boolean, default=False)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    message: Mapped[Message] = relationship(back_populates="attachments")


class CustomerRequest(Base):
    """Отдельная задача клиента внутри обращения."""

    __tablename__ = "customer_requests"

    id: Mapped[int] = mapped_column(primary_key=True)
    thread_id: Mapped[int] = mapped_column(ForeignKey("threads.id", ondelete="CASCADE"), index=True)
    request_type: Mapped[str] = mapped_column(String(48), default="other")
    description: Mapped[str] = mapped_column(Text, default="")
    fulfilled: Mapped[bool] = mapped_column(Boolean, default=False)
    evidence: Mapped[str | None] = mapped_column(Text)  # JSON: [{message_id, quote}]
    missing_information: Mapped[str | None] = mapped_column(Text)
    source: Mapped[str] = mapped_column(String(16), default="ai")  # ai | rules | manual
    manual_override: Mapped[bool] = mapped_column(Boolean, default=False)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    thread: Mapped[Thread] = relationship(back_populates="requests")


class AnalysisResult(Base):
    __tablename__ = "analysis_results"

    id: Mapped[int] = mapped_column(primary_key=True)
    thread_id: Mapped[int] = mapped_column(ForeignKey("threads.id", ondelete="CASCADE"), index=True)
    model: Mapped[str | None] = mapped_column(String(128))
    prompt_version: Mapped[str | None] = mapped_column(String(32))
    content_hash: Mapped[str] = mapped_column(String(64), index=True, default="")
    result_json: Mapped[str | None] = mapped_column(Text)
    status: Mapped[str | None] = mapped_column(String(32))
    confidence: Mapped[float | None] = mapped_column(Float)
    token_usage: Mapped[int] = mapped_column(Integer, default=0)
    prompt_tokens: Mapped[int] = mapped_column(Integer, default=0)
    completion_tokens: Mapped[int] = mapped_column(Integer, default=0)
    source: Mapped[str] = mapped_column(String(16), default="ai")
    error: Mapped[str | None] = mapped_column(String(512))
    analyzed_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    thread: Mapped[Thread] = relationship(back_populates="analyses")


class ManualReview(Base):
    __tablename__ = "manual_reviews"

    id: Mapped[int] = mapped_column(primary_key=True)
    thread_id: Mapped[int] = mapped_column(ForeignKey("threads.id", ondelete="CASCADE"), index=True)
    previous_status: Mapped[str | None] = mapped_column(String(32))
    new_status: Mapped[str | None] = mapped_column(String(32))
    field: Mapped[str | None] = mapped_column(String(64))
    comment: Mapped[str | None] = mapped_column(Text)
    author: Mapped[str | None] = mapped_column(String(128), default="local-user")
    created_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)

    thread: Mapped[Thread] = relationship(back_populates="reviews")


class AppSetting(Base):
    __tablename__ = "app_settings"

    key: Mapped[str] = mapped_column(String(128), primary_key=True)
    value: Mapped[str | None] = mapped_column(Text)
    updated_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow, onupdate=utcnow)


class SyncRun(Base):
    """Журнал синхронизаций — ошибки не должны скрываться."""

    __tablename__ = "sync_runs"

    id: Mapped[int] = mapped_column(primary_key=True)
    account_id: Mapped[int | None] = mapped_column(Integer)
    started_at: Mapped[datetime] = mapped_column(DateTime, default=utcnow)
    finished_at: Mapped[datetime | None] = mapped_column(DateTime)
    status: Mapped[str] = mapped_column(String(32), default="running")
    folders_processed: Mapped[int] = mapped_column(Integer, default=0)
    messages_fetched: Mapped[int] = mapped_column(Integer, default=0)
    messages_new: Mapped[int] = mapped_column(Integer, default=0)
    messages_failed: Mapped[int] = mapped_column(Integer, default=0)
    threads_touched: Mapped[int] = mapped_column(Integer, default=0)
    error: Mapped[str | None] = mapped_column(Text)
    details: Mapped[str | None] = mapped_column(Text)
