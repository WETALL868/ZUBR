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
 * @property {boolean} [optional] если исходника нет, не прерывать сборку
 * @property {boolean} [grayscale] переводить ли в оттенки серого (по умолчанию да)
 */

/** @type {Job[]} */
const JOBS = [
  {
    source: 'ustav-original.pdf',
    out: 'ustav.pdf',
    width: 1100,
    quality: 52,
  },
  {
    // Свидетельство о государственной регистрации: одна страница.
    // Принимается и сканом PDF, и фотографией — сканы часто присылают
    // снимком с телефона.
    //
    // Цвет здесь сохраняется, в отличие от Устава: на бланке гербовая
    // сетка, синяя печать инспекции и тиснёный герб. В оттенках серого
    // документ выглядел бы копией сомнительного происхождения.
    source: 'egrul-original',
    out: 'egrul.pdf',
    width: 1240,
    quality: 78,
    grayscale: false,
    optional: true,
  },
];

/** Расширения, которые принимаются как исходник. */
const SOURCE_EXTENSIONS = ['.pdf', '.jpg', '.jpeg', '.png', '.tif', '.tiff', '.webp'];

/**
 * Ищет исходник по имени с расширением или без него.
 * @param {string} name
 * @returns {string | null}
 */
const findSource = (name) => {
  const direct = join(SOURCE_DIR, name);
  if (existsSync(direct) && statSync(direct).isFile()) return direct;

  for (const extension of SOURCE_EXTENSIONS) {
    const candidate = join(SOURCE_DIR, name + extension);
    if (existsSync(candidate)) return candidate;
  }
  return null;
};

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
 * Пересобирает PDF из скана или фотографии.
 * @param {Job} job
 * @param {string} sourcePath
 */
const rebuild = async (job, sourcePath) => {
  const workdir = mkdtempSync(join(tmpdir(), 'scan-'));
  try {
    /** @type {string[]} Полные пути к страницам-изображениям. */
    let images;

    if (sourcePath.toLowerCase().endsWith('.pdf')) {
      // Страницы вынимаются как изображения: текстового слоя в сканах нет.
      requireTool('pdfimages');
      execFileSync('pdfimages', ['-j', '-p', sourcePath, join(workdir, 'page')], { stdio: 'pipe' });

      images = readdirSync(workdir)
        .filter((name) => /\.(jpg|jpeg|ppm|png)$/i.test(name))
        .sort()
        .map((name) => join(workdir, name));
    } else {
      // Фотография или скан одной страницы.
      images = [sourcePath];
    }

    if (!images.length) throw new Error(`В ${job.source} не нашлось страниц`);

    const pdf = await PDFDocument.create();
    pdf.setTitle(job.out.replace(/\.pdf$/, ''));
    pdf.setProducer('СНП «Новая Искань»');

    // Даты берутся от исходника, а не от момента запуска: иначе каждая
    // пересборка давала бы новый файл при том же содержимом, и в истории
    // проекта копились бы мегабайтные различия на пустом месте.
    const sourceTime = statSync(sourcePath).mtime;
    pdf.setCreationDate(sourceTime);
    pdf.setModificationDate(sourceTime);

    for (const name of images) {
      // Поворот по данным EXIF нужен для фотографий с телефона:
      // иначе страница окажется лежащей на боку.
      let pipeline = sharp(name).rotate();

      // Оттенки серого: страницы чёрно-белого текста от цвета ничего
      // не выигрывают, а весят втрое больше. Для бланков с печатью
      // цвет сохраняется — см. пояснение у соответствующего документа.
      if (job.grayscale !== false) pipeline = pipeline.grayscale();

      const jpeg = await pipeline
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

const report = [];
const skipped = [];

for (const job of JOBS) {
  const sourcePath = findSource(job.source);

  if (!sourcePath) {
    if (job.optional) {
      skipped.push(job);
      continue;
    }
    throw new Error(
      `Не найден исходник ${job.source} — положите его в assets/source/documents/ ` +
        `(принимаются ${SOURCE_EXTENSIONS.join(', ')})`,
    );
  }

  report.push(await rebuild(job, sourcePath));
}

if (report.length) console.table(report);

for (const job of skipped) {
  console.log(
    `Пропущено: ${job.out} — нет исходника assets/source/documents/${job.source}` +
      `${SOURCE_EXTENSIONS.join('|')}`,
  );
}
