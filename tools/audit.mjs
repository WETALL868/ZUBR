/**
 * Аудит вёрстки на всех требуемых ширинах.
 * Запуск: npm run audit  (сайт должен быть поднят: npm run dev)
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8080';

const WIDTHS = [320, 360, 375, 390, 414, 480, 768, 820, 1024, 1440];

const PAGES = [
  ['/', 'Главная'],
  ['/about', 'О партнёрстве'],
  ['/news', 'Новости'],
  ['/documents', 'Документы'],
  ['/meetings', 'Собрания'],
  ['/contacts', 'Контакты'],
  ['/appeal', 'Форма обращения'],
  ['/legal/privacy', 'Политика'],
  ['/legal/cookies', 'Cookie'],
  ['/legal/user-agreement', 'Соглашение'],
  ['/legal/personal-data-consent', 'Согласие'],
  ['/admin/', 'Админ — вход'],
];

/** Собирает проблемы вёрстки на текущей странице. */
const collect = () => {
  const problems = [];
  const vw = document.documentElement.clientWidth;

  /**
   * Элементы, которые пользователь не видит, проверять не нужно:
   *  - содержимое закрытого <details> (браузер считает для него геометрию,
   *    хотя на экране его нет);
   *  - скрытое поле-ловушка для спам-роботов, намеренно вынесенное за экран.
   */
  const hidden = (el) =>
    el.closest('details:not([open]) > *:not(summary)') !== null || el.closest('.honeypot') !== null;

  const scrollW = Math.max(
    document.documentElement.scrollWidth,
    document.body ? document.body.scrollWidth : 0,
  );
  if (scrollW > vw + 1) {
    problems.push({ type: 'h-scroll', detail: `scrollWidth ${scrollW} > viewport ${vw}` });
  }

  const describe = (el) => {
    const id = el.id ? `#${el.id}` : '';
    const cls = typeof el.className === 'string' && el.className
      ? `.${el.className.trim().split(/\s+/).join('.')}`
      : '';
    return `${el.tagName.toLowerCase()}${id}${cls}`.slice(0, 90);
  };

  for (const el of document.querySelectorAll('body *')) {
    const style = getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden' || style.position === 'fixed') continue;
    if (hidden(el)) continue;
    const r = el.getBoundingClientRect();
    if (r.width === 0 || r.height === 0) continue;

    // Выход за правый/левый край экрана
    if (r.right > vw + 1 || r.left < -1) {
      problems.push({
        type: 'overflow',
        detail: `${describe(el)} → left ${Math.round(r.left)}, right ${Math.round(r.right)} (vw ${vw})`,
      });
    }

    // Растяжение/сплющивание картинок
    const image = /** @type {HTMLImageElement} */ (el);
    if (el.tagName === 'IMG' && image.naturalWidth > 0) {
      const fit = style.objectFit;
      const natural = image.naturalWidth / image.naturalHeight;
      const rendered = r.width / r.height;
      if ((fit === 'fill' || fit === '' || fit === 'none') && Math.abs(natural - rendered) / natural > 0.02) {
        problems.push({
          type: 'img-distorted',
          detail: `${describe(el)} object-fit:${fit} natural ${natural.toFixed(2)} vs rendered ${rendered.toFixed(2)}`,
        });
      }
      if (!el.getAttribute('alt') && el.getAttribute('alt') !== '') {
        problems.push({ type: 'img-no-alt', detail: describe(el) });
      }
      if (!el.getAttribute('width') || !el.getAttribute('height')) {
        problems.push({ type: 'img-no-dimensions', detail: `${describe(el)} (риск скачка макета)` });
      }
    }
  }

  // Размер области нажатия
  for (const el of document.querySelectorAll('a[href], button, summary, input, select, textarea, [role="button"]')) {
    const style = getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden') continue;
    if (hidden(el)) continue;
    const r = el.getBoundingClientRect();
    if (r.width === 0 || r.height === 0) continue;
    // Ссылки внутри абзаца — исключение: их нельзя увеличить без разрыва текста
    const inFlow = el.tagName === 'A' && ['P', 'SPAN', 'LI', 'SMALL', 'LABEL', 'DD'].includes(el.parentElement?.tagName);
    if (inFlow) continue;

    // Флажок внутри подписи: нажимается вся подпись, её и проверяем.
    const input = /** @type {HTMLInputElement} */ (el);
    if (el.tagName === 'INPUT' && ['checkbox', 'radio'].includes(input.type)) {
      const label = el.closest('label');
      if (label && label.getBoundingClientRect().height >= 44) continue;
    }
    if (r.height < 44 || r.width < 44) {
      problems.push({
        type: 'tap-target',
        detail: `${describe(el)} ${Math.round(r.width)}×${Math.round(r.height)} (<44)`,
      });
    }
  }

  // Мелкий основной текст.
  // Рубрики, порядковые номера карточек, значок формата файла и строка
  // копирайта — это подписи, а не текст для чтения: они по замыслу мелкие.
  const labelClasses = ['eyebrow', 'eyebrow-light', 'card-index', 'doc-icon', 'notice-label',
    'hero-kicker', 'article-meta', 'catalog-summary', 'test-chip', 'admin-status'];
  for (const el of document.querySelectorAll('p, li, dd, td, label span, small')) {
    const style = getComputedStyle(el);
    if (style.display === 'none' || !el.textContent.trim()) continue;
    if (hidden(el)) continue;
    if (labelClasses.some((name) => el.classList.contains(name))) continue;
    if (el.parentElement?.classList.contains('footer-note') && el.tagName === 'SPAN') continue;
    const size = parseFloat(style.fontSize);
    if (size && size < 14) {
      problems.push({ type: 'small-text', detail: `${describe(el)} ${size}px` });
    }
  }

  return problems;
};

const browser = await chromium.launch();
const report = [];
let total = 0;

for (const [path, name] of PAGES) {
  for (const width of WIDTHS) {
    const context = await browser.newContext({
      viewport: { width, height: width < 768 ? 780 : 900 },
      deviceScaleFactor: 2,
      isMobile: width < 768,
      hasTouch: width < 768,
    });
    const page = await context.newPage();
    const consoleErrors = [];
    page.on('console', (m) => m.type() === 'error' && consoleErrors.push(m.text()));
    page.on('pageerror', (e) => consoleErrors.push(`pageerror: ${e.message}`));

    const response = await page.goto(BASE + path, { waitUntil: 'networkidle' });
    const status = response ? response.status() : 0;
    const problems = status === 200 ? await page.evaluate(collect) : [{ type: 'http', detail: `HTTP ${status}` }];
    for (const e of consoleErrors) problems.push({ type: 'console', detail: e });

    if (problems.length) {
      report.push({ path, name, width, problems });
      total += problems.length;
    }
    await context.close();
  }
}

await browser.close();

// Группируем одинаковые проблемы, чтобы отчёт читался
const grouped = new Map();
for (const row of report) {
  for (const p of row.problems) {
    const key = `${row.path}|${p.type}|${p.detail}`;
    if (!grouped.has(key)) grouped.set(key, { ...row, ...p, widths: [] });
    grouped.get(key).widths.push(row.width);
  }
}

let lastPath = null;
for (const item of [...grouped.values()].sort((a, b) => a.path.localeCompare(b.path) || a.type.localeCompare(b.type))) {
  if (item.path !== lastPath) {
    console.log(`\n${'='.repeat(70)}\n${item.name}  —  ${item.path}`);
    lastPath = item.path;
  }
  console.log(`  [${item.type}] ${item.detail}`);
  console.log(`      ширины: ${item.widths.join(', ')}`);
}

console.log(`\n${'='.repeat(70)}`);
console.log(`Всего замечаний: ${total} (уникальных: ${grouped.size})`);
process.exit(grouped.size > 0 ? 1 : 0);
