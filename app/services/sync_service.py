"""Синхронизация почты: загрузка писем в локальную базу (только чтение)."""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import date, datetime, timedelta

from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.core.config import settings
from app.core.constants import Direction, FolderType
from app.core.logging_setup import get_logger
from app.models import Attachment, MailAccount, MailFolder, Message, SyncRun, Thread, utcnow
from app.services import attachments as att_utils
from app.services.email_utils import detect_direction
from app.services.exclusions import classify_message
from app.services.imap_client import ImapError, RawMessage, YandexImapClient
from app.services.message_parser import ParsedMessage, parse_message, recipients_json
from app.services.settings_service import corporate_identity, get_all_settings
from app.services.threading_service import assign_thread, recompute_thread

logger = get_logger(__name__)


@dataclass
class SyncStats:
    folders_processed: int = 0
    messages_fetched: int = 0
    messages_new: int = 0
    messages_duplicate: int = 0
    messages_failed: int = 0
    threads_touched: set[int] = field(default_factory=set)
    errors: list[str] = field(default_factory=list)

    def as_dict(self) -> dict:
        return {
            "folders_processed": self.folders_processed,
            "messages_fetched": self.messages_fetched,
            "messages_new": self.messages_new,
            "messages_duplicate": self.messages_duplicate,
            "messages_failed": self.messages_failed,
            "threads_touched": len(self.threads_touched),
            "errors": self.errors[:20],
        }


# ------------------------------------------------------------------
# Учётная запись и папки
# ------------------------------------------------------------------

def get_or_create_account(db: Session, demo: bool = False) -> MailAccount:
    email_address = "demo@example.local" if demo else (settings.yandex_email or "not-configured@local")
    account = db.scalar(select(MailAccount).where(MailAccount.email == email_address))
    if account is None:
        account = MailAccount(
            email=email_address,
            display_name="Демонстрационный ящик" if demo else email_address,
            imap_host=settings.yandex_imap_host,
            imap_port=settings.yandex_imap_port,
            secret_ref="demo" if demo else "env:YANDEX_APP_PASSWORD",
            auth_type="demo" if demo else "app_password",
            is_demo=demo,
            enabled=True,
        )
        db.add(account)
        db.flush()
    return account


def discover_folders(db: Session, account: MailAccount, client: YandexImapClient) -> list[MailFolder]:
    """Читает список папок с сервера и сохраняет его. Включает Входящие и Отправленные."""
    remote = client.list_folders()
    existing = {f.folder_name: f for f in account.folders}
    result: list[MailFolder] = []
    first_run = not existing

    for info in remote:
        folder = existing.get(info.name)
        if folder is None:
            folder = MailFolder(
                account_id=account.id,
                folder_name=info.name,
                display_name=info.display_name,
                folder_type=info.folder_type,
                enabled=info.folder_type in {FolderType.INBOX, FolderType.SENT} if first_run else False,
            )
            db.add(folder)
        else:
            folder.display_name = info.display_name
            folder.folder_type = info.folder_type
        result.append(folder)
    db.flush()
    logger.info("IMAP: обнаружено папок: %s", len(result))
    return result


# ------------------------------------------------------------------
# Сохранение письма
# ------------------------------------------------------------------

def _message_exists(db: Session, account_id: int, parsed: ParsedMessage, folder_id: int) -> bool:
    """Защита от повторной загрузки."""
    if parsed.message_id:
        exists = db.scalar(
            select(func.count(Message.id)).where(
                Message.account_id == account_id,
                Message.message_id_header == parsed.message_id,
            )
        )
        if exists:
            return True
    if parsed.content_hash:
        exists = db.scalar(
            select(func.count(Message.id)).where(
                Message.account_id == account_id,
                Message.content_hash == parsed.content_hash,
            )
        )
        if exists:
            return True
    return False


def store_message(
    db: Session,
    account: MailAccount,
    folder: MailFolder,
    raw: RawMessage,
    parsed: ParsedMessage,
    domains: set[str],
    emails: set[str],
    settings_data: dict,
    save_attachments: bool = False,
) -> Message | None:
    """Сохраняет письмо и его вложения. Возвращает None, если письмо уже есть."""
    if _message_exists(db, account.id, parsed, folder.id):
        return None

    direction = detect_direction(parsed.sender_email, parsed.recipient_emails, domains, emails)
    # Папка «Отправленные» — надёжный признак исходящего письма
    if folder.folder_type == FolderType.SENT and direction in {Direction.UNKNOWN, Direction.INCOMING}:
        direction = Direction.OUTGOING

    is_automated, reason = classify_message(
        parsed.sender_email, parsed.subject, parsed.headers, folder.folder_type, settings_data
    )
    if direction == Direction.OUTGOING:
        is_automated, reason = False, None

    message = Message(
        account_id=account.id,
        folder_id=folder.id,
        imap_uid=raw.uid,
        message_id_header=parsed.message_id,
        in_reply_to=parsed.in_reply_to,
        references=parsed.references,
        subject=parsed.subject,
        normalized_subject=parsed.normalized_subject,
        sender_name=parsed.sender_name,
        sender_email=parsed.sender_email,
        recipients=recipients_json(parsed.recipients),
        cc=recipients_json(parsed.cc),
        direction=direction,
        sent_at=parsed.sent_at or utcnow(),
        received_at=utcnow(),
        is_seen=raw.is_seen,
        is_answered_flag=raw.is_answered,
        body_text=parsed.body_text[:500_000] if parsed.body_text else None,
        body_html=parsed.body_html[:500_000] if parsed.body_html else None,
        clean_text=parsed.clean_text,
        content_hash=parsed.content_hash,
        has_attachments=any(not a.is_inline for a in parsed.attachments),
        is_automated=is_automated,
        exclusion_reason=reason,
        auto_headers=json.dumps(parsed.headers, ensure_ascii=False) if parsed.headers else None,
        size_bytes=raw.size,
        parse_error=parsed.parse_error,
    )
    db.add(message)
    db.flush()

    base_dir = settings.attachments_path
    for item in parsed.attachments:
        local_path = None
        if save_attachments and item.payload:
            local_path = att_utils.save_attachment(
                item.payload, item.filename, base_dir, message.id, settings.max_attachment_size_mb
            )
        db.add(
            Attachment(
                message_id=message.id,
                filename=item.filename,
                mime_type=item.mime_type,
                size=item.size,
                local_path=local_path,
                attachment_category=item.category,
                is_inline=item.is_inline,
            )
        )

    thread = assign_thread(db, message, domains, emails)
    db.flush()
    # Пересчёт сразу: следующее письмо цепочки должно видеть актуального клиента
    recompute_thread(db, thread, domains, emails)
    db.flush()
    return message


# ------------------------------------------------------------------
# Синхронизация
# ------------------------------------------------------------------

def sync_account(
    db: Session,
    days: int = 7,
    folder_ids: list[int] | None = None,
    max_messages: int | None = None,
) -> SyncStats:
    """Загружает письма за период. Ошибка одного письма не останавливает процесс."""
    stats = SyncStats()
    settings_data = get_all_settings(db)
    save_attachments = bool(settings_data.get("attachments.save_local", False))
    domains, emails = corporate_identity(db)

    account = get_or_create_account(db)
    run = SyncRun(account_id=account.id, status="running")
    db.add(run)
    db.commit()

    if not settings.imap_configured():
        run.status = "error"
        run.error = (
            "Подключение к почте не настроено. Заполните YANDEX_EMAIL и "
            "YANDEX_APP_PASSWORD в файле .env."
        )
        run.finished_at = utcnow()
        db.commit()
        raise ImapError(run.error)

    since = (datetime.utcnow() - timedelta(days=max(1, days))).date()
    client = YandexImapClient(
        settings.yandex_imap_host,
        settings.yandex_imap_port,
        settings.yandex_email,
        settings.yandex_app_password,
    )

    try:
        client.connect()
        discover_folders(db, account, client)
        db.commit()

        folders = [f for f in account.folders if f.enabled]
        if folder_ids:
            folders = [f for f in account.folders if f.id in folder_ids]
        if not folders:
            folders = [f for f in account.folders if f.folder_type in {FolderType.INBOX, FolderType.SENT}]

        limit = max_messages or int(settings_data.get("sync.max_messages_per_folder", 2000) or 2000)

        for folder in folders:
            try:
                _sync_folder(
                    db, account, folder, client, since, limit,
                    domains, emails, settings_data, save_attachments, stats,
                )
                stats.folders_processed += 1
                db.commit()
            except ImapError as exc:
                db.rollback()
                stats.errors.append(f"Папка «{folder.folder_name}»: {exc}")
                logger.warning("Ошибка синхронизации папки id=%s: %s", folder.id, exc)
            except Exception as exc:
                db.rollback()
                stats.errors.append(f"Папка «{folder.folder_name}»: {type(exc).__name__}")
                logger.exception("Непредвиденная ошибка в папке id=%s", folder.id)

        # Пересчёт затронутых цепочек
        for thread_id in stats.threads_touched:
            thread = db.get(Thread, thread_id)
            if thread is not None:
                recompute_thread(db, thread, domains, emails)
        account.last_sync_at = utcnow()
        db.commit()

        run.status = "ok" if not stats.errors else "partial"
    except ImapError as exc:
        run.status = "error"
        run.error = str(exc)
        db.commit()
        raise
    finally:
        client.close()
        run.finished_at = utcnow()
        run.folders_processed = stats.folders_processed
        run.messages_fetched = stats.messages_fetched
        run.messages_new = stats.messages_new
        run.messages_failed = stats.messages_failed
        run.threads_touched = len(stats.threads_touched)
        run.details = json.dumps(stats.as_dict(), ensure_ascii=False)
        if stats.errors and not run.error:
            run.error = "; ".join(stats.errors[:5])
        db.commit()

    logger.info(
        "Синхронизация завершена: папок %s, писем получено %s, новых %s, ошибок %s",
        stats.folders_processed, stats.messages_fetched, stats.messages_new, stats.messages_failed,
    )
    return stats


def _sync_folder(
    db: Session,
    account: MailAccount,
    folder: MailFolder,
    client: YandexImapClient,
    since: date,
    limit: int,
    domains: set[str],
    emails: set[str],
    settings_data: dict,
    save_attachments: bool,
    stats: SyncStats,
) -> None:
    uid_validity = client.examine(folder.folder_name)
    if folder.uid_validity and uid_validity and folder.uid_validity != uid_validity:
        # Сервер пересоздал нумерацию — начинаем с нуля, дубликаты отсекаются по Message-ID
        logger.info("Папка id=%s: изменился UIDVALIDITY, полная перезагрузка периода", folder.id)
        folder.last_uid = 0
    folder.uid_validity = uid_validity or folder.uid_validity

    uids = client.search_uids(since=since)
    if folder.last_uid:
        uids = [u for u in uids if u > folder.last_uid]
    uids = sorted(uids)[-limit:]

    max_uid = folder.last_uid or 0
    for uid in uids:
        raw = client.fetch_message(uid)
        stats.messages_fetched += 1
        if raw.error or raw.raw_bytes is None:
            stats.messages_failed += 1
            logger.warning("Папка id=%s uid=%s: %s", folder.id, uid, raw.error or "пустое письмо")
            max_uid = max(max_uid, uid)
            continue

        parsed_email = client.parse(raw.raw_bytes)
        if parsed_email is None:
            stats.messages_failed += 1
            max_uid = max(max_uid, uid)
            continue

        try:
            parsed = parse_message(
                parsed_email,
                load_attachment_payload=save_attachments,
                max_attachment_bytes=settings.max_attachment_size_mb * 1024 * 1024,
            )
            message = store_message(
                db, account, folder, raw, parsed, domains, emails, settings_data, save_attachments
            )
            if message is None:
                stats.messages_duplicate += 1
            else:
                stats.messages_new += 1
                if message.thread_id:
                    stats.threads_touched.add(message.thread_id)
        except Exception as exc:  # одно письмо не должно ломать синхронизацию
            db.rollback()
            stats.messages_failed += 1
            logger.warning(
                "Ошибка обработки письма (папка id=%s, uid=%s): %s", folder.id, uid, type(exc).__name__
            )
        max_uid = max(max_uid, uid)

    folder.last_uid = max_uid
    folder.last_sync_at = utcnow()
    db.flush()
