/**
 * Итоговые скриншоты на ширинах 375, 768 и 1440.
 *
 * Для каждой ширины снимаются: главная страница, документы, форма
 * обращения, окно успешной отправки и футер.
 *
 * Запуск: npm run screenshots
 * Результат: screenshots/<ширина>-<название>.png
 */
import { mkdirSync, rmSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { startSite } from '../tests/helpers/site.mjs';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const OUT = join(ROOT, 'screenshots');

const WIDTHS = [
  { width: 375, height: 812, label: 'телефон' },
  { width: 768, height: 1024, label: 'планшет' },
  { width: 1440, height: 900, label: 'компьютер' },
];

const site = await startSite();

rmSync(OUT, { recursive: true, force: true });
mkdirSync(OUT, { recursive: true });

const shot = async (page, name, options = {}) => {
  const path = join(OUT, `${name}.png`);
  await page.screenshot({ path, ...options });
  console.log(`  ${name}.png`);
};

/** Заполняет форму обращения корректными данными. */
const fillAppeal = async (page) => {
  await page.fill('input[name=fullName]', 'Иван Иванов');
  await page.fill('input[name=plotNumber]', '12');
  await page.fill('input[name=phone]', '+7 900 000-00-00');
  await page.fill('input[name=email]', 'ivanov@example.ru');
  await page.selectOption('select[name=topic]', 'Дороги и проезд');
  await page.fill(
    'textarea[name=message]',
    'Прошу рассмотреть вопрос об освещении на въезде в посёлок: в тёмное время суток проезд плохо просматривается.',
  );
  await page.check('input[name=personalConsent]');
  await page.check('input[name=agreementConsent]');
};

/** Убирает cookie-уведомление, чтобы оно не закрывало снимаемый блок. */
const dismissCookies = async (page) => {
  await page.evaluate(() => {
    try {
      window.localStorage.setItem('novaya-iskan-cookie-choice', 'accepted');
    } catch {
      // приватный режим — уведомление просто останется
    }
    document.querySelector('.cookie-banner')?.setAttribute('hidden', '');
  });
};

for (const { width, height, label } of WIDTHS) {
  console.log(`\n${label} — ${width} px`);
  const page = await site.page({ width, height });

  // 1. Главная страница
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
  await shot(page, `${width}-1-главная`);

  // 2. Главная с cookie-уведомлением, полная высота
  await shot(page, `${width}-2-главная-целиком`, { fullPage: true });

  await dismissCookies(page);

  // 3. Документы: полный список
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });
  await dismissCookies(page);
  await shot(page, `${width}-3-документы`, { fullPage: true });

  // 4. Документы: выбранная категория (берётся первая из имеющихся —
  //    состав каталога со временем меняется)
  const category = await page.$eval(
    '.filter-row button[data-category]:not([data-category="all"])',
    (button) => ({ value: button.dataset.category, label: button.textContent.trim().toLowerCase() }),
  );
  await page.click(`.filter-row button[data-category="${category.value}"]`);
  await page.waitForTimeout(150);
  await shot(page, `${width}-4-документы-категория-${category.label}`, { fullPage: true });

  // 5. Форма обращения
  await page.goto(`${site.baseUrl}/appeal`, { waitUntil: 'networkidle' });
  await dismissCookies(page);
  await shot(page, `${width}-5-форма-обращения`, { fullPage: true });

  // 6. Окно успешной отправки
  await fillAppeal(page);
  await page.click('.submit-button');
  await page.waitForSelector('#appeal-success[open]', { timeout: 10000 });
  await page.waitForTimeout(200);
  await shot(page, `${width}-6-окно-отправлено`);

  await page.keyboard.press('Escape');
  await page.waitForTimeout(150);

  // 7. Контакты со схемой проезда
  await page.goto(`${site.baseUrl}/contacts`, { waitUntil: 'networkidle' });
  await dismissCookies(page);
  const mapBlock = await page.$('.map-section');
  await mapBlock.scrollIntoViewIfNeeded();
  await page.waitForTimeout(400);
  await mapBlock.screenshot({ path: join(OUT, `${width}-7-контакты-схема.png`) });
  console.log(`  ${width}-7-контакты-схема.png`);

  // 8. Увеличенная схема
  await page.click('[data-open-map]');
  await page.waitForTimeout(500);
  await shot(page, `${width}-8-схема-увеличенная`);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(200);

  // 9. Просмотр документа
  await page.goto(`${site.baseUrl}/documents`, { waitUntil: 'networkidle' });
  await dismissCookies(page);
  await page.click('[data-view-document]');
  await page.waitForSelector('#document-viewer[open]', { timeout: 10000 });
  // Встроенному просмотрщику нужно время на отрисовку первой страницы.
  await page.waitForTimeout(2500);
  await shot(page, `${width}-9-просмотр-документа`);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(200);

  // 10. Футер с фотографией Оки
  await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
  await dismissCookies(page);
  const footer = await page.$('.footer-photo, .site-footer');
  await footer.scrollIntoViewIfNeeded();
  await page.waitForTimeout(400);
  await page.evaluate(() => {
    const photo = document.querySelector('.footer-photo');
    const foot = document.querySelector('.site-footer');
    if (photo && foot) {
      const wrap = document.createElement('div');
      wrap.id = 'footer-shot';
      photo.parentNode.insertBefore(wrap, photo);
      wrap.append(photo, foot);
    }
  });
  await page.waitForTimeout(200);
  const shotArea = (await page.$('#footer-shot')) || footer;
  await shotArea.screenshot({ path: join(OUT, `${width}-10-футер-ока.png`) });
  console.log(`  ${width}-10-футер-ока.png`);

  // 11. Мобильное меню (только на телефоне и планшете)
  if (width <= 768) {
    await page.goto(`${site.baseUrl}/`, { waitUntil: 'networkidle' });
    await dismissCookies(page);
    await page.click('.mobile-menu summary');
    await page.waitForTimeout(300);
    await shot(page, `${width}-11-мобильное-меню`);
  }

  await page.context().close();
}

await site.stop();
console.log(`\nСкриншоты сохранены в ${OUT.replace(`${ROOT}/`, '')}/`);
