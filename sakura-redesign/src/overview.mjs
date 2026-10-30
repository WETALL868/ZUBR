// Обзорный экран этапа 1: сравнение направлений и рекомендация. Используется как index.html и как фрейм Figma.
import { page, asset, ctx } from './common.mjs';

export const CODEX = 'codex://threads/01a188df-b6fc-7ae1-a439-af6f4944bc25';
export const FIGMA = 'https://www.figma.com/design/6aMHGbcuXzqn4Ppb0gl1i0';

export const DIRS = [
  { k: 'A', name: 'Витрина', idea: 'Спокойный магазин с предметной фотографией. Фото задаёт настроение, но занимает ограниченные блоки; сетка разрежённая, тёплые нейтральные поверхности.',
    font: 'Literata + Onest', sample: 'font-family:Literata,serif;font-weight:500', rows: [
      ['Типографика', 'Антиква Literata в заголовках, гротеск Onest в интерфейсе и цифрах'],
      ['Композиция', 'Баннер 1/3 текст и 2/3 фото, подбор поверх нижнего края, каталог плитками с фото'],
      ['Плотность', 'Низкая и средняя: 4 товара в ряд на главной, 3 в каталоге'],
      ['Подача товара', 'Сгенерированные предметные фото категорий, фото товара в карточке на белом'],
      ['Навигация', 'Классическая шапка, тёмная кнопка «Каталог», фильтры слева'],
      ['Телефон', 'Нижняя панель вкладок, фиксированная панель покупки после прокрутки'],
    ], plus: 'Самый «журнальный» вид, сильный первый экран', minus: 'Нужна качественная съёмка; при 2000–3000 SKU фото товаров неоднородны' },
  { k: 'B', name: 'Прайс', idea: 'Строгая коммерческая сетка без декоративной графики. Таблицы, коды в моноширинном шрифте, постоянное дерево каталога, наличие по складам.',
    font: 'IBM Plex Sans + Plex Mono', sample: "font-family:'IBM Plex Sans',sans-serif;font-weight:600", rows: [
      ['Типографика', 'IBM Plex Sans, артикулы и ресурс в Plex Mono с выравниванием цифр'],
      ['Композиция', 'Три колонки на главной: дерево каталога, подбор, условия покупки'],
      ['Плотность', 'Высокая: таблица с ценой, наличием и количеством в строке'],
      ['Подача товара', 'Миниатюры 60 px, акцент на коде, совместимости и складе'],
      ['Навигация', 'Строка категорий, дерево слева, текстовый указатель производителей'],
      ['Телефон', 'Каталог списком уровней, разделы товара аккордеоном, постоянная панель покупки'],
    ], plus: 'Максимум информации на экране, проще всего масштабировать на 3000 SKU', minus: 'Меньше эмоции, подходит покупателю, который знает код' },
  { k: 'C', name: 'Мой принтер', idea: 'Сценарий строится вокруг устройства покупателя: сохранённый принтер в шапке, подбор в три шага, отметка «Подходит» в каталоге и карточке.',
    font: 'Manrope', sample: 'font-family:Manrope,sans-serif;font-weight:800', rows: [
      ['Типографика', 'Геометрический Manrope, плотные заголовки, крупные цифры цены'],
      ['Композиция', 'Светло-красная полоса подбора, карточка «Ваш принтер», компактные категории'],
      ['Плотность', 'Средняя: компактные плитки категорий 96 px, 4 товара в ряд'],
      ['Подача товара', 'Фото на мягкой подложке, строка совместимости с иконкой принтера'],
      ['Навигация', 'Фильтры-чипы над сеткой, «Мой принтер» в шапке, логотипы одним рядом'],
      ['Телефон', 'Кнопка «Подбор» в центре нижней панели, проверка совместимости над фото'],
    ], plus: 'Прямо решает главную задачу: найти совместимый картридж без ошибки', minus: 'Требует справочника совместимости и доработки CMS' },
];

const CSS = `
.O{width:1600px;background:#fff;color:#1F1D1B;font:400 15px/23px Onest,sans-serif;padding:56px 64px 64px}
.O .top{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #E6E1DB;padding-bottom:28px}
.O .k{font-size:12.5px;letter-spacing:.08em;text-transform:uppercase;color:#77716B;display:flex;gap:10px;align-items:center}
.O .k:before{content:"";width:22px;height:2px;background:#C5302D}
.O h1{font:500 40px/48px Literata,serif;margin:12px 0 8px;letter-spacing:-.01em}
.O .sub{color:#46423E;max-width:880px}
.O .meta{font-size:13px;line-height:20px;color:#77716B;text-align:right;max-width:520px}
.O .meta b{color:#1F1D1B;font-weight:600}
.O .cols{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:32px}
.O .col{border:1px solid #E6E1DB;border-radius:16px;padding:24px 26px 22px;display:flex;flex-direction:column}
.O .col.rec{border:2px solid #C5302D}
.O .hd{display:flex;align-items:center;gap:14px}
.O .lt{width:44px;height:44px;border-radius:12px;background:#1F1D1B;color:#fff;font:600 20px/44px Onest,sans-serif;text-align:center}
.O .rec .lt{background:#C5302D}
.O .hd b{font-size:22px;line-height:28px;font-weight:600}
.O .hd small{display:block;font-size:13px;color:#77716B}
.O .badge{margin-left:auto;height:28px;padding:0 12px;border-radius:14px;background:#FBEFEE;color:#A82623;font-size:12.5px;font-weight:600;line-height:28px}
.O .idea{margin:16px 0 14px;color:#46423E}
.O .smp{font-size:34px;line-height:40px;margin:4px 0 12px;letter-spacing:-.01em}
.O .row{display:grid;grid-template-columns:128px 1fr;gap:12px;padding:9px 0;border-top:1px solid #EFEBE6;font-size:14px;line-height:20px}
.O .row span{color:#77716B}
.O .pm{margin-top:14px;display:grid;gap:8px;font-size:14px;line-height:20px}
.O .pm div{display:grid;grid-template-columns:22px 1fr}
.O .pm i{font-style:normal;font-weight:700}
.O .pm .p i{color:#2F7A4C}.O .pm .m i{color:#A82623}
.O .links{margin-top:auto;padding-top:16px;display:flex;flex-wrap:wrap;gap:6px}
.O .links a{height:32px;padding:0 12px;border-radius:16px;border:1px solid #E6E1DB;font-size:13px;line-height:30px;color:#46423E}
.O .recb{margin-top:28px;display:grid;grid-template-columns:1.4fr 1fr;gap:24px}
.O .box{border-radius:16px;background:#F7F5F2;padding:26px 30px}
.O .box h2{font:500 24px/32px Literata,serif;margin:0 0 10px}
.O .box p{margin:0 0 10px;color:#46423E}
.O .box ul{margin:0;padding:0 0 0 18px;color:#46423E}
.O .box li{margin:4px 0}
.O .foot{margin-top:24px;font-size:13px;line-height:20px;color:#77716B;border-top:1px solid #E6E1DB;padding-top:18px;display:flex;justify-content:space-between;gap:24px}
`;

export function overview() {
  const link = (k, f, t) => ctx.mode === 'inline' ? `<a>${t}</a>` : `<a href="${k}-${f}.html">${t}</a>`;
  const body = `<div class="screen O" data-name="00 / Обзор направлений и рекомендация">
<div class="top"><div><div class="k">SAKURA · редизайн магазина · этап 1</div><h1>Три направления для выбора</h1><div class="sub">Главная, фрагмент каталога, карточка CE285A и мобильные экраны в каждом направлении. Отличия в типографике, композиции, плотности, подаче товара и навигации. Цены, остатки и сроки демонстрационные; CMS и платежи не подключались.</div></div>
<div class="meta"><b>30.10.2026 · Claude</b><br>Обратная ссылка на исходный чат Codex:<br>${CODEX}<br>Автоматической синхронизации между чатами нет.<br>Figma: страница «SAKURA / Claude / Варианты 2026-10-30»</div></div>
<div class="cols">${DIRS.map(d => `<section class="col ${d.k === 'C' ? 'rec' : ''}"><div class="hd"><span class="lt">${d.k}</span><span><b>${d.name}</b><small>${d.font}</small></span>${d.k === 'C' ? '<span class="badge">Рекомендация</span>' : ''}</div>
<div class="idea">${d.idea}</div><div class="smp" style="${d.sample}">Картридж CE285A</div>
${d.rows.map(([a, b]) => `<div class="row"><span>${a}</span>${b}</div>`).join('')}
<div class="pm"><div class="p"><i>+</i>${d.plus}</div><div class="m"><i>−</i>${d.minus}</div></div>
<div class="links">${link(d.k, 'home', 'Главная')}${link(d.k, 'catalog', 'Каталог')}${link(d.k, 'product', 'Товар')}${link(d.k, 'm-home', 'Телефон')}${link(d.k, 'm-product', 'Товар, телефон')}${link(d.k, 'm-product-scroll', 'Прокрутка')}</div></section>`).join('')}</div>
<div class="recb"><div class="box"><h2>Рекомендация: C «Мой принтер» с табличным видом из B</h2>
<p>Главная причина покупки в магазине картриджей: найти точно подходящую модель. Направление C делает совместимость основой интерфейса: принтер сохраняется один раз, каталог и поиск сужаются, карточка сразу отвечает «подходит или нет». Это снижает ошибки и возвраты и одинаково работает на телефоне.</p>
<ul><li>Из B взять переключатель «Таблица» в каталоге и наличие по складам: удобно при 2000–3000 SKU и для покупателей, которые знают код.</li><li>Из A взять предметные изображения только для категорий и раскрытого меню, без крупных фото в каждом блоке.</li><li>Логотипы производителей одним рядом с равным визуальным весом, без плиток.</li></ul></div>
<div class="box"><h2>Что нужно для C</h2><p>Справочник «производитель → серия → модель принтера» и связи с SKU уже заложены в модели данных (CartridgeCompatibility). Для выбранной CMS потребуется модуль или доработка сохранения принтера и фильтрации; оценка после согласования платформы.</p><p>После выбора направления: полный магазин для 1440, 1920, 1024, 390 и 360 px, компоненты и переменные Figma, кликабельный прототип и предпросмотр с демо-данными.</p></div></div>
<div class="foot"><span>Дизайн не подтверждает работу торговой CMS: принятых пунктов из 465 по-прежнему 0. Карта экранов и требований: stage1/REQUIREMENTS-MAP.md.</span><span>Изображения категорий и баннера созданы Figma generate_image; логотипы из sources.json без генерации надписей.</span></div></div>`;
  return page({ title: 'SAKURA · этап 1 · направления', families: ['Literata', 'Onest', 'IBM Plex Sans', 'Manrope'], css: CSS, body, width: 1600 });
}
