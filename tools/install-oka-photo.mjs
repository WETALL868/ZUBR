/**
 * Установка фотографии реки Оки в футер сайта.
 *
 * Скрипт берёт готовый файл фотографии, готовит из него облегчённые
 * варианты, вставляет блок с фотографией в футер всех страниц и
 * записывает источник и лицензию в docs/IMAGES.md.
 *
 * Использование:
 *   node tools/install-oka-photo.mjs <файл-или-ссылка> \
 *     --source "https://commons.wikimedia.org/wiki/File:..." \
 *     --license "CC0 1.0" \
 *     --author "Имя автора" \
 *     [--alt "Описание для незрячих"] \
 *     [--caption "Подпись под фотографией"]
 *
 * Принимаются только лицензии Public Domain и CC0 — на другие
 * скрипт отвечает отказом, чтобы на сайт не попал спорный файл.
 */
import sharp from 'sharp';
import { mkdirSync, writeFileSync, readFileSync, existsSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { glob } from 'node:fs/promises';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const OUT_DIR = join(ROOT, 'assets', 'img');
const DOCS = join(ROOT, 'docs', 'IMAGES.md');

/** Лицензии, при которых фотографию можно разместить на сайте. */
const ALLOWED_LICENCES = [
  /^cc0\b/i,
  /^public\s*domain$/i,
  /^pd(-|\b)/i,
  /общественное\s+достояние/i,
];

const SIZES = [
  { width: 800, media: '(max-width: 700px)' },
  { width: 1600, media: null },
];

const DEFAULT_ALT =
  'Река Ока на повороте русла: широкая излучина, песчаный берег и лес на дальнем берегу';
const DEFAULT_CAPTION = 'Река Ока';

/**
 * Разбор аргументов командной строки.
 * Понимает и пары «--ключ значение», и одиночные флаги вроде
 * «--owner-supplied», у которых значения нет.
 */
const parseArgs = (argv) => {
  const [input, ...rest] = argv;
  const options = {};

  for (let i = 0; i < rest.length; i += 1) {
    const argument = rest[i];
    if (!argument.startsWith('--')) throw new Error(`Непонятный аргумент: ${argument}`);

    const key = argument.slice(2);
    const next = rest[i + 1];

    if (next === undefined || next.startsWith('--')) {
      options[key] = true;
    } else {
      options[key] = next;
      i += 1;
    }
  }

  return { input, options };
};

/** Загружает исходник: локальный файл или ссылка. */
const readSource = async (input) => {
  if (/^https?:\/\//.test(input)) {
    const response = await fetch(input);
    if (!response.ok) throw new Error(`Не удалось скачать файл: HTTP ${response.status}`);
    return Buffer.from(await response.arrayBuffer());
  }
  if (!existsSync(input)) throw new Error(`Файл не найден: ${input}`);
  return readFileSync(input);
};

const buildMarkup = (alt, caption) => {
  const sources = SIZES.flatMap(({ width, media }) =>
    ['avif', 'webp'].map(
      (format) =>
        `<source type="image/${format}"${media ? ` media="${media}"` : ''} srcset="/assets/img/oka-${width}.${format}">`,
    ),
  ).join('');

  return (
    '<section class="footer-photo" data-photo="ready" aria-label="Река Ока">' +
    '<picture>' +
    sources +
    `<img src="/assets/img/oka-1600.jpg" alt="${alt}" width="1600" height="600" loading="lazy" decoding="async">` +
    '</picture>' +
    `<p class="footer-photo-caption">${caption}</p>` +
    '</section>'
  );
};

const main = async () => {
  const { input, options } = parseArgs(process.argv.slice(2));

  if (!input) {
    throw new Error(
      'Укажите файл фотографии: node tools/install-oka-photo.mjs <файл> --source <ссылка> --license <лицензия> --author <автор>',
    );
  }
  const ownerSupplied = Object.hasOwn(options, 'owner-supplied');
  let licence;
  let source;
  let author;

  if (ownerSupplied) {
    licence = typeof options.license === 'string'
      ? options.license.trim()
      : 'не указана — подтверждает владелец сайта';
    source = typeof options.source === 'string'
      ? options.source
      : 'файл передан владельцем сайта, страница источника не указана';
    author = typeof options.author === 'string' ? options.author : 'не указан';
  } else {
    for (const required of ['source', 'license', 'author']) {
      if (!options[required]) throw new Error(`Не указан обязательный параметр --${required}`);
    }

    licence = options.license.trim();
    source = options.source;
    author = options.author;

    if (!ALLOWED_LICENCES.some((pattern) => pattern.test(licence))) {
      throw new Error(
        `Лицензия «${licence}» не подходит. Разрешены только Public Domain и CC0 — ` +
          'фотографию с другой лицензией размещать нельзя. Если снимок ваш собственный, ' +
          'используйте --owner-supplied.',
      );
    }
  }

  const buffer = await readSource(input);
  const image = sharp(buffer);
  const meta = await image.metadata();

  if (!meta.width || !meta.height) throw new Error('Не удалось прочитать изображение');
  if (meta.width / meta.height < 1.5) {
    throw new Error(
      `Нужна широкоформатная фотография: у этой соотношение ${(meta.width / meta.height).toFixed(2)}, ` +
        'а для полосы в футере требуется не меньше 1,5 (например, 1600×900).',
    );
  }
  if (meta.width < 1200) {
    throw new Error(`Ширина исходника ${meta.width} px — для полосы в футере нужно не меньше 1200 px.`);
  }

  mkdirSync(OUT_DIR, { recursive: true });

  const produced = [];
  for (const { width } of SIZES) {
    // Полоса в футере показывает горизонтальный фрагмент, поэтому
    // варианты сразу режутся по пропорции 8:3 — файл меньше, а кадр тот же.
    const resized = sharp(buffer).resize({
      width,
      height: Math.round((width * 3) / 8),
      fit: 'cover',
      position: 'attention',
    });

    for (const format of ['avif', 'webp']) {
      const path = join(OUT_DIR, `oka-${width}.${format}`);
      await resized.clone()[format]({ quality: 64, effort: 6 }).toFile(path);
      produced.push(path);
    }

    if (width === 1600) {
      const path = join(OUT_DIR, 'oka-1600.jpg');
      await resized.clone().jpeg({ quality: 76, mozjpeg: true }).toFile(path);
      produced.push(path);
    }
  }

  const alt = (options.alt || DEFAULT_ALT).replace(/"/g, '&quot;');
  const caption = options.caption || DEFAULT_CAPTION;
  const markup = buildMarkup(alt, caption);

  let patched = 0;
  for await (const path of glob(join(ROOT, '**/*.html'))) {
    if (path.includes('node_modules')) continue;
    let html = readFileSync(path, 'utf8');
    if (!html.includes('<footer class="site-footer">')) continue;

    html = html.replace(/<section class="footer-photo"[\s\S]*?<\/section>/, '');
    html = html.replace('<footer class="site-footer">', `${markup}<footer class="site-footer">`);
    writeFileSync(path, html);
    patched += 1;
  }

  mkdirSync(dirname(DOCS), { recursive: true });
  const entry =
    `\n## Фотография реки Оки в футере\n\n` +
    `- Файлы: \`assets/img/oka-800.{avif,webp}\`, \`assets/img/oka-1600.{avif,webp,jpg}\`\n` +
    `- Страница источника: ${source}\n` +
    `- Автор: ${author}\n` +
    `- Лицензия: ${licence}\n` +
    `- Исходный размер: ${meta.width}×${meta.height}\n` +
    `- Дата установки: ${new Date().toISOString().slice(0, 10)}\n` +
    (ownerSupplied
      ? '\n> Файл передан владельцем сайта. Права на публикацию подтверждает\n' +
        '> владелец: страница первоисточника и название лицензии не проверялись\n' +
        '> инструментом. Если снимок взят из внешнего источника, впишите сюда\n' +
        '> прямую ссылку и название лицензии.\n'
      : '');
  writeFileSync(DOCS, (existsSync(DOCS) ? readFileSync(DOCS, 'utf8') : '# Изображения проекта\n') + entry);

  console.log('Фотография установлена.');
  console.table(
    produced.map((path) => ({
      Файл: path.replace(`${ROOT}/`, ''),
      'Размер, КБ': Math.round(statSync(path).size / 1024),
    })),
  );
  console.log(`Блок с фотографией добавлен на страниц: ${patched}`);
  console.log(`Источник и лицензия записаны в ${DOCS.replace(`${ROOT}/`, '')}`);

  if (ownerSupplied) {
    console.warn(
      '\nВнимание: лицензия не проверялась — файл принят как переданный владельцем.\n' +
        'Если снимок взят из внешнего источника, впишите ссылку и лицензию\n' +
        'в docs/IMAGES.md либо переустановите фотографию с параметрами\n' +
        '--source, --license и --author.',
    );
  }
};

main().catch((error) => {
  console.error(`Ошибка: ${error.message}`);
  process.exit(1);
});
