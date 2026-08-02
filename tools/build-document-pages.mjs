/**
 * Постраничные изображения документов для просмотра на телефоне.
 *
 * Зачем. Кнопка «Посмотреть» открывала PDF во встроенной рамке.
 * На телефоне это не работает: Chrome для Android и большинство
 * мобильных браузеров не умеют показывать PDF внутри страницы —
 * файл просто скачивался. Получалось, что на телефоне доступно
 * только «Скачать».
 *
 * Решение. Каждая страница документа заранее переводится в картинку.
 * Телефон открывает те же страницы, что и компьютер, листает их
 * прокруткой и ничего не скачивает. Картинки грузятся по мере
 * прокрутки (loading="lazy"), поэтому открытие стоит одной страницы,
 * а не всего документа.
 *
 * Исходники: documents/files/*.pdf (уже опубликованные файлы)
 * Результат: documents/pages/<имя>/NN.webp и NN.jpg + pages.json
 *
 * Запуск: npm run documents:pages
 */
import sharp from 'sharp';
import { execFileSync } from 'node:child_process';
import {
  existsSync,
  mkdirSync,
  mkdtempSync,
  readdirSync,
  readFileSync,
  rmSync,
  statSync,
  writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const CATALOG = join(ROOT, 'docs', 'documents-catalog.json');
const FILES_DIR = join(ROOT, 'documents', 'files');
const PAGES_DIR = join(ROOT, 'documents', 'pages');

/** Ширина картинки страницы. Текст скана читается уже при 1000 px. */
const WIDTH = 1000;

/** Разрешение отрисовки PDF. 110 dpi даёт запас перед уменьшением. */
const RENDER_DPI = 110;

/**
 * Настройки для отдельных документов.
 *
 * Устав — чёрно-белый машинописный текст, в оттенках серого он весит
 * вдвое меньше и читается так же. Свидетельство о регистрации остаётся
 * цветным: на бланке гербовая сетка, синяя печать и тиснёный герб.
 *
 * @type {Record<string, { grayscale?: boolean, quality?: number }>}
 */
const SETTINGS = {
  'ustav.pdf': { grayscale: true, quality: 62 },
  'egrul.pdf': { grayscale: false, quality: 74 },
};

/** Проверяет, что нужная утилита есть в системе. */
const requireTool = (name) => {
  try {
    execFileSync('which', [name], { stdio: 'pipe' });
  } catch {
    throw new Error(
      `Не найдена утилита ${name}. Установите poppler-utils: apt-get install poppler-utils`,
    );
  }
};

const kb = (bytes) => `${Math.round(bytes / 1024)} КБ`;

/**
 * Раскладывает один PDF на картинки страниц.
 * @param {string} file имя файла в documents/files
 * @returns {Promise<{ slug: string, pages: number, bytes: number }>}
 */
const renderDocument = async (file) => {
  const source = join(FILES_DIR, file);
  const slug = file.replace(/\.pdf$/i, '');
  const target = join(PAGES_DIR, slug);
  const settings = SETTINGS[file] ?? {};

  const workdir = mkdtempSync(join(tmpdir(), 'pages-'));
  try {
    execFileSync('pdftoppm', ['-png', '-r', String(RENDER_DPI), source, join(workdir, 'page')], {
      stdio: 'pipe',
    });

    const rendered = readdirSync(workdir)
      .filter((name) => name.endsWith('.png'))
      .sort();

    if (!rendered.length) throw new Error(`Из ${file} не удалось получить ни одной страницы`);

    // Папка пересобирается целиком: иначе от прежней версии документа
    // остались бы лишние страницы в конце.
    rmSync(target, { recursive: true, force: true });
    mkdirSync(target, { recursive: true });

    let bytes = 0;
    for (const [index, name] of rendered.entries()) {
      const number = String(index + 1).padStart(2, '0');
      let image = sharp(join(workdir, name)).resize({ width: WIDTH, withoutEnlargement: true });
      if (settings.grayscale !== false) image = image.grayscale();

      /*
       * Только WebP. Второй набор в JPEG удвоил бы вес всего каталога
       * ради браузеров старше 2020 года; для них в окне просмотра
       * остаются «Скачать» и «Открыть в новой вкладке», а скрипт
       * покажет это сообщение, если картинка не загрузилась.
       */
      const quality = settings.quality ?? 66;
      await image.webp({ quality }).toFile(join(target, `${number}.webp`));

      bytes += statSync(join(target, `${number}.webp`)).size;
    }

    const first = await sharp(join(target, '01.webp')).metadata();

    writeFileSync(
      join(target, 'pages.json'),
      `${JSON.stringify(
        {
          _комментарий: 'Файл собирается командой npm run documents:pages. Править вручную не нужно.',
          source: `/documents/files/${file}`,
          pages: rendered.length,
          width: first.width,
          height: first.height,
        },
        null,
        2,
      )}\n`,
    );

    return { slug, pages: rendered.length, bytes };
  } finally {
    rmSync(workdir, { recursive: true, force: true });
  }
};

const main = async () => {
  requireTool('pdftoppm');

  const catalog = JSON.parse(readFileSync(CATALOG, 'utf8'));
  mkdirSync(PAGES_DIR, { recursive: true });

  const rows = [];
  for (const doc of catalog.documents) {
    if (!existsSync(join(FILES_DIR, doc.file))) {
      console.warn(`  пропущен (файла нет): ${doc.file}`);
      continue;
    }
    const result = await renderDocument(doc.file);
    rows.push({
      Документ: doc.file,
      Страниц: result.pages,
      'Вес всех страниц': kb(result.bytes),
      'Одна страница в среднем': kb(result.bytes / result.pages),
    });
  }

  console.table(rows);
  console.log(`Страницы сохранены в documents/pages/.`);
};

await main();
