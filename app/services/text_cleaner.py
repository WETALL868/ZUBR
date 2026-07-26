"""Подготовка текста письма перед анализом.

Задача: убрать технический мусор (HTML, подписи, дисклеймеры, повторные цитаты),
но НЕ потерять запрос клиента, ответ менеджера, цены, количества, артикулы,
сроки, адрес доставки и упоминания вложений.
"""

from __future__ import annotations

import re

try:  # bs4 доступен, но код не должен падать без него
    from bs4 import BeautifulSoup  # type: ignore
except ImportError:  # pragma: no cover
    BeautifulSoup = None  # type: ignore

MAX_MESSAGE_CHARS = 4000
MAX_THREAD_CHARS = 24000

# Маркеры начала цитаты предыдущего письма
_QUOTE_MARKERS = [
    r"^\s*-{2,}\s*Пересланное сообщение\s*-{2,}",
    r"^\s*-{2,}\s*Original Message\s*-{2,}",
    r"^\s*-{2,}\s*Forwarded message\s*-{2,}",
    r"^\s*_{5,}\s*$",
    r"^\s*От кого:\s",
    r"^\s*От:\s.*<.*@.*>",
    r"^\s*From:\s.*@",
    r"^\s*\d{1,2}\.\d{1,2}\.\d{2,4}[,\s].{0,80}(писал|wrote)",
    r"^\s*В\s.{0,60},\s.{0,80}(писал|пишет)",
    r"^\s*On\s.{0,60}wrote:",
    r"^\s*Отправлено:\s",
    r"^\s*Sent:\s",
]
_QUOTE_RE = [re.compile(p, re.IGNORECASE | re.MULTILINE) for p in _QUOTE_MARKERS]

# Маркеры подписи
_SIGNATURE_MARKERS = [
    r"^\s*--\s*$",
    r"^\s*С уважением[,\s]",
    r"^\s*Best regards[,\s]",
    r"^\s*Kind regards[,\s]",
    r"^\s*Всего доброго[,\s]",
    r"^\s*Хорошего дня[,\s]",
]
_SIGNATURE_RE = [re.compile(p, re.IGNORECASE | re.MULTILINE) for p in _SIGNATURE_MARKERS]

# Юридические дисклеймеры
_DISCLAIMER_RE = re.compile(
    r"(конфиденциальн\w*\s+информац|"
    r"это\s+сообщение\s+предназначено|"
    r"if\s+you\s+are\s+not\s+the\s+intended\s+recipient|"
    r"this\s+e?-?mail\s+.{0,40}confidential|"
    r"настоящее\s+письмо\s+и\s+любые\s+приложения|"
    r"пожалуйста,?\s+подумайте\s+об\s+окружающей\s+среде)",
    re.IGNORECASE,
)

_URL_TRACKING_RE = re.compile(r"https?://\S{80,}")
_MULTI_BLANK_RE = re.compile(r"\n{3,}")
_MULTI_SPACE_RE = re.compile(r"[ \t]{2,}")
_QUOTE_LINE_RE = re.compile(r"^\s*>+\s?", re.MULTILINE)


def html_to_text(html: str | None) -> str:
    """Преобразует HTML письма в текст, сохраняя переносы строк."""
    if not html:
        return ""
    if BeautifulSoup is None:  # pragma: no cover
        text = re.sub(r"<br\s*/?>", "\n", html, flags=re.IGNORECASE)
        text = re.sub(r"</p>", "\n\n", text, flags=re.IGNORECASE)
        text = re.sub(r"<[^>]+>", " ", text)
        return _MULTI_SPACE_RE.sub(" ", text)
    try:
        soup = BeautifulSoup(html, "html.parser")
    except Exception:  # pragma: no cover - повреждённый HTML
        return re.sub(r"<[^>]+>", " ", html)
    for tag in soup(["script", "style", "head", "meta", "link", "noscript"]):
        tag.decompose()
    # Трекинговые пиксели и картинки подписи
    for img in soup.find_all("img"):
        img.decompose()
    for br in soup.find_all("br"):
        br.replace_with("\n")
    for block in soup.find_all(["p", "div", "tr", "li", "h1", "h2", "h3", "table"]):
        block.append("\n")
    text = soup.get_text(separator=" ")
    return text


def strip_quoted_history(text: str) -> tuple[str, bool]:
    """Обрезает цитату предыдущей переписки. Возвращает (текст, была_ли_цитата)."""
    if not text:
        return "", False
    earliest: int | None = None
    for pattern in _QUOTE_RE:
        match = pattern.search(text)
        if match and (earliest is None or match.start() < earliest):
            earliest = match.start()
    if earliest is not None and earliest > 30:
        return text[:earliest].rstrip(), True
    if earliest is not None and earliest <= 30:
        # Письмо почти целиком состоит из цитаты — оставляем его, но помечаем
        return text.rstrip(), True
    return text, False


def strip_signature(text: str) -> str:
    """Обрезает подпись, оставляя первые строки после маркера (имя/телефон могут быть полезны)."""
    if not text:
        return ""
    cut: int | None = None
    for pattern in _SIGNATURE_RE:
        for match in pattern.finditer(text):
            # Подпись обычно во второй половине письма
            if match.start() > len(text) * 0.3:
                if cut is None or match.start() < cut:
                    cut = match.start()
                break
    if cut is None:
        return text
    head = text[:cut].rstrip()
    tail = text[cut:cut + 200].strip()
    return f"{head}\n[подпись: {tail[:120]}...]" if tail else head


def remove_disclaimers(text: str) -> str:
    if not text:
        return ""
    lines = text.split("\n")
    kept = [line for line in lines if not _DISCLAIMER_RE.search(line)]
    return "\n".join(kept)


def clean_text(body_text: str | None, body_html: str | None = None) -> str:
    """Полная очистка тела письма."""
    raw = body_text or ""
    if not raw.strip() and body_html:
        raw = html_to_text(body_html)
    if not raw.strip():
        return ""
    text = raw.replace("\r\n", "\n").replace("\r", "\n").replace("\xa0", " ")
    text = _QUOTE_LINE_RE.sub("", text)
    text, _ = strip_quoted_history(text)
    text = remove_disclaimers(text)
    text = strip_signature(text)
    text = _URL_TRACKING_RE.sub("[ссылка]", text)
    text = _MULTI_SPACE_RE.sub(" ", text)
    text = _MULTI_BLANK_RE.sub("\n\n", text)
    return text.strip()


def truncate(text: str, limit: int = MAX_MESSAGE_CHARS) -> str:
    """Обрезает длинный текст, сохраняя начало и конец (там обычно суть и итог)."""
    if not text or len(text) <= limit:
        return text or ""
    head = int(limit * 0.7)
    tail = limit - head - 40
    return f"{text[:head]}\n...[пропущено {len(text) - limit} символов]...\n{text[-tail:]}"
