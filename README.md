# LANCOR — лендинг «Настройка локальной сети и интернета для офиса»

Статический лендинг под трафик из Яндекс.Директ. Без сборки, без зависимостей,
без внешних запросов — распаковать и залить на любой хостинг.

## Структура

```
index.html            главная страница
privacy.html          политика конфиденциальности (152-ФЗ)
robots.txt
sitemap.xml
assets/
  css/style.css       вся стилистика и дизайн-система
  js/main.js          меню, scroll-reveal, sticky CTA, форма
  img/favicon.svg
  img/apple-touch-icon.png
  img/og-cover.png    1200×630 для соцсетей и мессенджеров
```

## Что заменить перед публикацией

| Что | Где |
|---|---|
| Телефон `+7 (495) 000-00-00` | `index.html`, `privacy.html` |
| E-mail `info@lancor.ru` | `index.html`, `privacy.html` |
| Домен `lancor.ru` | `canonical`, `og:url`, `og:image`, `robots.txt`, `sitemap.xml` |
| Название `LANCOR` и реквизиты оператора ПДн | `index.html`, `privacy.html` |
| Регион обслуживания | секция контактов, `privacy.html` |

## Подключение формы заявок

В `assets/js/main.js` в начале файла:

```js
var LEAD_ENDPOINT = null; // -> "/api/lead"
```

Пока значение `null`, форма валидируется и показывает успех локально, никуда не
отправляя данные. Укажите свой обработчик — он должен принимать `POST` с JSON:

```json
{ "name": "...", "phone": "...", "seats": "...", "task": "...", "intent": "...", "page": "..." }
```

Поле `intent` содержит текст кнопки, с которой пришёл пользователь
(«Получить консультацию» / «Заказать настройку») — удобно для аналитики Директа.

Защита от спама: скрытое honeypot-поле `contact_reference`. Оно намеренно **не**
называется `company`/`organization` — такие имена заполняет автозаполнение
браузера, из-за чего реальные заявки молча отбрасывались бы.

### Цель в Яндекс.Метрике

После успешной отправки вызывается `reachGoal('lead_form_submit')`, если на
странице подключена Метрика и задан `window.__ymCounterId`:

```html
<script>window.__ymCounterId = 12345678;</script>
```

## HTTP-заголовки безопасности

Страница не делает внешних запросов, поэтому политику можно задать жёстко.
Настраивается на стороне хостинга/веб-сервера (в `<meta>` вынесено намеренно не
было, чтобы не ломать статику при изменении разметки).

Nginx:

```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header X-Frame-Options "DENY" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

`'unsafe-inline'` для script нужен из-за инлайнового `class="js"`-переключателя в
`<head>` и JSON-LD; при подключении Метрики добавьте её домены в `script-src`,
`img-src` и `connect-src`.

Apache (`.htaccess`) — те же значения через `Header always set`.

## Производительность

Проверено в Chromium: LCP ≈ 0.16 с, CLS = 0, горизонтального скролла нет на
ширинах 320–1920 px. Страница полностью читается с отключённым JavaScript —
анимации появления включаются только при наличии класса `js` на `<html>`.
