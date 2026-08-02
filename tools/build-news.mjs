/**
 * Сборка раздела новостей.
 *
 * Публикации описаны в docs/news.json. Из одного источника собираются
 * два места, где новости показываются:
 *   /news        — полная лента с переключателями рубрик;
 *   index.html   — блок «Новости партнёрства» на главной странице.
 *
 * Так тексты на главной и в ленте не расходятся между собой.
 *
 * Запуск: npm run news
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const SOURCE = join(ROOT, 'docs', 'news.json');
const NEWS_PAGE = join(ROOT, 'news', 'index.html');
const HOME_PAGE = join(ROOT, 'index.html');

const MONTHS = [
  'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
  'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
];

const escape = (value) =>
  String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

/** «2026-08-02» → «02 августа 2026». */
const formatDate = (iso) => {
  const [year, month, day] = iso.split('-').map(Number);
  return `${String(day).padStart(2, '0')} ${MONTHS[month - 1]} ${year}`;
};

/**
 * Заменяет содержимое блока, найденного по открывающему тегу.
 * Учитывает вложенные <div>, поэтому подходит для больших блоков.
 */
const replaceBlock = (html, openTag, replacement) => {
  const start = html.indexOf(openTag);
  if (start === -1) throw new Error(`Не найден блок ${openTag}`);

  let depth = 0;
  let index = start;
  while (index < html.length) {
    if (html.startsWith('<div', index)) depth += 1;
    else if (html.startsWith('</div>', index)) {
      depth -= 1;
      if (depth === 0) {
        return html.slice(0, start) + replacement + html.slice(index + '</div>'.length);
      }
    }
    index += 1;
  }
  throw new Error(`Не найден конец блока ${openTag}`);
};

/** Лента публикаций на странице /news. */
const buildFeed = (news) => {
  const used = new Set(news.publications.map((item) => item.category));
  const categories = news.showEmptyCategories
    ? news.categories
    : news.categories.filter(({ value }) => value === 'all' || used.has(value));

  const filters = categories
    .map(
      ({ value, label }) =>
        `<button type="button" data-category="${escape(value)}"` +
        `${value === 'all' ? ' class="selected" aria-pressed="true"' : ' aria-pressed="false"'}>` +
        `${escape(label)}</button>`,
    )
    .join('');

  const byValue = new Map(news.categories.map(({ value, label }) => [value, label]));

  const articles = news.publications
    .map((item, index) => {
      const number = String(index + 1).padStart(2, '0');
      return (
        `<a class="article-link" data-item-category="${escape(item.category)}" href="${escape(item.link)}">` +
        '<article>' +
        `<div class="article-number">${number}</div>` +
        '<div>' +
        `<span class="article-meta">${escape(formatDate(item.date))} · ${escape(byValue.get(item.category))}</span>` +
        `<h2>${escape(item.title)}</h2>` +
        `<p>${escape(item.summary)}</p>` +
        '</div>' +
        `<span class="article-more">${escape(item.linkLabel || 'Подробнее')} →</span>` +
        '</article>' +
        '</a>'
      );
    })
    .join('');

  return (
    '<div id="news-feed" data-filter-group data-filter-param="category">' +
    `<div class="filter-row" aria-label="Категории новостей">${filters}</div>` +
    `<div class="article-list" aria-live="polite">${articles}</div>` +
    '<p class="filter-empty" data-filter-empty hidden>' +
    'В этой категории пока нет публикаций. Выберите «Все публикации», ' +
    'чтобы вернуться к полному списку.' +
    '</p>' +
    '</div>'
  );
};

/** Блок «Новости партнёрства» на главной странице. */
const buildHomeGrid = (news) => {
  const byValue = new Map(news.categories.map(({ value, label }) => [value, label]));
  const featured = news.publications.find((item) => item.featured) || news.publications[0];
  const rest = news.publications.filter((item) => item !== featured).slice(0, 2);

  const featuredCard =
    '<article class="news-featured">' +
    `<span>${escape(formatDate(featured.date))} · ${escape(byValue.get(featured.category))}</span>` +
    `<h3>${escape(featured.title)}</h3>` +
    `<p>${escape(featured.summary)}</p>` +
    `<a href="${escape(featured.link)}">${escape(featured.linkLabel || 'Подробнее')} →</a>` +
    '</article>';

  const compactCards = rest
    .map(
      (item) =>
        '<article class="news-compact">' +
        `<span>${escape(item.homeLabel || byValue.get(item.category))}</span>` +
        `<h3>${escape(item.title)}</h3>` +
        `<p>${escape(item.summary)}</p>` +
        '</article>',
    )
    .join('');

  return `<div class="news-grid">${featuredCard}${compactCards}</div>`;
};

const main = () => {
  const news = JSON.parse(readFileSync(SOURCE, 'utf8'));

  const known = new Set(news.categories.map((category) => category.value));
  for (const item of news.publications) {
    if (!known.has(item.category)) {
      throw new Error(`Публикация «${item.title}»: неизвестная рубрика «${item.category}»`);
    }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(item.date)) {
      throw new Error(`Публикация «${item.title}»: дата должна быть в формате ГГГГ-ММ-ДД`);
    }
  }

  const newsHtml = readFileSync(NEWS_PAGE, 'utf8');
  writeFileSync(NEWS_PAGE, replaceBlock(newsHtml, '<div id="news-feed"', buildFeed(news)));

  const homeHtml = readFileSync(HOME_PAGE, 'utf8');
  writeFileSync(HOME_PAGE, replaceBlock(homeHtml, '<div class="news-grid">', buildHomeGrid(news)));

  console.log(
    `Новости собраны: публикаций ${news.publications.length}, ` +
      `рубрик ${news.categories.length}. Обновлены /news и главная страница.`,
  );
};

main();
