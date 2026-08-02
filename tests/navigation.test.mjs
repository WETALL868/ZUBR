/**
 * Переходы по сайту, мобильное меню и отсутствие публичной ссылки
 * на административный раздел.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { startSite, sitePages } from './helpers/site.mjs';

let site;

before(async () => {
  site = await startSite();
});

after(async () => {
  await site.stop();
});

test('на публичных страницах нет ссылки на административный раздел', async () => {
  const page = await site.page();

  for (const path of sitePages) {
    await page.goto(site.baseUrl + path, { waitUntil: 'domcontentloaded' });

    const adminLinks = await page.$$eval('a[href]', (links) =>
      links
        .map((link) => ({
          href: link.getAttribute('href') || '',
          text: (link.textContent || '').trim(),
        }))
        .filter(
          (link) =>
            /(^|\/)admin(\/|$|\?)/i.test(link.href) ||
            /администратор/i.test(link.text) ||
            /Вход для администратора|Администратору/i.test(link.text),
        ),
    );

    assert.deepEqual(adminLinks, [], `на странице ${path} осталась ссылка на административный раздел`);
  }

  await page.context().close();
});

test('административный раздел продолжает работать по прямому адресу', async () => {
  const page = await site.page();
  const response = await page.goto(`${site.baseUrl}/admin/`, { waitUntil: 'networkidle' });

  assert.equal(response.status(), 200, 'административный раздел должен оставаться доступен');
  assert.ok(
    (await page.textContent('h1')).includes('Вход администратора'),
    'форма входа администратора должна сохраниться',
  );
  assert.equal(await page.$$eval('input[name=login]', (els) => els.length), 1);
  assert.equal(await page.$$eval('input[name=password]', (els) => els.length), 1);
  await page.context().close();
});

test('все внутренние ссылки ведут на существующие страницы', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  const checked = new Map();
  const problems = [];

  for (const path of [...sitePages, '/documents?category=roads']) {
    await page.goto(site.baseUrl + path, { waitUntil: 'domcontentloaded' });

    const links = await page.$$eval('a[href]', (elements) =>
      elements.map((element) => ({
        href: element.getAttribute('href') || '',
        text: (element.textContent || '').trim().slice(0, 40),
      })),
    );

    for (const link of links) {
      // Пустые переходы недопустимы.
      assert.ok(link.href !== '' && link.href !== '#', `пустая ссылка «${link.text}» на странице ${path}`);

      if (!link.href.startsWith('/')) continue;
      if (checked.has(link.href)) continue;

      const response = await page.request.get(site.baseUrl + link.href);
      checked.set(link.href, response.status());
      if (response.status() >= 400) {
        problems.push(`${link.href} → HTTP ${response.status()} (со страницы ${path})`);
      }
    }
  }

  assert.deepEqual(problems, [], `неработающие ссылки:\n${problems.join('\n')}`);
  assert.ok(checked.size > 10, 'ожидалось больше проверенных ссылок');
  await page.context().close();
});

test('страницы открываются без ошибок в консоли', async () => {
  for (const path of sitePages) {
    const page = await site.page();
    await page.goto(site.baseUrl + path, { waitUntil: 'networkidle' });
    assert.deepEqual(page.consoleErrors, [], `ошибки консоли на странице ${path}`);
    await page.context().close();
  }
});

test('мобильное меню открывается и закрывается', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  const isOpen = () => page.$eval('.mobile-menu', (el) => el.hasAttribute('open'));
  const scrollLocked = () => page.$eval('body', (el) => el.classList.contains('menu-open'));

  assert.equal(await isOpen(), false);

  await page.click('.mobile-menu summary');
  await page.waitForTimeout(120);
  assert.equal(await isOpen(), true, 'меню не открылось');
  assert.equal(await scrollLocked(), true, 'страница под открытым меню должна быть заблокирована');
  assert.equal(await page.$$eval('.menu-close', (els) => els.length), 1, 'нет кнопки закрытия');

  // Кнопка закрытия.
  await page.click('.menu-close');
  await page.waitForTimeout(120);
  assert.equal(await isOpen(), false, 'кнопка закрытия не сработала');
  assert.equal(await scrollLocked(), false);

  // Клавиша Escape.
  await page.click('.mobile-menu summary');
  await page.waitForTimeout(120);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(120);
  assert.equal(await isOpen(), false, 'Escape не закрыл меню');

  // Нажатие вне меню.
  await page.click('.mobile-menu summary');
  await page.waitForTimeout(120);
  await page.mouse.click(20, 500);
  await page.waitForTimeout(120);
  assert.equal(await isOpen(), false, 'нажатие вне меню не закрыло его');

  await page.context().close();
});

test('меню закрывается после перехода по пункту', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  await page.click('.mobile-menu summary');
  await page.waitForTimeout(120);
  await page.click('.mobile-menu nav a[href="/documents"]');
  await page.waitForLoadState('networkidle');

  assert.equal(new URL(page.url()).pathname, '/documents');
  assert.equal(await page.$eval('.mobile-menu', (el) => el.hasAttribute('open')), false);
  assert.equal(await page.$eval('body', (el) => el.classList.contains('menu-open')), false);
  await page.context().close();
});

test('в мобильном меню есть все разделы сайта', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  const items = await page.$$eval('.mobile-menu nav a', (links) => links.map((link) => link.getAttribute('href')));
  for (const expected of ['/', '/about', '/news', '/documents', '/meetings', '/contacts', '/appeal']) {
    assert.ok(items.includes(expected), `в мобильном меню нет пункта ${expected}`);
  }
  await page.context().close();
});

test('cookie-уведомление не перекрывает меню и основные кнопки', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  await page.waitForSelector('.cookie-banner:not([hidden])', { timeout: 5000 });

  // Кнопка меню остаётся доступной для нажатия.
  await page.click('.mobile-menu summary');
  await page.waitForTimeout(150);
  assert.equal(await page.$eval('.mobile-menu', (el) => el.hasAttribute('open')), true);

  // Проверяем не числа z-index, а то, что реально лежит сверху:
  // из-за отдельного слоя у шапки числа могут вводить в заблуждение.
  const topmost = await page.evaluate(() => {
    const point = (x, y) => {
      const element = document.elementFromPoint(x, y);
      return element ? element.closest('.cookie-banner, .menu-backdrop, .mobile-menu')?.className || '' : '';
    };
    return {
      слева: point(20, 500),
      подПанелью: point(20, 760),
      панель: point(300, 400),
    };
  });
  assert.ok(
    !topmost.слева.includes('cookie-banner') && !topmost.подПанелью.includes('cookie-banner'),
    `cookie-уведомление перекрывает открытое меню: ${JSON.stringify(topmost)}`,
  );

  await page.keyboard.press('Escape');
  await page.waitForTimeout(120);

  // Выбор запоминается и на других страницах баннер больше не появляется.
  await page.click('.cookie-banner [data-cookie-choice]');
  await page.waitForTimeout(120);
  assert.equal(await page.$eval('.cookie-banner', (el) => el.hasAttribute('hidden')), true);

  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(200);
  assert.equal(await page.$eval('.cookie-banner', (el) => el.hasAttribute('hidden')), true, 'выбор не запомнился');

  await page.context().close();
});
