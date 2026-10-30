// Экспорт PNG каждого экрана: node src/shoot.mjs <dir с html> <dir для png> [префикс]
import { readdirSync, mkdirSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const [src, dst, prefix = ''] = process.argv.slice(2);
mkdirSync(dst, { recursive: true });
const browser = await chromium.launch();
for (const f of readdirSync(src).filter(f => f.endsWith('.html') && f.startsWith(prefix))) {
  const mobile = f.includes('-m-');
  const page = await browser.newPage({ viewport: { width: mobile ? 390 : f === 'index.html' ? 1600 : 1440, height: 900 }, deviceScaleFactor: mobile ? 2 : 1 });
  await page.goto('file://' + resolve(src, f));
  await page.evaluate(() => document.fonts.ready);
  const el = await page.$('.screen');
  const box = await el.boundingBox();
  const overflow = await page.evaluate(() => [...document.querySelectorAll('.screen *')].filter(n => { const r = n.getBoundingClientRect(); const s = document.querySelector('.screen').getBoundingClientRect(); return r.width > 0 && (r.right > s.right + 1); }).slice(0, 5).map(n => n.className || n.tagName));
  await el.screenshot({ path: join(dst, f.replace('.html', '.png')) });
  console.log(f, Math.round(box.width) + 'x' + Math.round(box.height), overflow.length ? 'OVERFLOW ' + overflow.join(',') : '');
  await page.close();
}
await browser.close();
