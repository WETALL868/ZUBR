"""IMAP-клиент Яндекс.Почты. СТРОГО РЕЖИМ ЧТЕНИЯ.

Гарантии безопасного режима:
* папки открываются через EXAMINE (readonly=True) — состояние не меняется;
* тела писем читаются через BODY.PEEK[] — флаг \\Seen не выставляется;
* в модуле нет ни одной команды STORE / COPY / MOVE / EXPUNGE / APPEND / DELETE.
"""

from __future__ import annotations

import email
import imaplib
import re
import socket
import ssl
from dataclasses import dataclass, field
from datetime import date, datetime
from email.message import Message as EmailMessage

from app.core.constants import FolderType
from app.core.logging_setup import get_logger

logger = get_logger(__name__)

# Максимальный размер письма, которое обрабатываем целиком (защита от гигантских писем)
MAX_MESSAGE_BYTES = 20 * 1024 * 1024

_MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]


class ImapError(Exception):
    """Понятная пользователю ошибка подключения/чтения."""


@dataclass
class FolderInfo:
    name: str            # имя для IMAP-команд
    display_name: str    # человекочитаемое имя
    folder_type: str
    flags: list[str] = field(default_factory=list)


@dataclass
class RawMessage:
    uid: int
    flags: list[str]
    raw_bytes: bytes | None
    size: int | None = None
    error: str | None = None

    @property
    def is_seen(self) -> bool:
        return any("\\Seen" in f for f in self.flags)

    @property
    def is_answered(self) -> bool:
        return any("\\Answered" in f for f in self.flags)


def _decode_folder_name(raw: bytes) -> str:
    """IMAP UTF-7 -> строка."""
    try:
        text = raw.decode("ascii")
    except UnicodeDecodeError:
        text = raw.decode("utf-8", errors="replace")
    # Преобразование modified UTF-7 (&XXX-) в юникод
    try:
        return _imap_utf7_decode(text)
    except Exception:
        return text


def _imap_utf7_decode(text: str) -> str:
    import base64

    result: list[str] = []
    i = 0
    while i < len(text):
        char = text[i]
        if char != "&":
            result.append(char)
            i += 1
            continue
        end = text.find("-", i)
        if end == -1:
            result.append(char)
            i += 1
            continue
        chunk = text[i + 1:end]
        if not chunk:
            result.append("&")
        else:
            padded = chunk.replace(",", "/")
            padded += "=" * (-len(padded) % 4)
            result.append(base64.b64decode(padded).decode("utf-16-be", errors="replace"))
        i = end + 1
    return "".join(result)


def classify_folder(name: str, flags: list[str]) -> str:
    lower = name.lower()
    flag_text = " ".join(flags).lower()
    if "\\sent" in flag_text or lower in {"sent", "отправленные", "inbox/sent"} or "отправленны" in lower or "sent" in lower:
        return FolderType.SENT
    if "\\junk" in flag_text or "спам" in lower or "spam" in lower or "junk" in lower:
        return FolderType.SPAM
    if "\\trash" in flag_text or "удален" in lower or "удалён" in lower or "trash" in lower or "корзина" in lower:
        return FolderType.TRASH
    if "\\archive" in flag_text or "архив" in lower or "archive" in lower:
        return FolderType.ARCHIVE
    if lower in {"inbox", "входящие"}:
        return FolderType.INBOX
    if "\\drafts" in flag_text or "черновик" in lower or "draft" in lower:
        return FolderType.CUSTOM
    return FolderType.CUSTOM


_LIST_RE = re.compile(rb'\((?P<flags>[^)]*)\)\s+"?(?P<delim>[^"\s]*)"?\s+(?P<name>.*)')


class YandexImapClient:
    """Подключение к IMAP только для чтения."""

    def __init__(
        self,
        host: str,
        port: int,
        email_address: str,
        password: str,
        timeout: int = 60,
    ):
        self.host = host
        self.port = port
        self.email_address = email_address
        self._password = password
        self.timeout = timeout
        self._conn: imaplib.IMAP4_SSL | None = None

    # ---------- соединение ----------

    def connect(self) -> None:
        if not self.host:
            raise ImapError("Не указан адрес IMAP-сервера (YANDEX_IMAP_HOST).")
        if not self.email_address or "@" not in self.email_address:
            raise ImapError("Не указан корректный почтовый адрес (YANDEX_EMAIL).")
        if not self._password:
            raise ImapError(
                "Не указан пароль приложения (YANDEX_APP_PASSWORD). "
                "Создайте его в настройках Яндекс ID: Безопасность → Пароли приложений → Почта."
            )
        try:
            context = ssl.create_default_context()
            self._conn = imaplib.IMAP4_SSL(
                self.host, self.port, ssl_context=context, timeout=self.timeout
            )
        except socket.timeout as exc:
            raise ImapError(
                f"Превышено время ожидания подключения к {self.host}:{self.port}. "
                "Проверьте интернет-соединение и настройки брандмауэра."
            ) from exc
        except (socket.gaierror, OSError) as exc:
            raise ImapError(
                f"Не удалось подключиться к {self.host}:{self.port}. "
                f"Проверьте адрес сервера и соединение с интернетом. ({type(exc).__name__})"
            ) from exc
        except ssl.SSLError as exc:
            raise ImapError(f"Ошибка защищённого соединения: {exc}") from exc

        try:
            self._conn.login(self.email_address, self._password)
        except imaplib.IMAP4.error as exc:
            detail = str(exc)
            if "AUTHENTICATIONFAILED" in detail.upper() or "INVALID" in detail.upper():
                raise ImapError(
                    "Не удалось войти в почтовый ящик. Проверьте, что: "
                    "1) указан пароль приложения, а не основной пароль; "
                    "2) в Яндекс.Почте включён доступ по IMAP "
                    "(Настройки → Почтовые программы → «Разрешить доступ по протоколу IMAP»); "
                    "3) адрес почты указан полностью, включая домен."
                ) from exc
            raise ImapError(f"Ошибка авторизации IMAP: {detail}") from exc

        logger.info("IMAP: подключение установлено (%s)", self.host)

    def close(self) -> None:
        if self._conn is None:
            return
        try:
            # close() без предшествующего EXPUNGE не изменяет письма,
            # но в режиме EXAMINE достаточно logout.
            self._conn.logout()
        except Exception:
            pass
        finally:
            self._conn = None

    def __enter__(self) -> "YandexImapClient":
        self.connect()
        return self

    def __exit__(self, exc_type, exc, tb) -> None:
        self.close()

    # ---------- папки ----------

    def list_folders(self) -> list[FolderInfo]:
        conn = self._require()
        try:
            status, data = conn.list()
        except imaplib.IMAP4.error as exc:
            raise ImapError(f"Не удалось получить список папок: {exc}") from exc
        if status != "OK":
            raise ImapError("Сервер отказал в получении списка папок.")

        folders: list[FolderInfo] = []
        for raw in data or []:
            if not isinstance(raw, bytes):
                continue
            match = _LIST_RE.match(raw)
            if not match:
                continue
            flags = _decode_folder_name(match.group("flags")).split()
            name = _decode_folder_name(match.group("name")).strip().strip('"')
            if not name:
                continue
            if "\\noselect" in " ".join(flags).lower():
                continue
            folders.append(
                FolderInfo(
                    name=name,
                    display_name=name.split("|")[-1].split("/")[-1],
                    folder_type=classify_folder(name, flags),
                    flags=flags,
                )
            )
        return folders

    def examine(self, folder: str) -> int:
        """Открывает папку ТОЛЬКО ДЛЯ ЧТЕНИЯ. Возвращает UIDVALIDITY."""
        conn = self._require()
        try:
            status, data = conn.select(_quote_folder(folder), readonly=True)
        except imaplib.IMAP4.error as exc:
            raise ImapError(f"Не удалось открыть папку «{folder}»: {exc}") from exc
        if status != "OK":
            raise ImapError(f"Папка «{folder}» недоступна.")
        try:
            status, uidv = conn.response("UIDVALIDITY")
            if uidv and uidv[0]:
                return int(uidv[0])
        except (ValueError, TypeError, IndexError):
            pass
        return 0

    # ---------- письма ----------

    def search_uids(self, since: date | None = None, before: date | None = None) -> list[int]:
        conn = self._require()
        criteria: list[str] = []
        if since:
            criteria += ["SINCE", _imap_date(since)]
        if before:
            criteria += ["BEFORE", _imap_date(before)]
        if not criteria:
            criteria = ["ALL"]
        try:
            status, data = conn.uid("SEARCH", None, *criteria)
        except imaplib.IMAP4.error as exc:
            raise ImapError(f"Ошибка поиска писем: {exc}") from exc
        if status != "OK" or not data:
            return []
        try:
            return [int(uid) for uid in (data[0] or b"").split()]
        except ValueError:
            return []

    def fetch_message(self, uid: int) -> RawMessage:
        """Загружает письмо, НЕ изменяя флаг прочтения (BODY.PEEK)."""
        conn = self._require()
        try:
            status, data = conn.uid("FETCH", str(uid), "(FLAGS RFC822.SIZE BODY.PEEK[])")
        except (imaplib.IMAP4.error, socket.timeout, OSError) as exc:
            return RawMessage(uid=uid, flags=[], raw_bytes=None, error=f"{type(exc).__name__}: {exc}")
        if status != "OK" or not data:
            return RawMessage(uid=uid, flags=[], raw_bytes=None, error="Сервер не вернул письмо")

        raw_bytes: bytes | None = None
        header_blob = b""
        for part in data:
            if isinstance(part, tuple) and len(part) >= 2:
                header_blob += part[0] or b""
                raw_bytes = part[1]
            elif isinstance(part, bytes):
                header_blob += part

        flags = [f.decode() if isinstance(f, bytes) else str(f) for f in imaplib.ParseFlags(header_blob)]
        size = None
        size_match = re.search(rb"RFC822\.SIZE\s+(\d+)", header_blob)
        if size_match:
            try:
                size = int(size_match.group(1))
            except ValueError:
                size = None

        if raw_bytes is not None and len(raw_bytes) > MAX_MESSAGE_BYTES:
            return RawMessage(
                uid=uid, flags=flags, raw_bytes=None, size=size,
                error=f"Письмо слишком большое ({len(raw_bytes) // 1024 // 1024} МБ) — пропущено",
            )
        return RawMessage(uid=uid, flags=flags, raw_bytes=raw_bytes, size=size)

    def parse(self, raw: bytes) -> EmailMessage | None:
        try:
            return email.message_from_bytes(raw)
        except Exception as exc:  # повреждённое письмо
            logger.warning("Не удалось разобрать письмо: %s", type(exc).__name__)
            return None

    # ---------- служебное ----------

    def _require(self) -> imaplib.IMAP4_SSL:
        if self._conn is None:
            raise ImapError("Нет активного IMAP-подключения. Вызовите connect().")
        return self._conn


def _imap_date(value: date) -> str:
    return f"{value.day:02d}-{_MONTHS[value.month - 1]}-{value.year}"


def _quote_folder(folder: str) -> str:
    if folder.startswith('"') and folder.endswith('"'):
        return folder
    return f'"{folder}"'


def test_connection(host: str, port: int, email_address: str, password: str) -> tuple[bool, str]:
    """Проверка настроек подключения. Возвращает (успех, сообщение)."""
    client = YandexImapClient(host, port, email_address, password, timeout=30)
    try:
        client.connect()
        folders = client.list_folders()
        return True, f"Подключение успешно. Найдено папок: {len(folders)}."
    except ImapError as exc:
        return False, str(exc)
    except Exception as exc:  # непредвиденная ошибка
        return False, f"Непредвиденная ошибка подключения: {type(exc).__name__}: {exc}"
    finally:
        client.close()
