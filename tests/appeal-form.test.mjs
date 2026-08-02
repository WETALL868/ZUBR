/**
 * Отправка обращения: подтверждение, номер, защита от двойной отправки.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync, existsSync } from 'node:fs';
import { startSite, TEST_ADMIN } from './helpers/site.mjs';

let site;

before(async () => {
  site = await startSite();
});

after(async () => {
  await site.stop();
});

const saved = () => (existsSync(site.submissionsDir) ? readdirSync(site.submissionsDir) : []);

/** Заполняет форму корректными данными. */
const fillForm = async (page, message = 'Просьба рассмотреть вопрос освещения на въезде.') => {
  await page.fill('input[name=fullName]', 'Иван Иванов');
  await page.fill('input[name=plotNumber]', '12');
  await page.fill('input[name=phone]', '+7 900 000-00-00');
  await page.fill('input[name=email]', 'test@example.ru');
  await page.selectOption('select[name=topic]', 'Дороги и проезд');
  await page.fill('textarea[name=message]', message);
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');
};

test('успешная отправка показывает подтверждение с коротким номером', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  const before = saved().length;
  await fillForm(page);
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });

  const heading = await page.$eval('#appeal-success h2', (el) => el.textContent.trim());
  assert.equal(heading, 'Ваше сообщение отправлено');

  const number = await page.$eval('[data-appeal-number]', (el) => el.textContent.trim());
  assert.match(number, /^[A-Z][0-9]{5}$/, `номер «${number}» не соответствует формату`);

  // Подтверждение появилось только потому, что обращение действительно сохранено.
  const files = saved();
  assert.equal(files.length, before + 1);
  assert.ok(files.includes(`${number}.record.php`), 'файл обращения с этим номером не найден');

  assert.deepEqual(page.consoleErrors, []);
  await page.context().close();
});

test('обращение появляется в административном разделе под тем же номером', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
  await fillForm(page, 'Вопрос о графике вывоза мусора на территории партнёрства.');
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });
  const number = await page.$eval('[data-appeal-number]', (el) => el.textContent.trim());

  await page.goto(`${site.baseUrl}/admin/`, { waitUntil: 'networkidle' });
  await page.fill('input[name=login]', TEST_ADMIN.login);
  await page.fill('input[name=password]', TEST_ADMIN.password);
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');

  const body = await page.textContent('body');
  assert.ok(body.includes(number), `номер ${number} не найден в административном разделе`);
  await page.context().close();
});

test('форма очищается только после успешной отправки', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  // Слишком короткий текст — сервер отказывает, введённое сохраняется.
  await page.fill('input[name=fullName]', 'Пётр Петров');
  await page.selectOption('select[name=topic]', 'Документы');
  await page.fill('textarea[name=message]', 'а'.repeat(12));
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');

  // Обходим проверку браузера, чтобы дошло до серверной проверки.
  await page.$eval('textarea[name=message]', (el) => {
    el.value = 'коротко';
    el.setAttribute('minlength', '1');
  });

  const before = saved().length;
  await page.click('.submit-button');
  await page.waitForSelector('.simple-alert-error', { timeout: 10000 });

  assert.equal(await page.$eval('#appeal-success', (el) => el.open), false, 'окно успеха не должно появляться при ошибке');
  assert.equal(await page.$eval('input[name=fullName]', (el) => el.value), 'Пётр Петров');
  assert.equal(saved().length, before, 'при ошибке обращение сохраняться не должно');
  await page.context().close();
});

test('двойное нажатие создаёт только одно обращение', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
  await fillForm(page, 'Проверка защиты от повторной отправки обращения.');

  const before = saved().length;

  // Три нажатия подряд, без ожидания ответа сервера.
  await page.click('.submit-button');
  await page.click('.submit-button', { force: true }).catch(() => {});
  await page.click('.submit-button', { force: true }).catch(() => {});

  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });
  await page.waitForTimeout(600);

  assert.equal(saved().length, before + 1, 'создано больше одного обращения');
  await page.context().close();
});

test('кнопка блокируется на время отправки', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
  await fillForm(page, 'Проверка блокировки кнопки на время отправки формы.');

  // Задерживаем ответ, чтобы застать промежуточное состояние кнопки.
  await page.route('**/api/appeals/', async (route) => {
    await new Promise((resolve) => setTimeout(resolve, 700));
    await route.continue();
  });

  await page.click('.submit-button');
  await page.waitForTimeout(200);

  assert.equal(await page.$eval('.submit-button', (el) => el.disabled), true);
  assert.equal(await page.$eval('.submit-button', (el) => el.getAttribute('aria-busy')), 'true');

  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });
  assert.equal(await page.$eval('.submit-button', (el) => el.disabled), false, 'кнопку нужно разблокировать после ответа');
  await page.context().close();
});

test('окно подтверждения закрывается кнопкой и клавишей Escape', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
  await fillForm(page, 'Проверка закрытия окна подтверждения обращения.');
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });

  // Фокус переходит внутрь окна — окно доступно с клавиатуры.
  assert.equal(await page.evaluate(() => document.activeElement?.textContent), 'Закрыть');

  await page.keyboard.press('Escape');
  await page.waitForTimeout(200);
  assert.equal(await page.$eval('#appeal-success', (el) => el.open), false, 'Escape не закрыл окно');

  await fillForm(page, 'Повторная проверка закрытия окна кнопкой «Закрыть».');
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });
  await page.click('[data-close-modal]');
  await page.waitForTimeout(200);
  assert.equal(await page.$eval('#appeal-success', (el) => el.open), false, 'кнопка «Закрыть» не закрыла окно');
  await page.context().close();
});

test('окно подтверждения помещается на экране телефона', async () => {
  for (const viewport of [
    { width: 320, height: 780 },
    { width: 375, height: 812 },
    { width: 414, height: 896 },
    { width: 812, height: 375 },
  ]) {
    const page = await site.page(viewport);
    await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
    await fillForm(page, `Проверка окна подтверждения на ширине ${viewport.width}.`);
    await page.click('.submit-button');
    await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });

    const fits = await page.evaluate(() => {
      const rect = document.querySelector('.modal-window').getBoundingClientRect();
      return (
        rect.left >= -1 &&
        rect.top >= -1 &&
        rect.right <= window.innerWidth + 1 &&
        rect.bottom <= window.innerHeight + 1
      );
    });
    assert.ok(fits, `окно не помещается при ${viewport.width}×${viewport.height}`);
    await page.context().close();
  }
});

test('без согласий обращение не принимается', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  const before = saved().length;
  const response = await page.evaluate(async (base) => {
    const body = new FormData();
    body.set('fullName', 'Без Согласий');
    body.set('topic', 'Документы');
    body.set('message', 'Текст обращения достаточной длины для проверки.');
    const result = await fetch(`${base}/api/appeals/`, {
      method: 'POST',
      body,
      headers: { Accept: 'application/json' },
    });
    return { status: result.status, payload: await result.json() };
  }, site.baseUrl);

  assert.equal(response.status, 422);
  assert.equal(response.payload.ok, false);
  assert.equal(saved().length, before);
  await page.context().close();
});
