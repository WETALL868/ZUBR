// Сборка документов для html_to_figma: по одному на направление, все экраны направления внутри,
// шрифты и изображения встроены. node src/figma-docs.mjs <выходная папка>
import { writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { ctx } from './common.mjs';
import * as A from './dirA.mjs';
import * as B from './dirB.mjs';
import * as C from './dirC.mjs';
import { overview } from './overview.mjs';

ctx.mode = 'inline';
const out = process.argv[2];
mkdirSync(out, { recursive: true });
for (const [k, mod] of [['A', A], ['B', B], ['C', C]]) {
  const docs = [mod.home(), mod.catalog(), mod.product(), mod.mHome(), mod.mProduct(), mod.mProductScroll()];
  const head = docs[0].slice(0, docs[0].indexOf('<body>'));
  const bodies = docs.map(d => d.slice(d.indexOf('<body>') + 6, d.lastIndexOf('</body>')));
  const html = `${head.replace('</style>', '.screen{margin:0 0 80px}body{width:1600px;padding:40px}</style>')}<body>${bodies.join('\n')}</body></html>`;
  writeFileSync(join(out, `${k}.html`), html);
  console.log(k, (html.length / 1e6).toFixed(1) + ' MB');
}

writeFileSync(join(out, 'O.html'), overview());
