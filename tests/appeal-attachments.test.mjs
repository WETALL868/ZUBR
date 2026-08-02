/**
 * Вложения обращения и формат ответа сервера.
 *
 * Здесь проверяется то, из-за чего форма ломалась на хостинге:
 * сервер обязан отвечать JSON при любом заголовке Accept, а клиент —
 * не падать с техническим сообщением, если ответ всё же оказался HTML.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync, existsSync, readFileSync } from 'node:fs';
import sharp from 'sharp';
import { startSite, TEST_ADMIN } from './helpers/site.mjs';

let site;

/** @type {Record<string, Buffer>} */
const files = {};

before(async () => {
  site = await startSite();

  // Минимальный, но настоящий PDF: сервер определяет тип по содержимому.
  files.pdf = Buffer.from(
    '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n' +
      '2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n' +
      '3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n' +
      'trailer<</Root 1 0 R>>\n%%EOF\n',
    'latin1',
  );

  files.jpg = await sharp({
    create: { width: 600, height: 400, channels: 3, background: { r: 40, g: 90, b: 60 } },
  })
    .jpeg()
    .toBuffer();

  files.png = await sharp({
    create: { width: 600, height: 400, channels: 3, background: { r: 200, g: 180, b: 120 } },
  })
    .png()
    .toBuffer();

  // Запрещённый формат: обычный текст.
  files.txt = Buffer.from('Это не разрешённый формат вложения.\n', 'utf8');

  const MB = 1024 * 1024;
  // Дописывание байтов к JPEG не меняет его сигнатуру: тип по-прежнему
  // определяется как image/jpeg, а размер выходит ровно нужный.
  const pad = (size) => Buffer.concat([files.jpg, Buffer.alloc(size - files.jpg.length, 0x20)]);
  files.almost5 = pad(5 * MB - 4096);
  files.exactly5 = pad(5 * MB);
  files.over5 = pad(5 * MB + 4096);
});

after(async () => {
  await site.stop();
});

const saved = () => (existsSync(site.submissionsDir) ? readdirSync(site.submissionsDir) : []);

/**
 * Отправляет обращение напрямую, как это делает браузер.
 * @param {{ attachment?: {name: string, type: string, data: Buffer}, accept?: string, omit?: string[] }} options
 */
const send = async (options = {}) => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'domcontentloaded' });

  const result = await page.evaluate(
    async ({ base, attachment, accept, omit }) => {
      const body = new FormData();
      const fields = {
        fullName: 'Иван Иванов',
        plotNumber: '12',
        phone: '+7 900 000-00-00',
        email: 'test@example.ru',
        topic: 'Документы',
        message: 'Проверка отправки обращения с вложением и без него.',
        personalConsent: 'on',
        agreementConsent: 'on',
      };
      for (const [key, value] of Object.entries(fields)) {
        if (!(omit || []).includes(key)) body.set(key, value);
      }

      if (attachment) {
        const bytes = Uint8Array.from(atob(attachment.data), (c) => c.charCodeAt(0));
        body.set('attachment', new File([bytes], attachment.name, { type: attachment.type }));
      }

      /** @type {Record<string, string>} */
      const headers = {};
      if (accept !== null) headers.Accept = accept || 'application/json';

      const response = await fetch(`${base}/api/appeals/`, { method: 'POST', body, headers });
      const text = await response.text();
      return {
        status: response.status,
        contentType: response.headers.get('content-type') || '',
        body: text.slice(0, 400),
      };
    },
    {
      base: site.baseUrl,
      accept: options.accept,
      omit: options.omit || [],
      attachment: options.attachment
        ? {
            name: options.attachment.name,
            type: options.attachment.type,
            data: options.attachment.data.toString('base64'),
          }
        : null,
    },
  );

  await page.context().close();

  result.json = result.contentType.includes('application/json') ? JSON.parse(result.body) : null;
  return result;
};

/* ------------------------------------------------------------------ *
 * Формат ответа — причина прежней поломки
 * ------------------------------------------------------------------ */

test('сервер отвечает JSON при любом заголовке Accept', async () => {
  for (const accept of ['application/json', '*/*', 'text/plain', '']) {
    const result = await send({ accept });
    assert.ok(
      result.contentType.includes('application/json'),
      `при Accept «${accept || 'пусто'}» сервер ответил «${result.contentType}»`,
    );
    assert.ok(!result.body.startsWith('<!doctype'), 'ответ начинается с HTML');
    assert.equal(result.json.ok, true);
  }
});

test('обычная отправка формы без JavaScript получает страницу, а не JSON', async () => {
  const result = await send({ accept: 'text/html,application/xhtml+xml' });
  assert.ok(result.contentType.includes('text/html'));
  assert.match(result.body, /Ваше сообщение отправлено/);
});

test('ответ содержит номер обращения в формате N и пяти цифр', async () => {
  const result = await send();
  assert.equal(result.status, 200);
  assert.match(result.json.appealNumber, /^N[0-9]{5}$/);
  assert.equal(result.json.publicId, result.json.appealNumber);
  assert.equal(result.json.message, 'Ваше сообщение отправлено');
});

test('GET по адресу формы отвечает JSON, а не страницей', async () => {
  const page = await site.page();
  const response = await page.request.get(`${site.baseUrl}/api/appeals/`);
  assert.equal(response.status(), 405);
  assert.ok((response.headers()['content-type'] || '').includes('application/json'));
  const payload = await response.json();
  assert.equal(payload.ok, false);
  assert.equal(payload.api, 'appeals');
  await page.context().close();
});

/* ------------------------------------------------------------------ *
 * Вложения
 * ------------------------------------------------------------------ */

test('обращение без вложения принимается', async () => {
  const before = saved().length;
  const result = await send();
  assert.equal(result.status, 200);
  assert.equal(saved().length, before + 1);
});

test('PDF, JPG и PNG принимаются и сохраняются', async () => {
  /** @type {[string, string, Buffer][]} */
  const cases = [
    ['документ.pdf', 'application/pdf', files.pdf],
    ['фото.jpg', 'image/jpeg', files.jpg],
    ['снимок.png', 'image/png', files.png],
  ];

  for (const [name, type, data] of cases) {
    const result = await send({ attachment: { name, type, data } });
    assert.equal(result.status, 200, `${name}: статус ${result.status}, ответ ${result.body}`);

    const number = result.json.appealNumber;
    const stored = `${site.submissionsDir}/${number}.upload.php`;
    assert.ok(existsSync(stored), `вложение ${name} не сохранено`);

    // Первая строка — заглушка, дальше идут байты исходного файла.
    const content = readFileSync(stored);
    const marker = Buffer.from('<?php exit; ?>\n');
    assert.ok(content.subarray(0, marker.length).equals(marker), 'нет защитной заглушки');
    assert.ok(content.subarray(marker.length).equals(data), `${name}: содержимое изменилось`);
  }
});

test('файл почти в 5 МБ принимается', async () => {
  const result = await send({
    attachment: { name: 'почти.jpg', type: 'image/jpeg', data: files.almost5 },
  });
  assert.equal(result.status, 200, `ответ: ${result.body}`);
});

test('файл ровно в 5 МБ принимается', async () => {
  const result = await send({
    attachment: { name: 'ровно.jpg', type: 'image/jpeg', data: files.exactly5 },
  });
  assert.equal(result.status, 200, `ответ: ${result.body}`);
});

test('файл больше 5 МБ отклоняется со статусом 413', async () => {
  const before = saved().length;
  const result = await send({
    attachment: { name: 'много.jpg', type: 'image/jpeg', data: files.over5 },
  });

  assert.equal(result.status, 413, `ответ: ${result.body}`);
  assert.equal(result.json.ok, false);
  assert.match(result.json.error, /5 МБ|размер/i);
  assert.equal(saved().length, before, 'при отказе обращение сохраняться не должно');
});

test('запрещённый формат отклоняется со статусом 415', async () => {
  const before = saved().length;
  const result = await send({
    attachment: { name: 'вирус.txt', type: 'text/plain', data: files.txt },
  });

  assert.equal(result.status, 415, `ответ: ${result.body}`);
  assert.match(result.json.error, /PDF, JPG и PNG/);
  assert.equal(saved().length, before);
});

test('подменённое расширение не обманывает проверку', async () => {
  // Текстовый файл, названный документом: тип определяется по содержимому.
  const result = await send({
    attachment: { name: 'документ.pdf', type: 'application/pdf', data: files.txt },
  });
  assert.equal(result.status, 415, `ответ: ${result.body}`);
});

test('при отказе по вложению не остаётся ни записи, ни файла', async () => {
  const before = saved();
  await send({ attachment: { name: 'много.jpg', type: 'image/jpeg', data: files.over5 } });
  await send({ attachment: { name: 'вирус.txt', type: 'text/plain', data: files.txt } });

  const after = saved();
  assert.deepEqual(after.sort(), before.sort(), 'после отказов появились лишние файлы');
});

/* ------------------------------------------------------------------ *
 * Проверка полей
 * ------------------------------------------------------------------ */

test('без обязательного поля — статус 400', async () => {
  const result = await send({ omit: ['fullName'] });
  assert.equal(result.status, 400);
  assert.equal(result.json.ok, false);
  assert.match(result.json.error, /имя и фамилию/i);
});

test('без обязательного согласия — статус 400', async () => {
  const result = await send({ omit: ['agreementConsent'] });
  assert.equal(result.status, 400);
  assert.match(result.json.error, /согласия/i);
});

/* ------------------------------------------------------------------ *
 * Поведение клиентской части
 * ------------------------------------------------------------------ */

test('если сервер вернул HTML, посетитель видит понятный текст', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  // Имитируем страницу ошибки хостинга вместо ответа API.
  await page.route('**/api/appeals/', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'text/html; charset=utf-8',
      body: '<!doctype html><html><body>Ошибка хостинга</body></html>',
    }),
  );

  await page.fill('input[name=fullName]', 'Иван Иванов');
  await page.selectOption('select[name=topic]', 'Документы');
  await page.fill('textarea[name=message]', 'Проверка ответа неверного формата.');
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');
  await page.click('.submit-button');

  await page.waitForSelector('.simple-alert-error', { timeout: 10000 });
  const text = await page.$eval('.simple-alert-error', (el) => el.textContent.trim());

  assert.match(text, /Сервер вернул некорректный ответ/);
  assert.ok(!text.includes('Unexpected token'), 'посетителю показан технический текст');
  assert.equal(await page.$eval('#appeal-success', (el) => el.open), false);
  assert.equal(await page.$eval('input[name=fullName]', (el) => el.value), 'Иван Иванов');

  await page.context().close();
});

test('при ошибке сервера данные формы сохраняются', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  await page.route('**/api/appeals/', (route) =>
    route.fulfill({
      status: 500,
      contentType: 'application/json; charset=utf-8',
      body: JSON.stringify({ ok: false, error: 'Внутренняя ошибка сервера.' }),
    }),
  );

  await page.fill('input[name=fullName]', 'Пётр Петров');
  await page.fill('input[name=plotNumber]', '77');
  await page.selectOption('select[name=topic]', 'Документы');
  await page.fill('textarea[name=message]', 'Проверка сохранения данных при ошибке.');
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');
  await page.click('.submit-button');

  await page.waitForSelector('.simple-alert-error', { timeout: 10000 });
  assert.equal(await page.$eval('input[name=plotNumber]', (el) => el.value), '77');
  assert.equal(await page.$eval('.submit-button', (el) => el.disabled), false);

  // Повторная отправка возможна: снимаем подмену и отправляем снова.
  await page.unroute('**/api/appeals/');
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });

  await page.context().close();
});

test('слишком большой файл отсекается ещё в браузере', async () => {
  const page = await site.page();
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });

  let requests = 0;
  page.on('request', (request) => {
    if (request.url().includes('/api/appeals/')) requests += 1;
  });

  await page.fill('input[name=fullName]', 'Иван Иванов');
  await page.selectOption('select[name=topic]', 'Документы');
  await page.fill('textarea[name=message]', 'Проверка предварительной проверки размера.');
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');
  await page.setInputFiles('input[name=attachment]', {
    name: 'много.jpg',
    mimeType: 'image/jpeg',
    buffer: files.over5,
  });

  await page.click('.submit-button');
  await page.waitForSelector('.simple-alert-error', { timeout: 10000 });

  const text = await page.$eval('.simple-alert-error', (el) => el.textContent.trim());
  assert.match(text, /5 МБ/);
  assert.equal(requests, 0, 'лишний запрос ушёл на сервер, хотя файл заведомо великоват');

  await page.context().close();
});

/* ------------------------------------------------------------------ *
 * Административный раздел
 * ------------------------------------------------------------------ */

test('вложение открывается в административном разделе', async () => {
  const result = await send({
    attachment: { name: 'схема участка.png', type: 'image/png', data: files.png },
  });
  assert.equal(result.status, 200);
  const number = result.json.appealNumber;

  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/admin/`, { waitUntil: 'networkidle' });
  await page.fill('input[name=login]', TEST_ADMIN.login);
  await page.fill('input[name=password]', TEST_ADMIN.password);
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');

  const body = await page.textContent('body');
  assert.ok(body.includes(number), `обращение ${number} не видно в разделе`);
  assert.ok(body.includes('схема участка.png'), 'имя вложения не показано');

  const download = await page.request.get(`${site.baseUrl}/admin/download.php?id=${number}`);
  assert.equal(download.status(), 200, 'вложение не скачивается');
  assert.ok((download.headers()['content-type'] || '').includes('image/png'));
  assert.equal(Buffer.compare(Buffer.from(await download.body()), files.png), 0, 'файл повреждён');

  await page.context().close();
});

test('вложение не отдаётся по прямой ссылке в обход раздела', async () => {
  const page = await site.page();
  const response = await page.request.get(`${site.baseUrl}/_private/submissions/`);
  assert.ok(response.status() >= 400, 'закрытая папка доступна');
  await page.context().close();
});
