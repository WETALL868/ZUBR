/**
 * Подготовка сканов документов к публикации.
 *
 * Сканы приходят тяжёлыми: страница А4 в цвете при 150 dpi занимает
 * около полумегабайта, а весь Устав — 13 МБ. На дачном интернете такой
 * файл качается минутами. Скрипт пересобирает PDF: переводит страницы
 * в оттенки серого и сжимает их, сохраняя читаемость текста.
 *
 * Исходники: assets/source/documents/
 * Результат: documents/files/
 *
 * Запуск: npm run documents:prepare
 */
import { PDFDocument } from 'pdf-lib';
import sharp from 'sharp';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync, readdirSync, writeFileSync, mkdirSync, existsSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const SOURCE_DIR = join(ROOT, 'assets', 'source', 'documents');
const OUT_DIR = join(ROOT, 'documents', 'files');

/**
 * @typedef {object} Job
 * @property {string} source  файл-исходник в assets/source/documents
 * @property {string} out     имя готового файла в documents/files
 * @property {number} [width] предельная ширина страницы в пикселях
 * @property {number} [quality] качество JPEG
 */

/** @type {Job[]} */
const JOBS = [
  {
    source: 'ustav-original.pdf',
    out: 'ustav.pdf',
    width: 1100,
    quality: 52,
  },
];

const mb = (path) => (statSync(path).size / 1024 / 1024).toFixed(2);

/** Проверяет, что нужные утилиты доступны. */
const requireTool = (name) => {
  try {
    execFileSync('which', [name], { stdio: 'pipe' });
  } catch {
    throw new Error(
      `Не найдена утилита ${name}. Установите poppler-utils: apt-get install poppler-utils`,
    );
  }
};

/**
 * Пересобирает PDF из сканов.
 * @param {Job} job
 */
const rebuild = async (job) => {
  const sourcePath = join(SOURCE_DIR, job.source);
  if (!existsSync(sourcePath)) {
    throw new Error(`Не найден исходник ${job.source} — положите его в assets/source/documents/`);
  }

  const workdir = mkdtempSync(join(tmpdir(), 'scan-'));
  try {
    // Страницы вынимаются как изображения: текстового слоя в сканах нет.
    execFileSync('pdfimages', ['-j', '-p', sourcePath, join(workdir, 'page')], { stdio: 'pipe' });

    const images = readdirSync(workdir)
      .filter((name) => /\.(jpg|jpeg|ppm|png)$/i.test(name))
      .sort();

    if (!images.length) throw new Error(`В ${job.source} не нашлось страниц`);

    const pdf = await PDFDocument.create();
    pdf.setTitle(job.out.replace(/\.pdf$/, ''));
    pdf.setProducer('СНП «Новая Искань»');

    for (const name of images) {
      // Оттенки серого: сканы чёрно-белого текста от цвета ничего не выигрывают,
      // а весят втрое больше.
      const jpeg = await sharp(join(workdir, name))
        .grayscale()
        .resize({ width: job.width, withoutEnlargement: true })
        .jpeg({ quality: job.quality, mozjpeg: true })
        .toBuffer();

      const embedded = await pdf.embedJpg(jpeg);
      const page = pdf.addPage([embedded.width, embedded.height]);
      page.drawImage(embedded, { x: 0, y: 0, width: embedded.width, height: embedded.height });
    }

    mkdirSync(OUT_DIR, { recursive: true });
    const outPath = join(OUT_DIR, job.out);
    writeFileSync(outPath, await pdf.save());

    return {
      Документ: job.out,
      Страниц: images.length,
      'Было, МБ': mb(sourcePath),
      'Стало, МБ': mb(outPath),
    };
  } finally {
    rmSync(workdir, { recursive: true, force: true });
  }
};

requireTool('pdfimages');

const report = [];
for (const job of JOBS) {
  report.push(await rebuild(job));
}
console.table(report);
