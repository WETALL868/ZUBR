/**
 * Проверка статических страниц сайта.
 *
 * Ловит то, что легко упустить при ручной правке HTML:
 *  - пустые переходы href="#" и href="";
 *  - ссылки на несуществующие внутренние страницы и файлы;
 *  - картинки без alt;
 *  - потерянное подключение стилей или скрипта;
 *  - вернувшуюся публичную ссылку на административный раздел.
 *
 * Запуск: npm run lint:html
 */
import { readFileSync, existsSync, statSync } from 'node:fs';
import { glob } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseHTML } from 'linkedom';
import { assetVersion } from './asset-version.mjs';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));

/** Номер версии стилей и скрипта — он же стоит в адресах на страницах. */
const VERSION = assetVersion();

/** Пути, которые обслуживает PHP, а не статический файл. */
const PHP_ROUTES = ['/admin', '/admin/', '/api/appeals/'];

const problems = [];

/** Проверяет, что внутренний адрес разрешается в файл или маршрут PHP. */
const resolves = (href) => {
  const path = href.split('#')[0].split('?')[0];
  if (path === '' || PHP_ROUTES.includes(path)) return true;

  const target = join(ROOT, path);
  if (existsSync(target)) {
    if (statSync(target).isDirectory()) {
      return existsSync(join(target, 'index.html')) || existsSync(join(target, 'index.php'));
    }
    return true;
  }
  return false;
};

const pages = [];
for await (const path of glob(`${ROOT}/**/*.html`)) {
  if (path.includes('node_modules')) continue;
  pages.push(path);
}
pages.sort();

for (const path of pages) {
  const name = path.replace(`${ROOT}/`, '');
  const html = readFileSync(path, 'utf8');
  const { document } = parseHTML(html);
  const report = (message) => problems.push(`${name}: ${message}`);

  for (const link of document.querySelectorAll('a')) {
    const href = link.getAttribute('href');
    const text = (link.textContent || '').trim().slice(0, 40);

    if (href === null) {
      report(`ссылка без адреса — «${text}»`);
      continue;
    }
    if (href === '' || href === '#') {
      report(`пустой переход href="${href}" — «${text}»`);
      continue;
    }
    if (href.startsWith('/') && !resolves(href)) {
      report(`ссылка на несуществующий адрес ${href} — «${text}»`);
    }
    if (/(^|\/)admin(\/|$|\?)/i.test(href) || /администратор/i.test(text)) {
      report(`публичная ссылка на административный раздел: ${href} — «${text}»`);
    }
  }

  for (const image of document.querySelectorAll('img')) {
    if (image.getAttribute('alt') === null) {
      report(`картинка без alt: ${image.getAttribute('src')}`);
    }
    const src = image.getAttribute('src');
    if (src && src.startsWith('/') && !resolves(src)) {
      report(`картинка на несуществующий файл: ${src}`);
    }
  }

  for (const source of document.querySelectorAll('source[srcset]')) {
    for (const candidate of source.getAttribute('srcset').split(',')) {
      const url = candidate.trim().split(/\s+/)[0];
      if (url.startsWith('/') && !resolves(url)) {
        report(`source ссылается на несуществующий файл: ${url}`);
      }
    }
  }

  for (const link of document.querySelectorAll('link[rel~="stylesheet"]')) {
    const href = link.getAttribute('href');
    if (href && href.startsWith('/') && !resolves(href)) {
      report(`подключён несуществующий файл стилей: ${href}`);
    }
  }

  for (const script of document.querySelectorAll('script[src]')) {
    const src = script.getAttribute('src');
    if (src.startsWith('/') && !resolves(src)) {
      report(`подключён несуществующий скрипт: ${src}`);
    }
  }

  // Обязательные части общей оболочки.
  if (!html.includes('/assets/site.css')) report('не подключён assets/site.css');
  if (!html.includes('/assets/site.js')) report('не подключён assets/site.js');

  /*
   * Номер версии в адресе стилей и скрипта. Без него браузер (и хостинг)
   * ещё неделю отдаёт файл из кеша, и правки не видно на живом сайте —
   * именно так исправленный футер не появился после загрузки архива.
   */
  for (const asset of ['site.css', 'site.js']) {
    if (!html.includes(`/assets/${asset}?v=${VERSION}`)) {
      report(`у /assets/${asset} нет номера версии ?v=${VERSION} — выполните npm run assets:version`);
    }
  }
  if (!html.includes('viewport-fit=cover')) report('в теге viewport нет viewport-fit=cover');
  if (!document.querySelector('.cookie-banner')) report('нет cookie-уведомления');
  if (!document.querySelector('html[lang]')) report('не указан язык страницы');
  if (!document.querySelector('title')) report('нет заголовка страницы');
}

if (problems.length) {
  console.error('Найдены замечания:\n');
  for (const problem of problems) console.error(`  ${problem}`);
  console.error(`\nВсего: ${problems.length}`);
  process.exit(1);
}

console.log(`Проверено страниц: ${pages.length}. Замечаний нет.`);
