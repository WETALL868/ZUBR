// Сборка экранов этапа 1.
// node src/build.mjs            -> stage1/preview/*.html (ссылки на assets)
// node src/build.mjs --inline D -> D/*.html со встроенными шрифтами и изображениями (для html_to_figma)
import { writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { ctx, ROOT } from './common.mjs';
import * as A from './dirA.mjs';
import * as B from './dirB.mjs';
import * as C from './dirC.mjs';
import { overview } from './overview.mjs';

export const SCREENS = [];
for (const [k, mod] of [['A', A], ['B', B], ['C', C]]) {
  SCREENS.push(
    [`${k}-home`, mod.home], [`${k}-catalog`, mod.catalog], [`${k}-product`, mod.product],
    [`${k}-m-home`, mod.mHome], [`${k}-m-product`, mod.mProduct], [`${k}-m-product-scroll`, mod.mProductScroll],
  );
}

const i = process.argv.indexOf('--inline');
const only = process.argv.find(a => a.startsWith('--only='))?.slice(7);
let out = join(ROOT, 'stage1', 'preview');
if (i > -1) { ctx.mode = 'inline'; out = process.argv[i + 1]; }
mkdirSync(out, { recursive: true });
for (const [name, fn] of SCREENS) {
  if (only && !name.startsWith(only)) continue;
  writeFileSync(join(out, `${name}.html`), fn());
}
if (!only) writeFileSync(join(out, 'index.html'), overview());
console.log('built', out);
