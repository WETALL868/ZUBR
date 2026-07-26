"""Константы предметной области: статусы, типы запросов, направления писем."""

from __future__ import annotations

from enum import StrEnum


class Direction(StrEnum):
    INCOMING = "incoming"      # письмо от клиента
    OUTGOING = "outgoing"      # письмо сотрудника
    INTERNAL = "internal"      # переписка между сотрудниками
    UNKNOWN = "unknown"


DIRECTION_RU = {
    Direction.INCOMING: "Входящее",
    Direction.OUTGOING: "Исходящее",
    Direction.INTERNAL: "Внутреннее",
    Direction.UNKNOWN: "Не определено",
}


class ThreadStatus(StrEnum):
    CRITICAL = "CRITICAL"
    INCOMPLETE = "INCOMPLETE"
    WAITING_FOR_CLIENT = "WAITING_FOR_CLIENT"
    COMPLETED = "COMPLETED"
    NOT_RELEVANT = "NOT_RELEVANT"
    NEEDS_REVIEW = "NEEDS_REVIEW"
    NEW = "NEW"  # техническое состояние до анализа


STATUS_RU: dict[str, str] = {
    ThreadStatus.CRITICAL: "Критично — без ответа",
    ThreadStatus.INCOMPLETE: "Ответ есть, запрос не выполнен",
    ThreadStatus.WAITING_FOR_CLIENT: "Ожидается ответ клиента",
    ThreadStatus.COMPLETED: "Выполнено",
    ThreadStatus.NOT_RELEVANT: "Не относится к клиентам",
    ThreadStatus.NEEDS_REVIEW: "Требует ручной проверки",
    ThreadStatus.NEW: "Не проанализировано",
}

STATUS_COLOR: dict[str, str] = {
    ThreadStatus.CRITICAL: "red",
    ThreadStatus.INCOMPLETE: "orange",
    ThreadStatus.WAITING_FOR_CLIENT: "yellow",
    ThreadStatus.COMPLETED: "green",
    ThreadStatus.NOT_RELEVANT: "gray",
    ThreadStatus.NEEDS_REVIEW: "purple",
    ThreadStatus.NEW: "gray",
}

# Порядок сортировки «сначала важное»
STATUS_PRIORITY: dict[str, int] = {
    ThreadStatus.CRITICAL: 0,
    ThreadStatus.INCOMPLETE: 1,
    ThreadStatus.NEEDS_REVIEW: 2,
    ThreadStatus.WAITING_FOR_CLIENT: 3,
    ThreadStatus.NEW: 4,
    ThreadStatus.COMPLETED: 5,
    ThreadStatus.NOT_RELEVANT: 6,
}


class RequestType(StrEnum):
    INVOICE = "invoice"
    COMMERCIAL_OFFER = "commercial_offer"
    PRICE = "price"
    AVAILABILITY = "availability"
    DELIVERY_TIME = "delivery_time"
    DELIVERY_COST = "delivery_cost"
    PRODUCT_SELECTION = "product_selection"
    ANALOGUE_SELECTION = "analogue_selection"
    COMPATIBILITY = "compatibility"
    TECHNICAL_SPECIFICATION = "technical_specification"
    DOCUMENTS = "documents"
    CONTRACT = "contract"
    REQUISITES = "requisites"
    WARRANTY = "warranty"
    CLAIM = "claim"
    RETURN = "return"
    ORDER_STATUS = "order_status"
    PAYMENT_STATUS = "payment_status"
    CONSULTATION = "consultation"
    OTHER = "other"


REQUEST_TYPE_RU: dict[str, str] = {
    RequestType.INVOICE: "Счёт",
    RequestType.COMMERCIAL_OFFER: "Коммерческое предложение",
    RequestType.PRICE: "Цена",
    RequestType.AVAILABILITY: "Наличие",
    RequestType.DELIVERY_TIME: "Срок поставки",
    RequestType.DELIVERY_COST: "Стоимость доставки",
    RequestType.PRODUCT_SELECTION: "Подбор товара",
    RequestType.ANALOGUE_SELECTION: "Подбор аналога",
    RequestType.COMPATIBILITY: "Совместимость",
    RequestType.TECHNICAL_SPECIFICATION: "Технические характеристики",
    RequestType.DOCUMENTS: "Документы",
    RequestType.CONTRACT: "Договор",
    RequestType.REQUISITES: "Реквизиты",
    RequestType.WARRANTY: "Гарантия",
    RequestType.CLAIM: "Претензия",
    RequestType.RETURN: "Возврат",
    RequestType.ORDER_STATUS: "Статус заказа",
    RequestType.PAYMENT_STATUS: "Статус оплаты",
    RequestType.CONSULTATION: "Консультация",
    RequestType.OTHER: "Прочее",
}

ALL_REQUEST_TYPES = [t.value for t in RequestType]


class FolderType(StrEnum):
    INBOX = "inbox"
    SENT = "sent"
    ARCHIVE = "archive"
    SPAM = "spam"
    TRASH = "trash"
    CUSTOM = "custom"


FOLDER_TYPE_RU = {
    FolderType.INBOX: "Входящие",
    FolderType.SENT: "Отправленные",
    FolderType.ARCHIVE: "Архив",
    FolderType.SPAM: "Спам",
    FolderType.TRASH: "Удалённые",
    FolderType.CUSTOM: "Пользовательская папка",
}


class AttachmentCategory(StrEnum):
    INVOICE = "invoice"
    COMMERCIAL_OFFER = "commercial_offer"
    CONTRACT = "contract"
    SPECIFICATION = "specification"
    ACT = "act"
    IMAGE = "image"
    ARCHIVE = "archive"
    OTHER = "other"
    INLINE = "inline"


ATTACHMENT_CATEGORY_RU = {
    AttachmentCategory.INVOICE: "Счёт",
    AttachmentCategory.COMMERCIAL_OFFER: "Коммерческое предложение",
    AttachmentCategory.CONTRACT: "Договор",
    AttachmentCategory.SPECIFICATION: "Спецификация",
    AttachmentCategory.ACT: "Акт / накладная",
    AttachmentCategory.IMAGE: "Изображение",
    AttachmentCategory.ARCHIVE: "Архив",
    AttachmentCategory.OTHER: "Файл",
    AttachmentCategory.INLINE: "Встроенное изображение",
}

RESPONSIBLE_PARTY_RU = {
    "manager": "Менеджер",
    "client": "Клиент",
    "none": "Действий не требуется",
}
