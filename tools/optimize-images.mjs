/**
 * Подготовка изображений сайта.
 *
 * Исходный экспорт отдавал один PNG на 2,7 МБ как фон первого экрана
 * и как фон шапки каждой внутренней страницы. На дачном интернете это
 * заметная задержка, поэтому из каждого исходника собираются варианты
 * в AVIF и WebP нескольких размеров, а PNG остаётся запасным.
 *
 * Запуск: npm run images
 * Исходники лежат в assets/source/, результат — в корне сайта.
 */
import sharp from 'sharp';
import { mkdirSync, existsSync, statSync, readdirSync, unlinkSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const SOURCE_DIR = join(ROOT, 'assets', 'source');
const OUT_DIR = join(ROOT, 'assets', 'img');

/**
 * @typedef {object} Job
 * @property {string} source     файл-исходник
 * @property {string} name       базовое имя результата
 * @property {number[]} widths   ширины вариантов
 * @property {number} quality    качество AVIF/WebP
 * @property {boolean} [jpeg]    дополнительно собрать JPEG (для og:image)
 * @property {boolean} [png]     дополнительно собрать PNG (запасной формат)
 */

/** @type {Job[]} */
const JOBS = [
  {
    // Фон первого экрана и шапок внутренних страниц.
    // Поверх него лежит плотный тёмно-зелёный градиент, поэтому
    // умеренное сжатие визуально не заметно.
    source: 'hero-road-production.png',
    name: 'hero-road',
    widths: [640, 960, 1280, 1920],
    quality: 62,
  },
  {
    // Логотип показывается размером 40–58 px, исходник был 1024×1024.
    source: 'novaya-iskan-logo.png',
    name: 'logo',
    widths: [128, 256],
    quality: 82,
    png: true,
  },
  {
    // Картинка для ссылок в мессенджерах: нужен один растровый файл,
    // который умеют читать все площадки предпросмотра.
    source: 'og.png',
    name: 'og',
    widths: [1200],
    quality: 78,
    jpeg: true,
  },
];

const kb = (path) => Math.round(statSync(path).size / 1024);

const run = async () => {
  mkdirSync(OUT_DIR, { recursive: true });

  // Прошлые результаты удаляются, чтобы не копились файлы от старых настроек.
  for (const file of existsSync(OUT_DIR) ? readdirSync(OUT_DIR) : []) {
    unlinkSync(join(OUT_DIR, file));
  }

  const report = [];

  for (const job of JOBS) {
    const sourcePath = join(SOURCE_DIR, job.source);
    if (!existsSync(sourcePath)) {
      throw new Error(`Не найден исходник ${job.source} — положите его в assets/source/`);
    }

    const meta = await sharp(sourcePath).metadata();
    const originalKb = kb(sourcePath);
    let producedKb = 0;

    for (const width of job.widths) {
      if (width > meta.width) continue;
      const base = sharp(sourcePath).resize({ width, withoutEnlargement: true });

      const avifPath = join(OUT_DIR, `${job.name}-${width}.avif`);
      await base.clone().avif({ quality: job.quality, effort: 6 }).toFile(avifPath);

      const webpPath = join(OUT_DIR, `${job.name}-${width}.webp`);
      await base.clone().webp({ quality: job.quality, effort: 6 }).toFile(webpPath);

      producedKb += kb(avifPath) + kb(webpPath);

      if (job.png) {
        const pngPath = join(OUT_DIR, `${job.name}-${width}.png`);
        await base.clone().png({ compressionLevel: 9, palette: true }).toFile(pngPath);
        producedKb += kb(pngPath);
      }

      if (job.jpeg) {
        const jpegPath = join(OUT_DIR, `${job.name}-${width}.jpg`);
        await base.clone().jpeg({ quality: job.quality, mozjpeg: true }).toFile(jpegPath);
        producedKb += kb(jpegPath);
      }
    }

    report.push({
      Исходник: job.source,
      'Размер, КБ': originalKb,
      'Варианты, КБ': producedKb,
      Ширины: job.widths.filter((w) => w <= meta.width).join(', '),
    });
  }

  console.table(report);
};

await run();
