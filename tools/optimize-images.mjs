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
 * @property {boolean} [flat]    имя файла без суффикса ширины
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
  // Фон футера: три готовых кадра от владельца сайта. Здесь они только
  // сжимаются и переводятся в лёгкие форматы — кадрирование автора
  // сохраняется без изменений.
  {
    source: 'oka-footer-desktop.png',
    name: 'oka-footer-desktop',
    widths: [1920],
    quality: 64,
    jpeg: true,
    flat: true,
  },
  {
    source: 'oka-footer-tablet.png',
    name: 'oka-footer-tablet',
    widths: [1200],
    quality: 64,
    jpeg: true,
    flat: true,
  },
  {
    source: 'oka-footer-mobile.png',
    name: 'oka-footer-mobile',
    widths: [860],
    quality: 64,
    jpeg: true,
    flat: true,
  },
  {
    // Схема проезда на странице «Контакты». Качество выше обычного:
    // на схеме мелкие номера участков и названия улиц, при сильном
    // сжатии они замыливаются. Полный размер нужен для увеличения
    // по нажатию, поэтому исходная ширина сохраняется как вариант.
    source: 'contact-map.png',
    name: 'contact-map',
    widths: [900, 1400, 1980],
    quality: 74,
    // JPEG нужен как запасной формат и как файл для кнопки
    // «Открыть в новой вкладке»: исходный PNG лежит в assets/source,
    // а эта папка закрыта от посетителей.
    jpeg: true,
  },
];

const kb = (path) => Math.round(statSync(path).size / 1024);

const run = async () => {
  mkdirSync(OUT_DIR, { recursive: true });

  // Удаляются только прежние результаты этого скрипта: в той же папке
  // лежат файлы, собранные другими инструментами (например фотография
  // Оки из tools/install-oka-photo.mjs), и стирать их нельзя.
  const ownPrefixes = JOBS.map((job) => `${job.name}-`);
  for (const file of existsSync(OUT_DIR) ? readdirSync(OUT_DIR) : []) {
    if (ownPrefixes.some((prefix) => file.startsWith(prefix))) {
      unlinkSync(join(OUT_DIR, file));
    }
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

      // flat: имя без ширины — файл один и называется по назначению.
      const suffix = job.flat ? '' : `-${width}`;

      const avifPath = join(OUT_DIR, `${job.name}${suffix}.avif`);
      await base.clone().avif({ quality: job.quality, effort: 6 }).toFile(avifPath);

      const webpPath = join(OUT_DIR, `${job.name}${suffix}.webp`);
      await base.clone().webp({ quality: job.quality, effort: 6 }).toFile(webpPath);

      producedKb += kb(avifPath) + kb(webpPath);

      if (job.png) {
        const pngPath = join(OUT_DIR, `${job.name}${suffix}.png`);
        await base.clone().png({ compressionLevel: 9, palette: true }).toFile(pngPath);
        producedKb += kb(pngPath);
      }

      if (job.jpeg) {
        const jpegPath = join(OUT_DIR, `${job.name}${suffix}.jpg`);
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
