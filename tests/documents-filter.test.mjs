/**
 * Переключатели категорий в разделе «Документы» и на странице новостей.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { startSite } from './helpers/site.mjs';

let site;

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

  const buttons = await page.$$eval('.filter-row button[data-category]', (els) =>
    els.map((el) => ({ category: el.dataset.category, label: el.textContent.trim() })),
  );
  assert.deepEqual(
    buttons.map((b) => b.label),
    ['Все документы', 'Учредительные', 'Дороги', 'Земля', 'Освещение', 'Финансы'],
  );
  await page.context().close();
});

test('каждая категория показывает только свои документы', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const total = await visibleDocs(page);
  assert.ok(total > 0, 'каталог не должен быть пустым');

  const categories = await page.$$eval('.filter-row button[data-category]', (els) =>
    els.map((el) => el.dataset.category).filter((value) => value !== 'all'),
  );

  let sum = 0;
  for (const category of categories) {
    await page.click(`.filter-row button[data-category="${category}"]`);
    await page.waitForTimeout(50);

    const shown = await visibleDocs(page);
    sum += shown;

    // Показаны только карточки выбранной категории.
    const wrong = await page.$$eval(
      '.document-list article:not([hidden])',
      (items, expected) => items.filter((item) => item.dataset.itemCategory !== expected).length,
      category,
    );
    assert.equal(wrong, 0, `в категории «${category}» показаны чужие документы`);

    // Выбранная категория выделена.
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

  await page.click('.filter-row button[data-category="lighting"]');
  await page.waitForTimeout(50);
  assert.ok((await visibleDocs(page)) < total);

  await page.click('.filter-row button[data-category="all"]');
  await page.waitForTimeout(50);
  assert.equal(await visibleDocs(page), total);
  assert.equal(await selectedLabel(page), 'Все документы');
  await page.context().close();
});

test('выбранная категория сохраняется в адресе и переживает обновление', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  await page.click('.filter-row button[data-category="roads"]');
  await page.waitForTimeout(50);
  assert.equal(new URL(page.url()).searchParams.get('category'), 'roads');

  const shown = await visibleDocs(page);

  await page.reload({ waitUntil: 'networkidle' });
  assert.equal(await selectedLabel(page), 'Дороги', 'после обновления категория потерялась');
  assert.equal(await visibleDocs(page), shown);
  await page.context().close();
});

test('кнопка «Назад» возвращает предыдущую категорию', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  await page.click('.filter-row button[data-category="founding"]');
  await page.waitForTimeout(50);
  await page.click('.filter-row button[data-category="finance"]');
  await page.waitForTimeout(50);
  assert.equal(await selectedLabel(page), 'Финансы');

  await page.goBack();
  await page.waitForTimeout(120);
  assert.equal(await selectedLabel(page), 'Учредительные', 'кнопка «Назад» не вернула прошлую категорию');
  await page.context().close();
});

test('неизвестная категория в адресе не ломает страницу', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/documents?category=несуществующая`, { waitUntil: 'networkidle' });

  assert.equal(await selectedLabel(page), 'Все документы');
  assert.ok((await visibleDocs(page)) > 0);
  assert.deepEqual(page.consoleErrors, []);
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
    const page = await site.page({ width, height: 780 });
    await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

    const overflow = await page.evaluate(() => {
      const viewport = document.documentElement.clientWidth;
      return [...document.querySelectorAll('.filter-row button')]
        .map((button) => button.getBoundingClientRect())
        .filter((rect) => rect.right > viewport + 1 || rect.left < -1).length;
    });
    assert.equal(overflow, 0, `на ширине ${width} кнопки категорий выходят за экран`);

    const tooSmall = await page.$$eval('.filter-row button', (els) =>
      els.filter((el) => el.getBoundingClientRect().height < 44).length,
    );
    assert.equal(tooSmall, 0, `на ширине ${width} кнопки ниже 44 px`);

    await page.context().close();
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
