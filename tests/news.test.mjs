/**
 * Раздел новостей: лента, рубрики и блок на главной странице.
 *
 * Тексты берутся из docs/news.json — того же источника, из которого
 * собираются обе страницы. Так проверяется, что новости на главной
 * и в ленте не расходятся между собой.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { startSite, ROOT } from './helpers/site.mjs';

let site;

const news = JSON.parse(readFileSync(join(ROOT, 'docs', 'news.json'), 'utf8'));
const labels = new Map(news.categories.map(({ value, label }) => [value, label]));

before(async () => {
  site = await startSite();
});

after(async () => {
  await site.stop();
});

test('в ленте показаны все публикации из источника', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/news`, { waitUntil: 'networkidle' });

  const titles = await page.$$eval('.article-list h2', (els) => els.map((el) => el.textContent.trim()));
  assert.deepEqual(titles, news.publications.map((item) => item.title));

  const summaries = await page.$$eval('.article-list article p', (els) => els.map((el) => el.textContent.trim()));
  assert.deepEqual(summaries, news.publications.map((item) => item.summary));

  await page.context().close();
});

test('каждая публикация ведёт на существующую страницу', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/news`, { waitUntil: 'networkidle' });

  const links = await page.$$eval('.article-list .article-link', (els) =>
    els.map((el) => el.getAttribute('href')),
  );
  assert.deepEqual(links, news.publications.map((item) => item.link));

  for (const link of links) {
    const response = await page.request.get(site.baseUrl + link);
    assert.equal(response.status(), 200, `ссылка ${link} не открывается`);
  }

  await page.context().close();
});

test('рубрики фильтруют ленту', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/news`, { waitUntil: 'networkidle' });

  const buttons = await page.$$eval('.filter-row button[data-category]', (els) =>
    els.map((el) => el.dataset.category),
  );
  const expected = news.showEmptyCategories
    ? news.categories.map(({ value }) => value)
    : [
        'all',
        ...news.categories
          .filter(({ value }) => value !== 'all' && news.publications.some((p) => p.category === value))
          .map(({ value }) => value),
      ];
  assert.deepEqual(buttons, expected);

  for (const value of buttons.filter((v) => v !== 'all')) {
    await page.click(`.filter-row button[data-category="${value}"]`);
    await page.waitForTimeout(50);

    const shown = await page.$$eval('.article-list .article-link', (els) =>
      els.filter((el) => !el.hasAttribute('hidden')).map((el) => el.dataset.itemCategory),
    );
    const inSource = news.publications.filter((item) => item.category === value).length;

    assert.equal(shown.length, inSource, `рубрика «${value}»: показано не столько публикаций`);
    for (const category of shown) {
      assert.equal(category, value, `в рубрике «${value}» показана чужая публикация`);
    }
  }

  await page.context().close();
});

test('на главной странице те же новости, что и в ленте', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  const featured = news.publications.find((item) => item.featured) || news.publications[0];
  const rest = news.publications.filter((item) => item !== featured).slice(0, 2);

  const homeTitles = await page.$$eval('.news-grid h3', (els) => els.map((el) => el.textContent.trim()));
  assert.deepEqual(homeTitles, [featured, ...rest].map((item) => item.title));

  const featuredMeta = await page.$eval('.news-featured span', (el) => el.textContent.trim());
  assert.ok(
    featuredMeta.includes(labels.get(featured.category)),
    `в подписи крупной карточки нет рубрики «${labels.get(featured.category)}»`,
  );

  const featuredLink = await page.$eval('.news-featured a', (el) => el.getAttribute('href'));
  assert.equal(featuredLink, featured.link);

  await page.context().close();
});

test('на сайте не осталось прежних сведений об архиве из девяти комплектов', async () => {
  const page = await site.page({ width: 1440, height: 900 });

  for (const path of ['/', '/news', '/about', '/documents']) {
    await page.goto(site.baseUrl + path, { waitUntil: 'domcontentloaded' });
    const text = await page.textContent('body');

    for (const stale of ['девять комплектов', 'Девять комплектов', 'комплектов документов', '70 страниц']) {
      assert.ok(!text.includes(stale), `на странице ${path} осталось «${stale}»`);
    }
  }

  await page.context().close();
});
