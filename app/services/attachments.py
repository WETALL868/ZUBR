"""Работа с вложениями: категоризация по имени/типу и безопасное сохранение."""

from __future__ import annotations

import re
import unicodedata
from pathlib import Path

from app.core.constants import AttachmentCategory

DOCUMENT_EXTENSIONS = {".pdf", ".xls", ".xlsx", ".doc", ".docx", ".odt", ".ods", ".rtf"}
IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".tiff", ".heic"}
ARCHIVE_EXTENSIONS = {".zip", ".rar", ".7z", ".tar", ".gz"}

# Расширения, которые никогда не сохраняются на диск
DANGEROUS_EXTENSIONS = {
    ".exe", ".com", ".bat", ".cmd", ".scr", ".pif", ".msi", ".msp", ".vbs", ".vbe",
    ".js", ".jse", ".ws", ".wsf", ".wsh", ".ps1", ".psm1", ".jar", ".lnk", ".reg",
    ".dll", ".sys", ".cpl", ".hta", ".apk", ".app", ".sh", ".py", ".php",
}

_CATEGORY_KEYWORDS: list[tuple[str, tuple[str, ...]]] = [
    (AttachmentCategory.INVOICE, ("счет", "счёт", "invoice", "bill", "schet", "sch_", "s-f", "счф")),
    (AttachmentCategory.COMMERCIAL_OFFER, ("кп", "коммерческое", "commercial", "quotation", "quote", "proposal", "offer", "предложение")),
    (AttachmentCategory.CONTRACT, ("договор", "контракт", "contract", "agreement", "dogovor")),
    (AttachmentCategory.SPECIFICATION, ("спецификац", "specification", "прайс", "price", "перечень", "смета")),
    (AttachmentCategory.ACT, ("акт", "накладная", "упд", "торг-12", "торг12", "waybill", "act")),
]

_SAFE_NAME_RE = re.compile(r"[^\w\-. ]+", re.UNICODE)


def file_extension(filename: str | None) -> str:
    if not filename:
        return ""
    return Path(filename).suffix.lower()


def categorize(filename: str | None, mime_type: str | None = None, is_inline: bool = False) -> str:
    """Определяет категорию вложения по имени файла и типу.

    ВАЖНО: расширение само по себе не делает файл счётом — решает имя файла.
    """
    if is_inline:
        return AttachmentCategory.INLINE
    name = (filename or "").lower()
    ext = file_extension(name)

    # Ключевые слова в имени файла — основной признак
    for category, keywords in _CATEGORY_KEYWORDS:
        for kw in keywords:
            if kw in name:
                # «Счёт» в имени + документ = уверенно счёт
                return category

    if ext in IMAGE_EXTENSIONS:
        return AttachmentCategory.IMAGE
    if ext in ARCHIVE_EXTENSIONS:
        return AttachmentCategory.ARCHIVE
    return AttachmentCategory.OTHER


def is_document(filename: str | None) -> bool:
    return file_extension(filename) in DOCUMENT_EXTENSIONS


def is_dangerous(filename: str | None) -> bool:
    return file_extension(filename) in DANGEROUS_EXTENSIONS


def safe_filename(filename: str | None, fallback: str = "attachment") -> str:
    """Безопасное имя файла: без путей, без управляющих символов, ограниченной длины."""
    name = (filename or "").strip()
    # Убираем любые компоненты пути (в т.ч. windows-style и обход каталогов)
    name = name.replace("\\", "/").split("/")[-1]
    name = name.replace("..", "_")
    name = unicodedata.normalize("NFKC", name)
    name = _SAFE_NAME_RE.sub("_", name).strip(" ._")
    if not name:
        name = fallback
    stem = Path(name).stem[:80] or fallback
    ext = Path(name).suffix[:12]
    return f"{stem}{ext}"


def unique_path(directory: Path, filename: str) -> Path:
    """Путь, не перезаписывающий существующие файлы."""
    directory.mkdir(parents=True, exist_ok=True)
    candidate = directory / filename
    if not candidate.exists():
        return candidate
    stem, suffix = Path(filename).stem, Path(filename).suffix
    for i in range(1, 1000):
        candidate = directory / f"{stem}_{i}{suffix}"
        if not candidate.exists():
            return candidate
    raise OSError("Не удалось подобрать уникальное имя файла для вложения")


def save_attachment(
    payload: bytes,
    filename: str,
    base_dir: Path,
    message_db_id: int,
    max_size_mb: int = 25,
) -> str | None:
    """Сохраняет вложение на диск. Возвращает относительный путь или None."""
    if not payload:
        return None
    if len(payload) > max_size_mb * 1024 * 1024:
        return None
    if is_dangerous(filename):
        return None
    safe = safe_filename(filename)
    target_dir = base_dir / f"msg_{message_db_id}"
    path = unique_path(target_dir, safe)
    try:
        path.write_bytes(payload)
    except OSError:
        return None
    return str(path)


def looks_like_invoice(filename: str | None) -> bool:
    """Файл похож на счёт: ключевое слово в имени И документный формат."""
    name = (filename or "").lower()
    has_keyword = any(kw in name for kw in ("счет", "счёт", "invoice", "bill", "schet", "счф"))
    return has_keyword and is_document(name)


def looks_like_offer(filename: str | None) -> bool:
    name = (filename or "").lower()
    has_keyword = any(
        kw in name for kw in ("кп", "коммерческ", "quotation", "quote", "proposal", "offer", "предложение")
    )
    return has_keyword and is_document(name)
