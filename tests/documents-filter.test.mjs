/**
 * Переключатели категорий в разделе «Документы» и на странице новостей.
 *
 * Состав каталога меняется, поэтому тесты не зашивают названия категорий,
 * а читают их из docs/documents-catalog.json — того же источника,
 * из которого собирается страница.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { startSite, ROOT } from './helpers/site.mjs';

let site;

const catalog = JSON.parse(readFileSync(join(ROOT, 'docs', 'documents-catalog.json'), 'utf8'));

/** Категории, для которых на странице должны быть кнопки. */
const used = new Set(catalog.documents.map((doc) => doc.category));
const expectedCategories = catalog.showEmptyCategories
  ? catalog.categories
  : catalog.categories.filter(({ value }) => value === 'all' || used.has(value));

before(async () => {
  site = await startSite();
});

after(async () => {
  await site.stop();
});

const visibleDocs = (page) =>
  page.$$eval('.document-list article', (items) => items.filter((item) => !item.hasAttribute('hidden')).length);

const selectedLabel = (page) =>
  page.$eval('.filter-row button.selected', (button) => button.textContent.trim());

test('переключатели категорий не используют неработающие ссылки', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const deadLinks = await page.$$eval('.filter-row a[href="#"], .filter-row a:not([href])', (els) => els.length);
  assert.equal(deadLinks, 0, 'в блоке категорий не должно быть ссылок href="#"');

  const labels = await page.$$eval('.filter-row button[data-category]', (els) =>
    els.map((el) => el.textContent.trim()),
  );
  assert.deepEqual(labels, expectedCategories.map(({ label }) => label));
  await page.context().close();
});

test('кнопки показываются только для категорий, где есть документы', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const shown = await page.$$eval('.filter-row button[data-category]', (els) =>
    els.map((el) => el.dataset.category),
  );

  if (!catalog.showEmptyCategories) {
    for (const value of shown) {
      assert.ok(value === 'all' || used.has(value), `категория «${value}» пуста, а кнопка показана`);
    }
    for (const value of used) {
      assert.ok(shown.includes(value), `для категории «${value}» нет кнопки`);
    }
  }
  await page.context().close();
});

test('каждая категория показывает только свои документы', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const total = await visibleDocs(page);
  assert.equal(total, catalog.documents.length, 'показаны не все документы каталога');

  const categories = expectedCategories.map(({ value }) => value).filter((value) => value !== 'all');

  let sum = 0;
  for (const category of categories) {
    await page.click(`.filter-row button[data-category="${category}"]`);
    await page.waitForTimeout(50);

    const shown = await visibleDocs(page);
    sum += shown;

    const wrong = await page.$$eval(
      '.document-list article:not([hidden])',
      (items, expected) => items.filter((item) => item.dataset.itemCategory !== expected).length,
      category,
    );
    assert.equal(wrong, 0, `в категории «${category}» показаны чужие документы`);

    const pressed = await page.$eval(
      `.filter-row button[data-category="${category}"]`,
      (el) => el.classList.contains('selected') && el.getAttribute('aria-pressed') === 'true',
    );
    assert.ok(pressed, `категория «${category}» не выделена после нажатия`);
  }

  assert.equal(sum, total, 'сумма по категориям не совпала с полным списком');
  await page.context().close();
});

test('«Все документы» возвращает полный список', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });
  const total = await visibleDocs(page);

  const first = expectedCategories.find(({ value }) => value !== 'all');
  await page.click(`.filter-row button[data-category="${first.value}"]`);
  await page.waitForTimeout(50);

  await page.click('.filter-row button[data-category="all"]');
  await page.waitForTimeout(50);
  assert.equal(await visibleDocs(page), total);
  assert.equal(await selectedLabel(page), 'Все документы');
  await page.context().close();
});

test('выбранная категория сохраняется в адресе и переживает обновление', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const target = expectedCategories.find(({ value }) => value !== 'all');
  await page.click(`.filter-row button[data-category="${target.value}"]`);
  await page.waitForTimeout(50);
  assert.equal(new URL(page.url()).searchParams.get('category'), target.value);

  const shown = await visibleDocs(page);

  await page.reload({ waitUntil: 'networkidle' });
  assert.equal(await selectedLabel(page), target.label, 'после обновления категория потерялась');
  assert.equal(await visibleDocs(page), shown);
  await page.context().close();
});

test('неизвестная категория в адресе не ломает страницу', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents?category=несуществующая`, { waitUntil: 'networkidle' });

  assert.equal(await selectedLabel(page), 'Все документы');
  assert.equal(await visibleDocs(page), catalog.documents.length);
  assert.deepEqual(page.consoleErrors, []);
  await page.context().close();
});

test('ссылка «Скачать» есть только у опубликованных файлов', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const cards = await page.$$eval('.document-list article', (items) =>
    items.map((item) => ({
      file: item.dataset.file,
      href: item.querySelector('.doc-status a')?.getAttribute('href') || null,
      status: item.querySelector('.doc-status span')?.textContent.trim() || '',
    })),
  );

  assert.equal(cards.length, catalog.documents.length);

  for (const card of cards) {
    const published = existsSync(join(ROOT, 'documents', 'files', card.file));

    if (published) {
      assert.equal(card.href, `/documents/files/${card.file}`, `у «${card.file}» нет ссылки на скачивание`);
      const response = await page.request.get(site.baseUrl + card.href);
      assert.equal(response.status(), 200, `файл ${card.file} не отдаётся сервером`);
      assert.ok(
        (response.headers()['content-type'] || '').includes('pdf'),
        `файл ${card.file} отдаётся не как PDF`,
      );
    } else {
      // Пока файла нет, ссылки быть не должно: иначе это переход в 404.
      assert.equal(card.href, null, `у неопубликованного «${card.file}» есть ссылка`);
      assert.match(card.status, /готовится к публикации/i);
    }
  }

  await page.context().close();
});

test('кнопка «Назад» возвращает предыдущую категорию на странице новостей', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/news`, { waitUntil: 'networkidle' });

  await page.click('.filter-row button[data-category="documents"]');
  await page.waitForTimeout(50);
  await page.click('.filter-row button[data-category="site"]');
  await page.waitForTimeout(50);
  assert.equal(await selectedLabel(page), 'Сайт');

  await page.goBack();
  await page.waitForTimeout(120);
  assert.equal(await selectedLabel(page), 'Документы', 'кнопка «Назад» не вернула прошлую категорию');
  await page.context().close();
});

test('пустая категория показывает пояснение вместо голого экрана', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/news`, { waitUntil: 'networkidle' });

  // На странице новостей категория «Собрания» пока без публикаций.
  await page.click('.filter-row button[data-category="meetings"]');
  await page.waitForTimeout(50);

  const shown = await page.$$eval('.article-list .article-link', (items) =>
    items.filter((item) => !item.hasAttribute('hidden')).length,
  );
  assert.equal(shown, 0);

  const noticeVisible = await page.$eval('[data-filter-empty]', (el) => !el.hasAttribute('hidden'));
  assert.ok(noticeVisible, 'для пустой категории нужно пояснение');
  await page.context().close();
});

test('на телефоне кнопки категорий не выходят за экран', async () => {
  for (const width of [320, 360, 375, 390, 414, 480]) {
    for (const path of ['/documents', '/news']) {
      const page = await site.page({ width, height: 780 });
      await page.goto(site.baseUrl + path, { waitUntil: 'networkidle' });

      const overflow = await page.evaluate(() => {
        const viewport = document.documentElement.clientWidth;
        return [...document.querySelectorAll('.filter-row button')]
          .map((button) => button.getBoundingClientRect())
          .filter((rect) => rect.right > viewport + 1 || rect.left < -1).length;
      });
      assert.equal(overflow, 0, `${path} на ширине ${width}: кнопки категорий выходят за экран`);

      const tooSmall = await page.$$eval('.filter-row button', (els) =>
        els.filter((el) => el.getBoundingClientRect().height < 44).length,
      );
      assert.equal(tooSmall, 0, `${path} на ширине ${width}: кнопки ниже 44 px`);

      await page.context().close();
    }
  }
});

test('карточки документов помещаются на мобильном экране', async () => {
  const page = await site.page({ width: 320, height: 780 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const overflow = await page.evaluate(() => {
    const viewport = document.documentElement.clientWidth;
    return [...document.querySelectorAll('.document-list article, .document-list article *')]
      .map((el) => el.getBoundingClientRect())
      .filter((rect) => rect.width > 0 && (rect.right > viewport + 1 || rect.left < -1)).length;
  });
  assert.equal(overflow, 0);
  await page.context().close();
});
