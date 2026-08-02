/**
 * Сборка страницы /documents.
 *
 * Состав каталога описан в docs/documents-catalog.json, а шапка, футер
 * и подключение стилей берутся из уже существующей страницы сайта —
 * так оболочка гарантированно совпадает с остальными страницами.
 *
 * Кнопка «Скачать» появляется только у тех документов, файл которых
 * действительно лежит в documents/files: ссылок, ведущих на ошибку 404,
 * на сайте быть не должно.
 *
 * Запуск: npm run documents
 */
import { readFileSync, writeFileSync, existsSync, statSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const CATALOG = join(ROOT, 'docs', 'documents-catalog.json');
const SHELL_SOURCE = join(ROOT, 'meetings', 'index.html');
const TARGET = join(ROOT, 'documents', 'index.html');
const FILES_DIR = join(ROOT, 'documents', 'files');

const NAV = [
  ['/', 'Главная'],
  ['/about', 'О партнёрстве'],
  ['/news', 'Новости'],
  ['/documents', 'Документы'],
  ['/meetings', 'Собрания'],
  ['/contacts', 'Контакты'],
];

const escape = (value) =>
  String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const humanSize = (bytes) =>
  bytes >= 1024 * 1024
    ? `${(bytes / 1024 / 1024).toFixed(1)} МБ`
    : `${Math.max(1, Math.round(bytes / 1024))} КБ`;

const buildNav = () =>
  '<nav class="desktop-nav" aria-label="Основная навигация">' +
  NAV.map(([href, label]) =>
    href === '/documents'
      ? `<a class="active" aria-current="page" href="${href}">${label}</a>`
      : `<a class="" href="${href}">${label}</a>`,
  ).join('') +
  '</nav>';

const buildCard = (doc) => {
  const path = join(FILES_DIR, doc.file);
  const present = existsSync(path);

  // Кнопки появляются только у документов, файл которых действительно
  // лежит на диске: просмотр несуществующего файла был бы обманом.
  const status = present
    ? `<span>${escape(doc.kind)} · ${humanSize(statSync(path).size)}</span>` +
      '<div class="doc-actions">' +
      `<button type="button" class="doc-view" data-view-document` +
      ` data-file="/documents/files/${escape(doc.file)}"` +
      ` data-title="${escape(doc.title)}">Посмотреть</button>` +
      `<a class="doc-download" href="/documents/files/${escape(doc.file)}" download>Скачать</a>` +
      '</div>'
    : '<span>Файл готовится к публикации</span>';

  return (
    `<article data-item-category="${escape(doc.category)}" data-file="${escape(doc.file)}">` +
    `<span class="doc-icon" aria-hidden="true">${escape(doc.kind)}</span>` +
    '<div class="doc-main">' +
    `<h2>${escape(doc.title)}</h2>` +
    `<p>${escape(doc.note)}</p>` +
    `<small>Год документа: ${escape(doc.year)}</small>` +
    '</div>' +
    `<div class="doc-status">${status}</div>` +
    '</article>'
  );
};

const buildMain = (catalog) => {
  // Кнопка категории показывается, только если в ней есть документы:
  // переключатель, который всегда приводит к пустому списку, сбивает с толку.
  // Добавятся материалы по дорогам — кнопка «Дороги» появится сама.
  const used = new Set(catalog.documents.map((doc) => doc.category));
  const visibleCategories = catalog.showEmptyCategories
    ? catalog.categories
    : catalog.categories.filter(({ value }) => value === 'all' || used.has(value));

  const filters = visibleCategories
    .map(
      ({ value, label }) =>
        `<button type="button" data-category="${escape(value)}"` +
        `${value === 'all' ? ' class="selected" aria-pressed="true"' : ' aria-pressed="false"'}>` +
        `${escape(label)}</button>`,
    )
    .join('');

  const missing = catalog.documents.filter((doc) => !existsSync(join(FILES_DIR, doc.file)));

  const notice = missing.length
    ? '<div class="privacy-note">' +
      '<strong>Публикация файлов</strong>' +
      `<p>Готовится к публикации документов: ${missing.length}. ` +
      'Файлы появляются в каталоге по мере загрузки — у карточки ' +
      'сразу возникает кнопка «Скачать».</p>' +
      '</div>'
    : '';

  return (
    '<main>' +
    '<section class="page-intro">' +
    '<div class="page-intro-overlay"></div>' +
    '<div class="content-width">' +
    '<span class="eyebrow">Документы</span>' +
    '<h1>Электронный архив партнёрства</h1>' +
    `<p>Учредительные документы партнёрства. Всего в каталоге: ` +
    `${catalog.documents.length}.</p>` +
    '</div>' +
    '</section>' +
    '<section class="content-section content-width">' +
    notice +
    '<div id="catalog" data-filter-group data-filter-param="category">' +
    '<div class="filter-row" aria-label="Категории документов">' +
    filters +
    '</div>' +
    '<p class="catalog-summary" data-filter-summary role="status"></p>' +
    '<div class="document-list">' +
    catalog.documents.map(buildCard).join('') +
    '</div>' +
    '<p class="filter-empty" data-filter-empty hidden>' +
    'В этой категории пока нет документов. Выберите «Все документы», ' +
    'чтобы вернуться к полному списку.' +
    '</p>' +
    '</div>' +
    '</section>' +
    buildViewer() +
    '</main>'
  );
};

/**
 * Окно просмотра документа. Название, ссылки и содержимое подставляет
 * скрипт при нажатии «Посмотреть».
 */
const buildViewer = () =>
  '<dialog class="modal modal-wide" id="document-viewer" aria-labelledby="document-viewer-title">' +
  '<div class="modal-frame">' +
  '<div class="modal-bar">' +
  '<strong id="document-viewer-title">Документ</strong>' +
  // Ссылки «Скачать» и «Открыть в новой вкладке» создаёт скрипт в момент
  // открытия документа. В разметке их нет намеренно: заготовка с href="#"
  // была бы переходом в никуда, а такие ссылки на сайте недопустимы.
  '<div class="modal-bar-actions" data-viewer-actions>' +
  '<button type="button" class="modal-close" data-close-modal>Закрыть</button>' +
  '</div>' +
  '</div>' +
  '<div class="modal-scroll" data-viewer-body></div>' +
  '</div>' +
  '</dialog>';

const main = () => {
  const catalog = JSON.parse(readFileSync(CATALOG, 'utf8'));

  const known = new Set(catalog.categories.map((category) => category.value));
  for (const doc of catalog.documents) {
    if (!known.has(doc.category)) {
      throw new Error(`Документ «${doc.title}»: неизвестная категория «${doc.category}»`);
    }
  }

  const shell = readFileSync(SHELL_SOURCE, 'utf8');
  let head = shell.split('<main>')[0];
  const tail = shell.split('</main>')[1];

  head = head.replace(/<nav class="desktop-nav"[\s\S]*?<\/nav>/, buildNav());
  head = head.replace(
    '<title>СНП «Новая Искань»</title>',
    '<title>Документы — СНП «Новая Искань»</title>',
  );
  head = head.replace(
    /<meta name="description" content="[^"]*"\/>/,
    '<meta name="description" content="Электронный архив документов СНП «Новая Искань»: ' +
      'свидетельство о государственной регистрации и устав партнёрства."/>',
  );

  mkdirSync(dirname(TARGET), { recursive: true });
  writeFileSync(TARGET, head + buildMain(catalog) + tail);

  const published = catalog.documents.filter((doc) => existsSync(join(FILES_DIR, doc.file))).length;
  const used = new Set(catalog.documents.map((doc) => doc.category));
  const shownCategories = catalog.showEmptyCategories
    ? catalog.categories.length
    : catalog.categories.filter(({ value }) => value === 'all' || used.has(value)).length;
  console.log(
    `documents/index.html собрана: документов ${catalog.documents.length}, ` +
      `кнопок категорий ${shownCategories}, файлов опубликовано: ${published}.`,
  );
};

main();
