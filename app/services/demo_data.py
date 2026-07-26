"""Демонстрационный режим: набор тестовых переписок с ожидаемыми результатами.

Письма формируются как настоящие MIME-сообщения и проходят весь конвейер
(разбор → направление → цепочки → анализ), поэтому демо-режим проверяет
реальную логику программы, а не «нарисованные» данные.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime, timedelta
from email.message import EmailMessage
from email.utils import format_datetime, make_msgid

from sqlalchemy import delete, select
from sqlalchemy.orm import Session

from app.core.constants import Direction, FolderType, RequestType, ThreadStatus
from app.core.logging_setup import get_logger
from app.models import (
    AnalysisResult,
    Attachment,
    CustomerRequest,
    MailFolder,
    ManualReview,
    Message,
    Thread,
)
from app.services.imap_client import RawMessage
from app.services.message_parser import parse_message
from app.services.settings_service import get_all_settings, set_setting
from app.services.sync_service import get_or_create_account, store_message
from app.services.threading_service import recompute_thread

logger = get_logger(__name__)

DEMO_DOMAIN = "zubr-demo.ru"
MANAGERS = {
    "ivanov@zubr-demo.ru": "Иванов Сергей",
    "petrova@zubr-demo.ru": "Петрова Анна",
    "sidorov@zubr-demo.ru": "Сидоров Дмитрий",
}

PDF_BYTES = b"%PDF-1.4\n% demo file\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"
XLSX_BYTES = b"PK\x03\x04demo-xlsx-content"


@dataclass
class DemoMessage:
    direction: str
    sender: str
    to: list[str]
    subject: str
    hours_ago: float
    body: str
    attachments: list[tuple[str, str, bytes]] = field(default_factory=list)
    is_seen: bool = True
    headers: dict[str, str] = field(default_factory=dict)


@dataclass
class DemoScenario:
    code: str
    title: str
    messages: list[DemoMessage]
    expected_status: str
    expected_requests: dict[str, bool] = field(default_factory=dict)
    note: str = ""


def _client(email_addr: str) -> str:
    return email_addr


SCENARIOS: list[DemoScenario] = [
    DemoScenario(
        code="01",
        title="Клиент запросил счёт — счёт отправлен",
        expected_status=ThreadStatus.COMPLETED,
        expected_requests={RequestType.INVOICE: True},
        messages=[
            DemoMessage(
                Direction.INCOMING, "zakupki@stroymash.ru", ["ivanov@zubr-demo.ru"],
                "Запрос счёта на насос НМШ-25",
                hours_ago=28,
                body="Добрый день!\n\nПрошу выставить счёт на насос НМШ-25 в количестве 2 шт.\n"
                     "Реквизиты во вложении не нужны, мы у вас уже покупали.\n\n"
                     "С уважением, Алексей Морозов, ООО «Строймаш»",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["zakupki@stroymash.ru"],
                "Re: Запрос счёта на насос НМШ-25",
                hours_ago=26,
                body="Здравствуйте, Алексей!\n\nСчёт на оплату № 1043 от 24.07 направляю во вложении.\n"
                     "Товар в наличии на складе в Москве.\n\nС уважением, Сергей Иванов",
                attachments=[("Счет_1043_от_24.07.pdf", "application/pdf", PDF_BYTES)],
            ),
        ],
    ),
    DemoScenario(
        code="02",
        title="Клиент запросил счёт — в ответ сообщили только цену",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.INVOICE: False},
        note="Ключевой сценарий: ответ есть, но запрос клиента не выполнен.",
        messages=[
            DemoMessage(
                Direction.INCOMING, "snab@tehprom.ru", ["petrova@zubr-demo.ru"],
                "Счёт на подшипники 6206",
                hours_ago=26,
                body="Здравствуйте! Нужен счёт на подшипники 6206 — 20 штук. "
                     "Оплатим сегодня же.\n\nОльга Ткаченко, ООО «Техпром»",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["snab@tehprom.ru"],
                "Re: Счёт на подшипники 6206",
                hours_ago=24,
                body="Добрый день!\n\nПодшипники 6206 есть в наличии, цена 450 руб. за штуку.\n\n"
                     "С уважением, Анна Петрова",
            ),
        ],
    ),
    DemoScenario(
        code="03",
        title="Письмо прочитано, но ответа нет",
        expected_status=ThreadStatus.CRITICAL,
        messages=[
            DemoMessage(
                Direction.INCOMING, "info@energoset.ru", ["ivanov@zubr-demo.ru"],
                "Запрос цены на кабель ВВГнг 3х2.5",
                hours_ago=30,
                body="Добрый день! Сообщите, пожалуйста, цену и наличие кабеля ВВГнг 3х2.5, "
                     "нужно 500 метров.\n\nСергей Волков, ООО «Энергосеть»",
                is_seen=True,
            ),
        ],
    ),
    DemoScenario(
        code="04",
        title="Письмо не прочитано и без ответа",
        expected_status=ThreadStatus.CRITICAL,
        messages=[
            DemoMessage(
                Direction.INCOMING, "op@metallresurs.ru", ["petrova@zubr-demo.ru"],
                "Запрос на трубу 57х3.5",
                hours_ago=20,
                body="Здравствуйте! Требуется труба 57х3.5, 3 тонны. "
                     "Прошу сообщить стоимость и срок поставки.\n\nМарина Крылова",
                is_seen=False,
            ),
        ],
    ),
    DemoScenario(
        code="05",
        title="Менеджер запросил реквизиты — ожидается ответ клиента",
        expected_status=ThreadStatus.WAITING_FOR_CLIENT,
        messages=[
            DemoMessage(
                Direction.INCOMING, "buh@promline.ru", ["sidorov@zubr-demo.ru"],
                "Счёт на редуктор Ч-100",
                hours_ago=22,
                body="Добрый день! Выставите, пожалуйста, счёт на редуктор Ч-100, 1 шт.",
            ),
            DemoMessage(
                Direction.OUTGOING, "sidorov@zubr-demo.ru", ["buh@promline.ru"],
                "Re: Счёт на редуктор Ч-100",
                hours_ago=21,
                body="Здравствуйте!\n\nРедуктор Ч-100 в наличии, цена 18 400 руб. "
                     "Для выставления счёта пришлите, пожалуйста, реквизиты вашей организации.\n\n"
                     "С уважением, Дмитрий Сидоров",
            ),
        ],
    ),
    DemoScenario(
        code="06",
        title="Клиент запросил цену и наличие — получил только цену",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.PRICE: True, RequestType.AVAILABILITY: False},
        messages=[
            DemoMessage(
                Direction.INCOMING, "zakaz@agroholding.ru", ["ivanov@zubr-demo.ru"],
                "Цена и наличие фильтров",
                hours_ago=27,
                body="Добрый день. Сообщите цену и наличие фильтров масляных 6.2-24, "
                     "интересует 40 штук.",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["zakaz@agroholding.ru"],
                "Re: Цена и наличие фильтров",
                hours_ago=25,
                body="Здравствуйте! Цена фильтра 6.2-24 — 780 руб. с НДС.",
            ),
        ],
    ),
    DemoScenario(
        code="07",
        title="Клиент запросил КП — файл приложен",
        expected_status=ThreadStatus.COMPLETED,
        expected_requests={RequestType.COMMERCIAL_OFFER: True},
        messages=[
            DemoMessage(
                Direction.INCOMING, "tender@gorstroy.ru", ["petrova@zubr-demo.ru"],
                "Коммерческое предложение на светильники",
                hours_ago=30,
                body="Здравствуйте! Просим направить коммерческое предложение на светодиодные "
                     "светильники 36 Вт, 120 штук, для участия в закупке.",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["tender@gorstroy.ru"],
                "Re: Коммерческое предложение на светильники",
                hours_ago=28,
                body="Добрый день! Коммерческое предложение направляю во вложении. "
                     "Срок действия — 14 дней.",
                attachments=[
                    ("КП_светильники_36Вт.pdf", "application/pdf", PDF_BYTES),
                ],
            ),
        ],
    ),
    DemoScenario(
        code="08",
        title="Клиент запросил КП — файла нет",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.COMMERCIAL_OFFER: False},
        messages=[
            DemoMessage(
                Direction.INCOMING, "pto@mostotrest.ru", ["sidorov@zubr-demo.ru"],
                "Прошу выслать КП",
                hours_ago=26,
                body="Добрый день! Прошу выслать коммерческое предложение на аренду "
                     "компрессорного оборудования.",
            ),
            DemoMessage(
                Direction.OUTGOING, "sidorov@zubr-demo.ru", ["pto@mostotrest.ru"],
                "Re: Прошу выслать КП",
                hours_ago=24,
                body="Здравствуйте! Ваш запрос получили, работаем над ним.",
            ),
        ],
    ),
    DemoScenario(
        code="09",
        title="Клиент запросил срок — ответ неконкретный",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.DELIVERY_TIME: False},
        messages=[
            DemoMessage(
                Direction.INCOMING, "logistic@sevtrans.ru", ["ivanov@zubr-demo.ru"],
                "Срок поставки задвижек",
                hours_ago=29,
                body="Здравствуйте! Уточните срок поставки задвижек 30ч906бр Ду100, 6 штук.",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["logistic@sevtrans.ru"],
                "Re: Срок поставки задвижек",
                hours_ago=27,
                body="Добрый день! Уточняем у поставщика, сообщим дополнительно. Скоро ответим.",
            ),
        ],
    ),
    DemoScenario(
        code="10",
        title="Клиент запросил аналог — предложена конкретная модель",
        expected_status=ThreadStatus.COMPLETED,
        expected_requests={RequestType.ANALOGUE_SELECTION: True},
        messages=[
            DemoMessage(
                Direction.INCOMING, "service@rembaza.ru", ["petrova@zubr-demo.ru"],
                "Аналог датчика Siemens",
                hours_ago=24,
                body="Добрый день! Нужен аналог датчика давления Siemens 7MF4033. "
                     "Оригинал не поставляется.",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["service@rembaza.ru"],
                "Re: Аналог датчика Siemens",
                hours_ago=22,
                body="Здравствуйте! Предлагаем замену: датчик ОВЕН ПД100-ДИ0.25-111-0.5, "
                     "полный аналог по присоединению и диапазону. Цена 8 900 руб., в наличии 4 шт.",
            ),
        ],
    ),
    DemoScenario(
        code="11",
        title="Клиент запросил аналог — ответили «подберём»",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.ANALOGUE_SELECTION: False},
        messages=[
            DemoMessage(
                Direction.INCOMING, "glavmeh@zavod-krm.ru", ["sidorov@zubr-demo.ru"],
                "Замена насоса Grundfos",
                hours_ago=25,
                body="Здравствуйте. Требуется аналог насоса Grundfos CR 5-10. "
                     "Что можете предложить?",
            ),
            DemoMessage(
                Direction.OUTGOING, "sidorov@zubr-demo.ru", ["glavmeh@zavod-krm.ru"],
                "Re: Замена насоса Grundfos",
                hours_ago=23,
                body="Добрый день! Можем подобрать аналог, уточним у поставщиков и вернёмся с ответом.",
            ),
        ],
    ),
    DemoScenario(
        code="12",
        title="Одно письмо содержит пять задач — выполнены не все",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={
            RequestType.PRICE: True,
            RequestType.AVAILABILITY: True,
            RequestType.DELIVERY_TIME: False,
            RequestType.INVOICE: False,
            RequestType.DELIVERY_COST: False,
        },
        note="Пример из задания: цена и наличие даны, срок, счёт и доставка — нет.",
        messages=[
            DemoMessage(
                Direction.INCOMING, "snab@krasproekt.ru", ["ivanov@zubr-demo.ru"],
                "Запрос по электродвигателям АИР100",
                hours_ago=26,
                body="Добрый день. Прошу сообщить цену, наличие, срок поставки и выставить счёт "
                     "на 10 штук электродвигателей АИР100S4 с доставкой до Красноярска.\n\n"
                     "С уважением, Николай Зайцев, ООО «Красноярскпроект»",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["snab@krasproekt.ru"],
                "Re: Запрос по электродвигателям АИР100",
                hours_ago=24,
                body="Здравствуйте! Электродвигатели АИР100S4 в наличии, цена 12 500 руб. за штуку.",
            ),
        ],
    ),
    DemoScenario(
        code="13",
        title="Выполнена часть задач: цена и срок даны, документы не отправлены",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={
            RequestType.PRICE: True,
            RequestType.DELIVERY_TIME: True,
            RequestType.DOCUMENTS: False,
        },
        messages=[
            DemoMessage(
                Direction.INCOMING, "kontrol@vodokanal-nsk.ru", ["petrova@zubr-demo.ru"],
                "Цена, срок и сертификаты на трубы ПЭ100",
                hours_ago=27,
                body="Здравствуйте! Сообщите цену и срок поставки труб ПЭ100 SDR17 Ду160, "
                     "а также приложите сертификаты соответствия и паспорт качества.",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["kontrol@vodokanal-nsk.ru"],
                "Re: Цена, срок и сертификаты на трубы ПЭ100",
                hours_ago=25,
                body="Добрый день! Цена трубы ПЭ100 SDR17 Ду160 — 1 240 руб./м. "
                     "Срок поставки 5 рабочих дней с момента оплаты.",
            ),
        ],
    ),
    DemoScenario(
        code="14",
        title="Клиент написал повторно, не получив ответа",
        expected_status=ThreadStatus.CRITICAL,
        messages=[
            DemoMessage(
                Direction.INCOMING, "director@techsnab-ural.ru", ["ivanov@zubr-demo.ru"],
                "Запрос на поставку ЗИП",
                hours_ago=50,
                body="Добрый день! Просим направить предложение на комплект ЗИП по прилагаемому "
                     "перечню. Позиции: 14 наименований.",
            ),
            DemoMessage(
                Direction.INCOMING, "director@techsnab-ural.ru", ["ivanov@zubr-demo.ru"],
                "Re: Запрос на поставку ЗИП",
                hours_ago=6,
                body="Добрый день! Повторно направляю запрос — ответа не получили. "
                     "Вопрос остаётся актуальным, ждём ваше предложение.",
            ),
        ],
    ),
    DemoScenario(
        code="15",
        title="Автоматическое уведомление — не клиентское обращение",
        expected_status=ThreadStatus.NOT_RELEVANT,
        messages=[
            DemoMessage(
                Direction.INCOMING, "noreply@sberbank.ru", ["ivanov@zubr-demo.ru"],
                "Уведомление о платеже",
                hours_ago=18,
                body="Уважаемый клиент! На ваш счёт поступил платёж. "
                     "Данное сообщение сформировано автоматически.",
                headers={"Auto-Submitted": "auto-generated", "Precedence": "bulk"},
            ),
        ],
    ),
    DemoScenario(
        code="16",
        title="Рекламная рассылка",
        expected_status=ThreadStatus.NOT_RELEVANT,
        messages=[
            DemoMessage(
                Direction.INCOMING, "news@promo-instrument.com", ["petrova@zubr-demo.ru"],
                "Скидки до 40% на весь ассортимент!",
                hours_ago=15,
                body="Только до конца недели! Успейте купить инструмент со скидкой. "
                     "Отписаться от рассылки можно по ссылке.",
                headers={"List-Unsubscribe": "<mailto:unsub@promo-instrument.com>"},
            ),
        ],
    ),
    DemoScenario(
        code="17",
        title="Менеджер ответил с другого корпоративного адреса",
        expected_status=ThreadStatus.COMPLETED,
        expected_requests={RequestType.PRICE: True, RequestType.AVAILABILITY: True},
        messages=[
            DemoMessage(
                Direction.INCOMING, "zakupki@stankoservis.ru", ["ivanov@zubr-demo.ru"],
                "Цена и наличие фрез",
                hours_ago=24,
                body="Добрый день! Сообщите цену и наличие фрез концевых 12 мм, 25 штук.",
            ),
            DemoMessage(
                Direction.OUTGOING, "sidorov@zubr-demo.ru", ["zakupki@stankoservis.ru"],
                "Re: Цена и наличие фрез",
                hours_ago=22,
                body="Здравствуйте! Отвечаю за коллегу. Фрезы концевые 12 мм — 1 350 руб. за штуку, "
                     "в наличии 40 шт. на складе.",
            ),
        ],
    ),
    DemoScenario(
        code="18",
        title="Переписка началась до анализируемого дня",
        expected_status=ThreadStatus.INCOMPLETE,
        expected_requests={RequestType.INVOICE: False},
        messages=[
            DemoMessage(
                Direction.INCOMING, "office@stroygrad.ru", ["petrova@zubr-demo.ru"],
                "Поставка крепежа",
                hours_ago=200,
                body="Добрый день! Интересует крепёж по спецификации. Какие условия?",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["office@stroygrad.ru"],
                "Re: Поставка крепежа",
                hours_ago=190,
                body="Здравствуйте! Отправляю прайс-лист. Цены от 12 руб. за единицу, "
                     "в наличии на складе.",
            ),
            DemoMessage(
                Direction.INCOMING, "office@stroygrad.ru", ["petrova@zubr-demo.ru"],
                "Re: Поставка крепежа",
                hours_ago=26,
                body="Спасибо! Всё устраивает. Прошу выставить счёт на позиции 1-5 "
                     "из спецификации, количество согласовано.",
            ),
            DemoMessage(
                Direction.OUTGOING, "petrova@zubr-demo.ru", ["office@stroygrad.ru"],
                "Re: Поставка крепежа",
                hours_ago=25,
                body="Добрый день! Принято в работу, вернусь с ответом.",
            ),
        ],
    ),
    DemoScenario(
        code="19",
        title="В теме письма несколько Re: — цепочка должна остаться одной",
        expected_status=ThreadStatus.COMPLETED,
        expected_requests={RequestType.DELIVERY_TIME: True},
        messages=[
            DemoMessage(
                Direction.INCOMING, "proekt@gidromash.ru", ["ivanov@zubr-demo.ru"],
                "Сроки изготовления корпуса",
                hours_ago=30,
                body="Здравствуйте! Какой срок изготовления корпуса по чертежу?",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["proekt@gidromash.ru"],
                "Re: Сроки изготовления корпуса",
                hours_ago=29,
                body="Добрый день! Срок изготовления 15 рабочих дней с момента согласования чертежа.",
            ),
            DemoMessage(
                Direction.INCOMING, "proekt@gidromash.ru", ["ivanov@zubr-demo.ru"],
                "Re: Re: Fwd: Re: Сроки изготовления корпуса",
                hours_ago=28,
                body="Понял, спасибо. Срок нас устраивает.",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["proekt@gidromash.ru"],
                "Re: Re: Re: Fwd: Re: Сроки изготовления корпуса",
                hours_ago=27,
                body="Отлично! Срок 15 рабочих дней зафиксирован, ожидаем согласованный чертёж.",
            ),
        ],
    ),
    DemoScenario(
        code="20",
        title="Цепочку невозможно уверенно определить",
        expected_status=ThreadStatus.NEEDS_REVIEW,
        note="Общая тема, неясный запрос, нет заголовков связи — низкая уверенность.",
        messages=[
            DemoMessage(
                Direction.INCOMING, "a.novikov@mail.ru", ["ivanov@zubr-demo.ru"],
                "По вчерашнему разговору",
                hours_ago=23,
                body="Здравствуйте. По нашему вчерашнему разговору — всё в силе?",
            ),
            DemoMessage(
                Direction.OUTGOING, "ivanov@zubr-demo.ru", ["a.novikov@mail.ru"],
                "Re: По вчерашнему разговору",
                hours_ago=22,
                body="Добрый день! Да, договорённости в силе.",
            ),
        ],
    ),
]


def build_mime(msg: DemoMessage, base_time: datetime, thread_msgid: str | None) -> tuple[bytes, str]:
    """Собирает настоящее MIME-письмо."""
    mail = EmailMessage()
    sender_name = MANAGERS.get(msg.sender, "")
    mail["From"] = f"{sender_name} <{msg.sender}>" if sender_name else msg.sender
    mail["To"] = ", ".join(msg.to)
    mail["Subject"] = msg.subject
    sent_at = base_time - timedelta(hours=msg.hours_ago)
    mail["Date"] = format_datetime(sent_at)
    own_id = make_msgid(domain="demo.local")
    mail["Message-ID"] = own_id
    if thread_msgid:
        mail["In-Reply-To"] = thread_msgid
        mail["References"] = thread_msgid
    for key, value in msg.headers.items():
        mail[key] = value
    mail.set_content(msg.body)

    for filename, mime_type, payload in msg.attachments:
        maintype, _, subtype = mime_type.partition("/")
        mail.add_attachment(payload, maintype=maintype, subtype=subtype, filename=filename)

    return mail.as_bytes(), own_id


def clear_demo_data(db: Session) -> None:
    """Удаляет данные демонстрационного ящика."""
    from app.models import MailAccount

    account = db.scalar(select(MailAccount).where(MailAccount.is_demo.is_(True)))
    if account is None:
        return
    thread_ids = [t.id for t in db.scalars(select(Thread).where(Thread.account_id == account.id)).all()]
    if thread_ids:
        db.execute(delete(CustomerRequest).where(CustomerRequest.thread_id.in_(thread_ids)))
        db.execute(delete(AnalysisResult).where(AnalysisResult.thread_id.in_(thread_ids)))
        db.execute(delete(ManualReview).where(ManualReview.thread_id.in_(thread_ids)))
    message_ids = [m.id for m in db.scalars(select(Message).where(Message.account_id == account.id)).all()]
    if message_ids:
        db.execute(delete(Attachment).where(Attachment.message_id.in_(message_ids)))
    db.execute(delete(Message).where(Message.account_id == account.id))
    db.execute(delete(Thread).where(Thread.account_id == account.id))
    db.commit()


def demo_anchor(db: Session) -> datetime:
    """Точка отсчёта демо-писем: конец последнего рабочего дня (naive UTC).

    Привязка к рабочему дню нужна, чтобы демонстрация показывала осмысленное
    рабочее время ожидания и нарушения сроков, а не нули из-за выходных.
    """
    from datetime import time as dt_time

    from app.services.working_time import WorkSchedule, is_working_day

    schedule = WorkSchedule.from_settings(get_all_settings(db))
    now_local = datetime.now(schedule.tz)
    day = now_local.date()
    for _ in range(14):
        candidate = datetime.combine(day, schedule.end_time, tzinfo=schedule.tz)
        if is_working_day(day, schedule) and candidate <= now_local:
            return datetime(*candidate.utctimetuple()[:6])
        day -= timedelta(days=1)
    # Запасной вариант: текущий момент
    return datetime.utcnow()


def load_demo_data(db: Session, base_time: datetime | None = None) -> dict:
    """Загружает демонстрационные переписки. Возвращает статистику."""
    base_time = base_time or demo_anchor(db)
    clear_demo_data(db)

    account = get_or_create_account(db, demo=True)
    folders: dict[str, MailFolder] = {}
    for folder_type, name in ((FolderType.INBOX, "INBOX"), (FolderType.SENT, "Отправленные")):
        folder = db.scalar(
            select(MailFolder).where(
                MailFolder.account_id == account.id, MailFolder.folder_name == name
            )
        )
        if folder is None:
            folder = MailFolder(
                account_id=account.id,
                folder_name=name,
                display_name=name,
                folder_type=folder_type,
                enabled=True,
            )
            db.add(folder)
            db.flush()
        folders[folder_type] = folder

    # Корпоративные настройки демо-режима
    current = get_all_settings(db)
    domains = set(current.get("corporate.domains") or []) | {DEMO_DOMAIN}
    set_setting(db, "corporate.domains", sorted(domains))
    set_setting(
        db,
        "corporate.managers",
        [{"email": e, "name": n} for e, n in MANAGERS.items()],
    )
    db.commit()

    settings_data = get_all_settings(db)
    corp_domains = {d.lower() for d in settings_data.get("corporate.domains") or []}
    corp_emails = {e.lower() for e in settings_data.get("corporate.emails") or []} | set(MANAGERS)

    created = 0
    uid = 1000
    for scenario in SCENARIOS:
        thread_root: str | None = None
        for demo_msg in scenario.messages:
            raw_bytes, own_id = build_mime(demo_msg, base_time, thread_root)
            if thread_root is None and scenario.code != "20":
                thread_root = own_id
            uid += 1
            folder = folders[
                FolderType.SENT if demo_msg.direction == Direction.OUTGOING else FolderType.INBOX
            ]
            flags = ["\\Seen"] if (demo_msg.is_seen or demo_msg.direction == Direction.OUTGOING) else []
            raw = RawMessage(uid=uid, flags=flags, raw_bytes=raw_bytes, size=len(raw_bytes))
            from email import message_from_bytes

            parsed = parse_message(message_from_bytes(raw_bytes), load_attachment_payload=False)
            message = store_message(
                db, account, folder, raw, parsed,
                corp_domains, corp_emails, settings_data, save_attachments=False,
            )
            if message is not None:
                created += 1
        db.commit()

    for thread in db.scalars(select(Thread).where(Thread.account_id == account.id)).all():
        recompute_thread(db, thread, corp_domains, corp_emails)
    db.commit()

    thread_count = len(
        db.scalars(select(Thread).where(Thread.account_id == account.id)).all()
    )
    logger.info("Демо-данные загружены: писем %s, цепочек %s", created, thread_count)
    return {"messages": created, "threads": thread_count, "scenarios": len(SCENARIOS)}


def expected_results() -> list[dict]:
    """Ожидаемые результаты по каждому сценарию (для проверки и документации)."""
    return [
        {
            "code": s.code,
            "title": s.title,
            "expected_status": s.expected_status,
            "expected_requests": s.expected_requests,
            "note": s.note,
        }
        for s in SCENARIOS
    ]
