/**
 * Схема проезда на странице «Контакты» и просмотр документов.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { startSite, ROOT } from './helpers/site.mjs';

let site;

const catalog = JSON.parse(readFileSync(join(ROOT, 'docs', 'documents-catalog.json'), 'utf8'));

before(async () => {
  site = await startSite();
});

after(async () => {
  await site.stop();
});

/* ------------------------------------------------------------------ *
 * Схема проезда
 * ------------------------------------------------------------------ */

test('на странице «Контакты» есть блок «Как нас найти»', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });

  assert.equal(
    await page.$eval('.contact-map-head h2', (el) => el.textContent.trim()),
    'Как нас найти',
  );
  assert.equal(
    await page.$eval('.contact-map-head p', (el) => el.textContent.trim()),
    'Схема расположения и проезда к СНП «Новая Искань»',
  );

  const image = await page.$eval('.contact-map-button img', (el) => ({
    alt: el.getAttribute('alt') || '',
    width: el.getAttribute('width'),
    height: el.getAttribute('height'),
    loading: el.getAttribute('loading'),
  }));

  assert.ok(image.alt.length > 30, 'у схемы должно быть понятное описание');
  assert.ok(image.width && image.height, 'без размеров страница будет дёргаться при загрузке');
  assert.equal(image.loading, 'lazy');

  assert.deepEqual(page.consoleErrors, []);
  await page.context().close();
});

test('схема не растянута и не сплющена', async () => {
  for (const width of [320, 375, 414, 768, 820, 1024, 1440]) {
    const page = await site.page({ width, height: 900 });
    await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });

    const check = await page.evaluate(() => {
      const image = /** @type {HTMLImageElement} */ (
        document.querySelector('.contact-map-button img')
      );
      const rect = image.getBoundingClientRect();
      const style = getComputedStyle(image);
      const natural = image.naturalWidth / image.naturalHeight;
      const rendered = rect.width / rect.height;
      return {
        fit: style.objectFit,
        distortion: Math.abs(natural - rendered) / natural,
        withinScreen: rect.left >= -1 && rect.right <= document.documentElement.clientWidth + 1,
        height: rect.height,
      };
    });

    // Либо пропорции сохранены, либо кадр обрезан через cover —
    // растяжения (object-fit: fill) быть не должно.
    assert.ok(
      check.fit === 'cover' || check.distortion < 0.02,
      `ширина ${width}: схема искажена (object-fit: ${check.fit})`,
    );
    assert.ok(check.withinScreen, `ширина ${width}: схема выходит за экран`);
    assert.ok(check.height > 120, `ширина ${width}: схема слишком мелкая (${check.height} px)`);

    await page.context().close();
  }
});

test('схема увеличивается по нажатию и закрывается тремя способами', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });

  const isOpen = () => page.$eval('#map-modal', (el) => el.open);

  // Кнопка «Закрыть».
  await page.click('[data-open-map]');
  await page.waitForTimeout(200);
  assert.equal(await isOpen(), true, 'окно не открылось');
  await page.click('#map-modal [data-close-modal]');
  await page.waitForTimeout(200);
  assert.equal(await isOpen(), false, 'кнопка «Закрыть» не сработала');

  // Клавиша Escape.
  await page.click('[data-open-map]');
  await page.waitForTimeout(200);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(200);
  assert.equal(await isOpen(), false, 'Escape не закрыл окно');

  // Нажатие по затемнённому фону.
  await page.click('[data-open-map]');
  await page.waitForTimeout(200);
  await page.evaluate(() => {
    const dialog = document.querySelector('#map-modal');
    dialog.dispatchEvent(new MouseEvent('click', { bubbles: true }));
  });
  await page.waitForTimeout(200);
  assert.equal(await isOpen(), false, 'нажатие по фону не закрыло окно');

  await page.context().close();
});

test('увеличенная схема помещается на экране и прокручивается', async () => {
  for (const [width, height] of [
    [320, 780],
    [375, 812],
    [768, 1024],
    [1440, 900],
  ]) {
    const page = await site.page({ width, height });
    await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });
    await page.click('[data-open-map]');
    await page.waitForTimeout(250);

    const check = await page.evaluate(() => {
      const frame = document.querySelector('#map-modal .modal-frame').getBoundingClientRect();
      const scroll = document.querySelector('.map-scroll');
      return {
        fits:
          frame.left >= -1 &&
          frame.top >= -1 &&
          frame.right <= window.innerWidth + 1 &&
          frame.bottom <= window.innerHeight + 1,
        scrollable: scroll.scrollWidth > scroll.clientWidth || scroll.scrollHeight > scroll.clientHeight,
      };
    });

    assert.ok(check.fits, `ширина ${width}: окно выходит за границы экрана`);
    if (width < 900) {
      assert.ok(check.scrollable, `ширина ${width}: увеличенную схему нельзя подвинуть`);
    }

    await page.context().close();
  }
});

test('кнопка «Открыть в новой вкладке» ведёт на существующий файл', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });

  const href = await page.$eval('#map-modal [href]', (el) => el.getAttribute('href'));
  const response = await page.request.get(site.baseUrl + href);
  assert.equal(response.status(), 200, `файл схемы ${href} не отдаётся`);
  assert.ok((response.headers()['content-type'] || '').startsWith('image/'));

  await page.context().close();
});

/* ------------------------------------------------------------------ *
 * Просмотр документов
 * ------------------------------------------------------------------ */

test('у каждого опубликованного документа есть «Посмотреть» и «Скачать»', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const cards = await page.$$eval('.document-list article', (items) =>
    items.map((item) => ({
      title: item.querySelector('h2').textContent.trim(),
      view: item.querySelector('[data-view-document]')?.getAttribute('data-file') || null,
      download: item.querySelector('.doc-download')?.getAttribute('href') || null,
      status: item.querySelector('.doc-status span')?.textContent.trim() || '',
    })),
  );

  assert.equal(cards.length, catalog.documents.length);

  for (const card of cards) {
    if (card.status.includes('готовится')) {
      // Просмотр несуществующего файла был бы обманом.
      assert.equal(card.view, null, `у неопубликованного «${card.title}» есть просмотр`);
      assert.equal(card.download, null);
      continue;
    }

    assert.ok(card.view, `у «${card.title}» нет кнопки «Посмотреть»`);
    assert.equal(card.view, card.download, 'просмотр и скачивание должны вести на один файл');

    const response = await page.request.get(site.baseUrl + card.view);
    assert.equal(response.status(), 200, `файл ${card.view} не отдаётся`);
    assert.ok((response.headers()['content-type'] || '').includes('pdf'));
  }

  await page.context().close();
});

test('кнопка просмотра выделена сильнее кнопки скачивания', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const styles = await page.evaluate(() => {
    const view = getComputedStyle(document.querySelector('.doc-view'));
    const download = getComputedStyle(document.querySelector('.doc-download'));
    return { view: view.backgroundColor, download: download.backgroundColor };
  });

  assert.notEqual(styles.view, styles.download, 'кнопки выглядят одинаково');
  assert.notEqual(styles.view, 'rgba(0, 0, 0, 0)', 'основное действие должно быть заливным');

  await page.context().close();
});

test('каждый документ открывается на просмотр с первой страницы', async () => {
  for (const [width, height] of [
    [375, 812],
    [1440, 900],
  ]) {
    const page = await site.page({ width, height });
    await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

    const buttons = await page.$$('[data-view-document]');
    assert.ok(buttons.length > 0, 'нет ни одной кнопки просмотра');

    for (let index = 0; index < buttons.length; index += 1) {
      const expectedTitle = await buttons[index].getAttribute('data-title');
      const expectedFile = await buttons[index].getAttribute('data-file');

      await buttons[index].click();
      await page.waitForSelector('#document-viewer[open]', { timeout: 10000 });
      await page.waitForTimeout(600);

      const state = await page.evaluate(() => {
        const dialog = document.querySelector('#document-viewer');
        const frame = dialog.querySelector('iframe');
        const box = dialog.querySelector('.modal-frame').getBoundingClientRect();
        return {
          title: dialog.querySelector('#document-viewer-title').textContent.trim(),
          src: frame ? frame.getAttribute('src') : null,
          notice: dialog.querySelector('.viewer-notice')?.textContent.trim() || null,
          download: dialog.querySelector('[data-viewer-download]').getAttribute('href'),
          tab: dialog.querySelector('[data-viewer-tab]').getAttribute('href'),
          fits:
            box.left >= -1 &&
            box.top >= -1 &&
            box.right <= window.innerWidth + 1 &&
            box.bottom <= window.innerHeight + 1,
        };
      });

      assert.equal(state.title, expectedTitle, 'в окне не то название');
      assert.equal(state.download, expectedFile);
      assert.equal(state.tab, expectedFile);
      assert.ok(state.fits, `ширина ${width}: окно документа выходит за экран`);

      // Либо встроенный просмотр, либо честное пояснение с запасной кнопкой.
      if (state.src) {
        assert.ok(state.src.includes('#page=1'), 'документ открылся не с первой страницы');
        assert.ok(state.src.includes('view=Fit'), 'страница не вписана в окно');
        assert.ok(state.src.startsWith(expectedFile), 'показан не тот файл');
      } else {
        assert.ok(state.notice, 'нет ни просмотра, ни пояснения');
      }

      await page.keyboard.press('Escape');
      await page.waitForTimeout(250);
      assert.equal(await page.$eval('#document-viewer', (el) => el.open), false, 'Escape не закрыл окно');
    }

    await page.context().close();
  }
});

test('после закрытия фокус возвращается к тому же документу', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const buttons = await page.$$('[data-view-document]');
  const target = buttons[buttons.length - 1];
  const expected = await target.getAttribute('data-title');

  await target.click();
  await page.waitForSelector('#document-viewer[open]', { timeout: 10000 });
  await page.click('#document-viewer [data-close-modal]');
  await page.waitForTimeout(250);

  const focused = await page.evaluate(() => {
    const active = document.activeElement;
    return active ? active.getAttribute('data-title') : null;
  });
  assert.equal(focused, expected, 'фокус не вернулся на открытый документ');

  // Содержимое выгружается: тяжёлый PDF не должен висеть в памяти.
  assert.equal(await page.$eval('[data-viewer-body]', (el) => el.innerHTML), '');

  await page.context().close();
});

test('если файл не открылся, показано понятное сообщение', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  await page.route('**/documents/files/*.pdf', (route) =>
    route.fulfill({ status: 404, contentType: 'text/html', body: '<!doctype html><p>нет</p>' }),
  );

  await page.click('[data-view-document]');
  await page.waitForSelector('#document-viewer .viewer-notice', { timeout: 10000 });

  const notice = await page.$eval('.viewer-notice', (el) => el.textContent.trim());
  assert.match(notice, /не открылся/i);
  assert.match(notice, /404/);

  // Запасная кнопка на месте.
  const fallback = await page.$eval('.viewer-notice a', (el) => el.textContent.trim());
  assert.equal(fallback, 'Открыть в новой вкладке');

  await page.context().close();
});

test('скачивание документов продолжает работать', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });

  const links = await page.$$eval('.doc-download', (els) =>
    els.map((el) => ({ href: el.getAttribute('href'), download: el.hasAttribute('download') })),
  );

  assert.ok(links.length > 0);
  for (const link of links) {
    assert.ok(link.download, `у ссылки ${link.href} нет атрибута download`);
    const response = await page.request.get(site.baseUrl + link.href);
    assert.equal(response.status(), 200);
    const body = await response.body();
    assert.equal(body.subarray(0, 5).toString('latin1'), '%PDF-', 'скачался не PDF');
  }

  await page.context().close();
});

test('схема стоит между контактными сведениями и порядком обращения', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });

  const order = await page.$$eval('.contact-grid > *', (items) =>
    items.map((item) => {
      if (item.classList.contains('contact-map-card')) return 'схема';
      if (item.classList.contains('contact-guidance')) return 'порядок обращения';
      return (item.querySelector('h2')?.textContent || '').trim();
    }),
  );

  const map = order.indexOf('схема');
  const guidance = order.indexOf('порядок обращения');

  assert.ok(map > 0, 'схема не должна быть первым блоком страницы');
  assert.ok(guidance > map, 'схема должна стоять до порядка обращения');
  assert.equal(guidance, order.length - 1, 'порядок обращения должен идти последним');

  // Схема внутри общей ширины содержимого, а не отдельной секцией.
  const inGrid = await page.$$eval('.contact-grid .contact-map-card', (els) => els.length);
  assert.equal(inGrid, 1, 'схема должна лежать в сетке контактов');

  assert.deepEqual(page.consoleErrors, []);
  await page.context().close();
});

test('фотография Оки — фон самого футера, отдельного блока и подписи нет', async () => {
  for (const [width, height] of [
    [375, 812],
    [768, 1024],
    [1440, 900],
  ]) {
    const page = await site.page({ width, height });
    await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

    const check = await page.evaluate(() => {
      const footer = document.querySelector('footer.site-footer');
      const picture = footer ? footer.querySelector('.footer-bg') : null;
      const image = picture ? picture.querySelector('img') : null;
      const inner = footer ? footer.querySelector('.footer-inner') : null;
      const style = image ? getComputedStyle(image) : null;
      const footerBox = footer.getBoundingClientRect();
      const innerBox = inner.getBoundingClientRect();

      return {
        insideFooter: Boolean(image),
        separateBlock: Boolean(document.querySelector('.footer-photo')),
        caption: document.body.textContent.includes('Река Ока'),
        fit: style ? style.objectFit : null,
        currentSrc: image ? (image.currentSrc || '').split('/').pop() : null,
        alt: image ? image.getAttribute('alt') : null,
        // Текст футера лежит поверх фотографии, а не рядом с ней.
        overlaps:
          innerBox.top >= footerBox.top - 1 && innerBox.bottom <= footerBox.bottom + 1,
        footerHeight: Math.round(footerBox.height),
        viewportHeight: window.innerHeight,
      };
    });

    assert.ok(check.insideFooter, `${width}px: фотография не внутри футера`);
    assert.equal(check.separateBlock, false, `${width}px: остался отдельный блок с фотографией`);
    assert.equal(check.caption, false, `${width}px: на странице осталась надпись «Река Ока»`);
    assert.equal(check.fit, 'cover', `${width}px: фотография может исказиться`);
    assert.equal(check.alt, '', 'фон должен быть скрыт от чтения с экрана');
    assert.ok(check.overlaps, `${width}px: содержимое футера не лежит поверх фотографии`);
    assert.ok(
      check.footerHeight < check.viewportHeight * 1.4,
      `${width}px: футер слишком высокий (${check.footerHeight} px)`,
    );

    await page.context().close();
  }
});

test('футер подставляет свой кадр под каждый размер экрана', async () => {
  const expected = [
    [375, 'oka-footer-mobile'],
    [900, 'oka-footer-tablet'],
    [1440, 'oka-footer-desktop'],
  ];

  for (const [width, name] of expected) {
    const page = await site.page({ width, height: 900 });
    await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

    // Фон футера грузится лениво: пока до него не прокрутили, браузер
    // ещё не выбрал источник и currentSrc пуст.
    await page.$eval('.footer-bg', (el) => el.scrollIntoView());
    await page.waitForFunction(() => {
      const image = /** @type {HTMLImageElement | null} */ (
        document.querySelector('.footer-bg img')
      );
      return Boolean(image && image.complete && image.naturalWidth > 0 && image.currentSrc);
    }, { timeout: 10000 });

    const file = await page.$eval('.footer-bg img', (el) =>
      /** @type {HTMLImageElement} */ (el).currentSrc.split('/').pop(),
    );
    assert.ok(file.startsWith(name), `ширина ${width}: подставлен ${file}, ожидался ${name}`);

    await page.context().close();
  }
});

test('ссылки футера читаются поверх фотографии', async () => {
  const page = await site.page({ width: 375, height: 812 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });

  const links = await page.$$eval('.footer-nav a, .footer-legal a', (items) =>
    items.map((item) => {
      const rect = item.getBoundingClientRect();
      return {
        text: item.textContent.trim(),
        // Фотография не должна перехватывать нажатия по ссылкам.
        topmost: document.elementFromPoint(
          rect.left + rect.width / 2,
          rect.top + rect.height / 2,
        )?.closest('a') !== null,
        height: rect.height,
      };
    }),
  );

  assert.ok(links.length > 4);
  for (const link of links) {
    assert.ok(link.topmost, `по ссылке «${link.text}» нельзя нажать: её перекрывает фотография`);
    assert.ok(link.height >= 44, `ссылка «${link.text}» ниже 44 px`);
  }

  await page.context().close();
});

test('cookie-уведомление — компактная карточка, а не полоса во всю ширину', async () => {
  const cases = [
    { width: 1440, height: 900, maxWidth: 420, minOffset: 18 },
    { width: 768, height: 1024, maxWidth: 420, minOffset: 18 },
    { width: 375, height: 812, maxWidth: 360, minOffset: 10 },
  ];

  for (const { width, height, maxWidth, minOffset } of cases) {
    const page = await site.page({ width, height });
    await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
    await page.waitForSelector('.cookie-banner:not([hidden])', { timeout: 5000 });

    const box = await page.evaluate(() => {
      const rect = document.querySelector('.cookie-banner').getBoundingClientRect();
      return {
        width: rect.width,
        height: rect.height,
        right: window.innerWidth - rect.right,
        bottom: window.innerHeight - rect.bottom,
        share: (rect.width * rect.height) / (window.innerWidth * window.innerHeight),
      };
    });

    assert.ok(box.width <= maxWidth, `ширина ${width}: карточка ${Math.round(box.width)} px шире ${maxWidth}`);
    assert.ok(box.right >= minOffset, `ширина ${width}: нет отступа справа`);
    assert.ok(box.bottom >= minOffset, `ширина ${width}: нет отступа снизу`);
    assert.ok(box.height < height * 0.5, `ширина ${width}: карточка занимает больше половины высоты`);
    assert.ok(box.share < 0.3, `ширина ${width}: карточка закрывает ${Math.round(box.share * 100)}% экрана`);

    await page.context().close();
  }
});

test('страница под уведомлением не затемняется', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.cookie-banner:not([hidden])', { timeout: 5000 });

  // Слева от карточки должно оставаться обычное содержимое страницы.
  const topmost = await page.evaluate(() => {
    const element = document.elementFromPoint(200, window.innerHeight - 100);
    return element ? element.closest('.cookie-banner') !== null : false;
  });
  assert.equal(topmost, false, 'уведомление перекрывает содержимое страницы');

  await page.context().close();
});

test('выбор cookie запоминается и уведомление не возвращается', async () => {
  const page = await site.page({ width: 1440, height: 900 });
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.cookie-banner:not([hidden])', { timeout: 5000 });

  await page.click('.cookie-banner [data-cookie-choice]');
  await page.waitForTimeout(150);
  assert.equal(await page.$eval('.cookie-banner', (el) => el.hasAttribute('hidden')), true);

  for (const path of ['/documents', '/contacts', '/news', '/']) {
    await page.goto(site.baseUrl + path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(150);
    assert.equal(
      await page.$eval('.cookie-banner', (el) => el.hasAttribute('hidden')),
      true,
      `на странице ${path} уведомление появилось снова`,
    );
  }

  await page.context().close();
});
