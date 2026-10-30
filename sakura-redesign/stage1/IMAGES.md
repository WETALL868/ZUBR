# Изображения и логотипы. Этап 1

## Созданные изображения

Генерация выполнена инструментом Figma MCP `generate_image`, модель `gpt-image-2.5-flare`, 30.10.2026. Генерация расходует AI-кредиты тарифа Figma владельца файла: использовано 7 вызовов. Изображения размещены в Figma на странице «SAKURA / Claude / Варианты 2026-10-30», секция «Источники / Сгенерированные изображения Claude 2026-10-30» (узел [66:242](https://www.figma.com/design/6aMHGbcuXzqn4Ppb0gl1i0?node-id=66-242)).

Текста, кнопок, логотипов и характеристик внутри растров нет. Генеративные изображения не обозначаются как фото конкретного SKU.

| Файл (исходник PNG) | Версия для макета | Узел Figma | Размер | Назначение |
|---|---|---|---|---|
| `assets/generated/source/hero-a.png` | `assets/generated/web/hero-a.jpg` | 45:242 | 1536×1024 | Главный баннер направления A |
| `assets/generated/source/cat-laser.png` | `web/cat-laser.jpg` | 45:243 | 1024×1024 | Категория «Лазерные картриджи» |
| `assets/generated/source/cat-ink.png` | `web/cat-ink.jpg` | 45:244 | 1024×1024 | Категория «Струйные картриджи» |
| `assets/generated/source/cat-screens.png` | `web/cat-screens.jpg` | 45:245 | 1024×1024 | Категория «Проекционные экраны» |
| `assets/generated/source/cat-photo.png` | `web/cat-photo.jpg` | 45:246 | 1024×1024 | Категория «Фотобумага» |
| `assets/generated/source/cat-roll.png` | `web/cat-roll.jpg` | 45:247 | 1024×1024 | Категория «Широкоформатная бумага» |
| `assets/generated/source/cat-mounts.png` | `web/cat-mounts.jpg` | 45:248 | 1024×1024 | Категория «Крепления» |

Альфа-канал. `generate_image` в Figma не возвращает прозрачный фон: все изображения RGB на ровном тёплом фоне около #F0EDE9. Подложки плиток в макетах окрашены в тот же цвет, поэтому край изображения не виден. Пробное автоматическое отделение фона дало ореолы у белых предметов и в макеты не включено. Для этапа 2, если понадобится вырезка, нужна генерация с прозрачным фоном или ручная обработка.

### Промпты

hero-a (1536×1024):
> Quiet premium commercial product photograph for an online store of printer supplies. On a pale warm off-white tabletop (#F3F1EE) against a seamless matching backdrop: a compact modern white laser printer at the right, in front of it a single generic black toner cartridge lying on its side, and a low neat stack of white A4 paper; one thin sheet of deep red paper (#C5302D) partly under the stack as the only color accent. Soft natural daylight from the upper left, gentle long soft shadows, high-end catalog look, calm and minimal, lots of empty background on the left third. Realistic materials, sharp focus, slight elevated three-quarter camera angle, 50mm. No text, no letters, no logos, no labels, no brand marks, no ribbons, no curls, no people, no props other than listed.

Общая основа категорий (1024×1024): «Minimal premium e-commerce category photograph, square. [предмет], centered with generous empty margins, on a seamless pale warm off-white background (exact flat color #F3F1EE edge to edge), soft diffused daylight from the upper left, soft contact shadow. Realistic, sharp focus, catalog style, calm. No text, no letters, no logos, no labels, no ribbons, no curls, no props, no gradient vignette.» Предметы:

- cat-laser: A single generic black laser toner cartridge, three-quarter view (matte plastic, no stickers).
- cat-ink: Four generic inkjet printer ink cartridges standing in a neat row, black, cyan, magenta and yellow bodies, three-quarter view.
- cat-screens: A portable projection screen on a black tripod stand, matte white screen surface with thin black border, front three-quarter view, whole object visible; nothing projected on the screen, no projector.
- cat-photo: A small fanned stack of glossy photo paper prints, top print shows a calm natural landscape photo with a lake and pale mountains in soft muted colors, white borders on the sheets, slight three-quarter overhead view, subtle gloss highlight.
- cat-roll: A single large roll of white matte wide-format plotter paper on a cardboard core, lying horizontally, slightly unrolled at the front, three-quarter view.
- cat-mounts: A single ceiling projector mount: a square white metal pole with a flat ceiling plate on top and an adjustable mounting bracket with four arms at the bottom, standing upright as a product shot, three-quarter view; no projector attached.

## Изображения из концепта 05 (повторное использование)

| Файл | Происхождение | Где использовано |
|---|---|---|
| `assets/concept05/footer-fullwidth-alpha.png` | Фон подвала концепта 05, встроенный image_gen Codex, PNG 2172×724 с альфа-каналом; промпт в архиве: concept/assets/v5/footer-alpha-prompt.txt | Подвал направления A, прозрачность 38%, за ссылками, без отдельной карточки |
| `assets/concept05/header-line.jpg`, `printer-line.jpg`, `set-line.jpg` | Линейный рисунок шапки концепта 05 (dist/assets/v5/header.webp) и его кадрирования | Карточка «Ваш принтер» в направлении C |

## Фотографии товаров

Упаковки картриджей SAKURA взяты с текущего магазина sakurarussia.ru (миниатюры 970 px из каталога «Совместимые картриджи HP», 30.10.2026) и из архива концепта 05 (sakura-product.jpg, tk1170.jpg, tk1200.jpg, w1106a.jpg). Используются только в закрытом макете. Кадры `ce285a-label.jpg` и `ce285a-end.jpg` вырезаны из той же фотографии упаковки CE285A для демонстрации галереи; других ракурсов этого товара в источниках нет. Для второго товара CE285A показано состояние «Фото появится после загрузки».

## Логотипы производителей

Источники: `assets/brands/sources.json` (Wikimedia Commons, для Pantum официальный сайт). Надписи логотипов не генерировались. Для стабильного переноса в Figma SVG переведены в PNG с прозрачностью без изменения пропорций (`assets/brands/png/`), обрезаны по содержимому. Размер в макетах рассчитан по равной площади, HP ограничен по высоте.

Исправление: в исходном `samsung.svg` прямоугольник охранного поля имел чёрную заливку, из-за чего логотип выглядел белым на чёрной плашке. В `samsung-blue.svg` охранное поле прозрачное, надпись окрашена в фирменный синий #1428A0. Перед публикацией цвета и использование логотипов стоит сверить с руководствами брендов.
