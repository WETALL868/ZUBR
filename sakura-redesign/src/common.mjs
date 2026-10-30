// Общие данные и помощники для экранов этапа 1. Все цены, остатки и сроки демонстрационные.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, extname } from 'node:path';

export const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const MIME = { '.jpg': 'image/jpeg', '.png': 'image/png', '.svg': 'image/svg+xml', '.woff2': 'font/woff2' };

// mode 'path': ссылки на файлы относительно stage1/preview; mode 'inline': data URI для html_to_figma
export const ctx = { mode: 'path' };
export function asset(rel) {
  if (ctx.mode === 'inline') {
    const buf = readFileSync(join(ROOT, 'assets', rel));
    return `data:${MIME[extname(rel)]};base64,${buf.toString('base64')}`;
  }
  return `../../assets/${rel}`;
}

const FONT_FILES = {
  'Literata': ['literata', [400, 500, 600]],
  'Onest': ['onest', [400, 500, 600, 700]],
  'IBM Plex Sans': ['ibm-plex-sans', [400, 500, 600]],
  'IBM Plex Mono': ['ibm-plex-mono', [400, 500]],
  'Manrope': ['manrope', [400, 500, 600, 700, 800]],
};
export function fontFaces(families) {
  const out = [];
  for (const fam of families) {
    const [slug, weights] = FONT_FILES[fam];
    for (const w of weights) {
      for (const [sub, range] of [['cyrillic', 'U+0301,U+0400-045F,U+0490-0491,U+04B0-04B1,U+2116'], ['latin', 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD,U+20BD']]) {
        out.push(`@font-face{font-family:'${fam}';font-style:normal;font-weight:${w};font-display:block;src:url(${asset(`fonts/${slug}-${sub}-${w}-normal.woff2`)}) format('woff2');unicode-range:${range}}`);
      }
    }
  }
  return out.join('\n');
}

export const rub = n => new Intl.NumberFormat('ru-RU').format(n).replace(/\s/g, '\u00a0') + '\u00a0₽';
export const num = n => new Intl.NumberFormat('ru-RU').format(n).replace(/\s/g, '\u00a0');

export const STORE = {
  phone: '+7 (495) 477-56-25',
  hours: 'Пн–пт, 9:00–18:00',
  address: 'Москва, Ясеневая ул., 50, офис 114',
  addressShort: 'Ясеневая ул., 50',
  email: 'info@tdofficetorg.ru',
};

// Цены взяты из текущего магазина sakurarussia.ru 30.10.2026 и используются только как демо.
export const PRODUCTS = [
  { id: 'ce285a', img: 'ce285a', name: 'Картридж SAKURA CE285A / CB435A / 436A / 725', code: 'CE285A / CB435A / 436A / 725',
    sku: 'SA-SACE285A/CB435A/436A/725', mfr: 'SACE285A/CB435A/436A/725', oem: 'HP 85A, HP 35A, HP 36A, Canon 725',
    for: 'HP, Canon', fits: 'HP LaserJet P1102, P1005, P1505 · Canon MF3010', yield: 2000, color: 'Чёрный', price: 425, stock: 'in' },
  { id: 'cb435a', img: 'cb435a', name: 'Картридж SAKURA CB435A', code: 'CB435A', sku: 'SA-SACB435A', oem: 'HP 35A', for: 'HP', fits: 'HP LaserJet P1005, P1006', yield: 2000, color: 'Чёрный', price: 413, stock: 'in' },
  { id: 'cb436a', img: 'cb436a', name: 'Картридж SAKURA CB436A', code: 'CB436A', sku: 'SA-SACB436A', oem: 'HP 36A', for: 'HP', fits: 'HP LaserJet P1505, M1120, M1522', yield: 2000, color: 'Чёрный', price: 425, stock: 'in' },
  { id: 'w1106a', img: 'w1106a', name: 'Картридж SAKURA W1106A', code: 'W1106A', sku: 'SA-W1106A', oem: 'HP 106A', for: 'HP', fits: 'HP Laser 107a, 107w, 135a, 137fnw', yield: 1000, color: 'Чёрный', price: 580, stock: 'in' },
  { id: 'cb540a', img: 'cb540a', name: 'Картридж SAKURA CB540A / 716Bk', code: 'CB540A / 716Bk', sku: 'SA-CB540A/716BK', oem: 'HP 125A, Canon 716', for: 'HP, Canon', fits: 'HP Color LaserJet CP1215, CP1515 · Canon LBP5050', yield: 2300, color: 'Чёрный', price: 835, stock: 'in' },
  { id: 'cb541a', img: 'cb541a', name: 'Картридж SAKURA CB541A / 716C', code: 'CB541A / 716C', sku: 'SA-CB541A/716C', oem: 'HP 125A, Canon 716', for: 'HP, Canon', fits: 'HP Color LaserJet CP1215, CP1515 · Canon LBP5050', yield: 1500, color: 'Голубой', price: 745, stock: 'order' },
  { id: 'cb542a', img: 'cb542a', name: 'Картридж SAKURA CB542A / 716Y', code: 'CB542A / 716Y', sku: 'SA-CB542A/716Y', oem: 'HP 125A, Canon 716', for: 'HP, Canon', fits: 'HP Color LaserJet CP1215, CP1515 · Canon LBP5050', yield: 1500, color: 'Жёлтый', price: 745, stock: 'in' },
  { id: 'cb543a', img: 'cb543a', name: 'Картридж SAKURA CB543A', code: 'CB543A', sku: 'SA-CB543A', oem: 'HP 125A', for: 'HP', fits: 'HP Color LaserJet CM1312, CP1215, CP1515', yield: 1500, color: 'Пурпурный', price: 745, stock: 'out' },
  { id: 'c7115a', img: 'c7115a', name: 'Картридж SAKURA C7115A / Q2613A / 2624A', code: 'C7115A / Q2613A / 2624A', sku: 'SA-C7115A/Q2613A', oem: 'HP 15A, HP 13A, HP 24A', for: 'HP', fits: 'HP LaserJet 1000, 1200, 1300, 3300', yield: 2500, color: 'Чёрный', price: 652, stock: 'in' },
  { id: 'tk1170', img: 'tk1170', name: 'Картридж SAKURA TK1170', code: 'TK-1170', sku: 'SA-TK1170', oem: 'Kyocera TK-1170', for: 'Kyocera', fits: 'Kyocera ECOSYS M2040dn, M2540dn, M2640idw', yield: 7200, color: 'Чёрный', price: 440, stock: 'in' },
  { id: 'tk1200', img: 'tk1200', name: 'Картридж SAKURA TK1200', code: 'TK-1200', sku: 'SA-TK1200', oem: 'Kyocera TK-1200', for: 'Kyocera', fits: 'Kyocera ECOSYS M2235dn, M2735dn, M2835dw', yield: 3000, color: 'Чёрный', price: 413, stock: 'in' },
  { id: 'cb400a', img: 'cb400a', name: 'Картридж SAKURA CB400A', code: 'CB400A', sku: 'SA-CB400A', oem: 'HP 642A', for: 'HP', fits: 'HP Color LaserJet CP4005, CP4005n, CP4005dn', yield: 7500, color: 'Чёрный', price: 5693, stock: 'in' },
];
export const P = Object.fromEntries(PRODUCTS.map(p => [p.id, p]));

// Совместимость CE285A по названию товара в текущем магазине
export const CE285A_COMPAT = {
  'HP LaserJet': ['P1005', 'P1006', 'P1102', 'P1102W', 'P1500', 'P1505', 'P1505N', 'M1120', 'M1120N', 'M1522N', 'M1522F', '1522'],
  'Canon i-SENSYS': ['LBP6000', 'LBP6018', 'LBP6020', 'LBP6030', 'MF3010'],
};
export const CE285A_SPECS = [
  ['Тип', 'Картридж для лазерного принтера'],
  ['Цвет печати', 'Чёрный'],
  ['Ресурс', '2\u00a0000 страниц при 5% заполнении'],
  ['Модель оригинала', 'HP 85A, HP 35A, HP 36A, Canon 725'],
  ['Артикул производителя', 'SACE285A/CB435A/436A/725'],
  ['Производитель картриджа', 'SAKURA'],
  ['Вес', '0,806 кг'],
  ['Объём упаковки', '0,0038 м³'],
];

export const CATEGORIES = [
  { id: 'laser', name: 'Лазерные картриджи', note: 'Чёрные и цветные', img: 'generated/web/cat-laser.jpg' },
  { id: 'ink', name: 'Струйные картриджи', note: 'Для Canon, Epson, HP, Brother', img: 'generated/web/cat-ink.jpg' },
  { id: 'screens', name: 'Проекционные экраны', note: 'Настенные, моторизованные, на штативе', img: 'generated/web/cat-screens.jpg' },
  { id: 'photo', name: 'Фотобумага', note: 'Глянцевая и матовая, А4–А6', img: 'generated/web/cat-photo.jpg' },
  { id: 'roll', name: 'Широкоформатная бумага', note: 'Рулоны для плоттеров', img: 'generated/web/cat-roll.jpg' },
  { id: 'mounts', name: 'Крепления', note: 'Для проекторов и экранов', img: 'generated/web/cat-mounts.jpg' },
];

// Логотипы: размеры подобраны по равной площади, без растяжения
export const BRANDS = [
  { name: 'HP', file: 'brands/png/hp.png', r: 1.0 },
  { name: 'Canon', file: 'brands/png/canon.png', r: 4.8 },
  { name: 'Brother', file: 'brands/png/brother.png', r: 4.51 },
  { name: 'Kyocera', file: 'brands/png/kyocera.png', r: 4.67 },
  { name: 'Samsung', file: 'brands/png/samsung.png', r: 6.5 },
  { name: 'Xerox', file: 'brands/png/xerox.png', r: 4.57 },
  { name: 'Pantum', file: 'brands/png/pantum.png', r: 8.81 },
];
export function logoSize(b, area = 1500, maxH = 30) {
  let h = Math.sqrt(area / b.r);
  if (b.r < 1.5) h = Math.min(h, maxH);
  h = Math.round(h * 10) / 10;
  return { h, w: Math.round(h * b.r * 10) / 10 };
}
export const PRINTER_BRANDS = ['HP', 'Canon', 'Brother', 'Kyocera', 'Samsung', 'Xerox', 'Pantum', 'Ricoh', 'Lexmark', 'OKI', 'Konica Minolta', 'Epson', 'Panasonic'];

const ICON_PATHS = {
  search: '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
  heart: '<path d="M19.5 5.6a4.8 4.8 0 0 0-6.8 0L12 6.3l-.7-.7a4.8 4.8 0 1 0-6.8 6.8l7.5 7.4 7.5-7.4a4.8 4.8 0 0 0 0-6.8Z"/>',
  compare: '<path d="M6 19V10M12 19V5M18 19v-6"/>',
  cart: '<path d="M3 4h2.2l2 11h10.6l1.9-8H6.4"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
  user: '<circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
  menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
  down: '<path d="m6.5 9.5 5.5 5.5 5.5-5.5"/>',
  right: '<path d="m9.5 6.5 5.5 5.5-5.5 5.5"/>',
  left: '<path d="m14.5 6.5-5.5 5.5 5.5 5.5"/>',
  check: '<path d="m5 12.5 4.2 4.2L19 7"/>',
  truck: '<path d="M2.5 6h11v10h-11zM13.5 9.5h4l3 3.5V16h-7"/><circle cx="7" cy="17.5" r="1.7"/><circle cx="17" cy="17.5" r="1.7"/>',
  pin: '<path d="M19 10c0 5.2-7 11-7 11s-7-5.8-7-11a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
  shield: '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
  file: '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 16h4"/>',
  phone: '<path d="M5 4h3.5l1.7 4.3-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5A16 16 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4Z"/>',
  minus: '<path d="M6 12h12"/>',
  plus: '<path d="M12 6v12M6 12h12"/>',
  printer: '<path d="M7 9V3.5h10V9"/><rect x="3.5" y="9" width="17" height="8" rx="1.5"/><path d="M7 14h10v6.5H7z"/>',
  filter: '<path d="M4 6h16M7 12h10M10 18h4"/>',
  grid: '<rect x="4" y="4" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1"/>',
  list: '<path d="M9 6.5h11M9 12h11M9 17.5h11"/><circle cx="5" cy="6.5" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="17.5" r="1"/>',
  close: '<path d="m6.5 6.5 11 11M17.5 6.5l-11 11"/>',
  copy: '<rect x="8.5" y="8.5" width="11" height="11" rx="1.5"/><path d="M15.5 8.5V5.5a1 1 0 0 0-1-1h-9a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h3"/>',
  info: '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.2"/>',
  box: '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
  arrow: '<path d="M5 12h14M13.5 6.5 19 12l-5.5 5.5"/>',
  home: '<path d="M4 10.5 12 4l8 6.5V20h-5.5v-5.5h-5V20H4z"/>',
  clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
  card: '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>',
  return: '<path d="M9 7 4.5 11.5 9 16"/><path d="M4.5 11.5H15a4.5 4.5 0 0 1 0 9h-2"/>',
  chat: '<path d="M4.5 5.5h15v10h-8l-4.5 3.5v-3.5H4.5z"/>',
  share: '<circle cx="17.5" cy="6" r="2.2"/><circle cx="6.5" cy="12" r="2.2"/><circle cx="17.5" cy="18" r="2.2"/><path d="m8.5 11 7-4M8.5 13l7 4"/>',
  layers: '<path d="m12 4 8.5 4.5L12 13 3.5 8.5 12 4Z"/><path d="m3.5 12.5 8.5 4.5 8.5-4.5M3.5 16.5 12 21l8.5-4.5"/>',
  sliders: '<path d="M5 6h9M18 6h1M5 12h3M12 12h7M5 18h11M20 18h-1"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
};
export function icon(name, size = 20, stroke = 1.6, cls = '') {
  return `<svg class="ico ${cls}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${stroke}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICON_PATHS[name]}</svg>`;
}

export const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

export const stockLabel = { in: 'В наличии', order: 'Под заказ', out: 'Нет в наличии' };
export const colorDot = { 'Чёрный': '#1d1d1f', 'Голубой': '#1aa3d9', 'Жёлтый': '#f2c200', 'Пурпурный': '#d4237a' };

export function page({ title, families, css, body, width }) {
  return `<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=${width}"><title>${esc(title)}</title><style>
${fontFaces(families)}
*{box-sizing:border-box}html,body{margin:0;padding:0}body{background:#e9e7e4;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
img{display:block;max-width:100%}a{color:inherit;text-decoration:none}button{font:inherit;color:inherit}
.screen{position:relative;overflow:hidden;margin:0 auto}
${css}
</style></head><body>${body}</body></html>`;
}
