/**
 * Проставляет номер версии в ссылки на assets/site.css и assets/site.js.
 *
 * Зачем. Стили и скрипт лежат по постоянным адресам, а .htaccess просит
 * браузер держать их в кеше неделю. После обновления сайта посетитель
 * (и владелец, который проверяет результат) ещё несколько дней видел бы
 * старую версию. Номер в адресе — ?v=4 — делает адрес новым, и браузер
 * скачивает файл заново.
 *
 * Номер берётся из первой строки assets/site.css: «ВЕРСИЯ ФАЙЛА: N».
 * Меняете стили или скрипт — увеличиваете там номер и выполняете
 * npm run assets:version.
 *
 * Запуск: npm run assets:version
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { glob } from 'node:fs/promises';
import { join, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));

/** Читает номер версии из шапки assets/site.css. */
export const assetVersion = () => {
  const css = readFileSync(join(ROOT, 'assets', 'site.css'), 'utf8');
  const found = css.match(/ВЕРСИЯ ФАЙЛА:\s*(\d+)/);
  if (!found) {
    throw new Error('В assets/site.css не найдена строка «ВЕРСИЯ ФАЙЛА: N»');
  }
  return found[1];
};

/**
 * Заменяет адреса стилей и скрипта на адреса с номером версии.
 * @param {string} html
 * @param {string} version
 */
export const applyVersion = (html, version) =>
  html
    .replace(/\/assets\/site\.css(\?v=\d+)?/g, `/assets/site.css?v=${version}`)
    .replace(/\/assets\/site\.js(\?v=\d+)?/g, `/assets/site.js?v=${version}`);

const main = async () => {
  const version = assetVersion();
  const changed = [];

  /** Страницы сайта и страница-ответ формы без JavaScript. */
  const targets = [join(ROOT, 'api', 'appeals', 'index.php')];
  for await (const path of glob(`${ROOT}/**/*.html`)) targets.push(path);

  for (const path of targets) {
    if (path.includes('/node_modules/') || path.includes('/dist/')) continue;
    const html = readFileSync(path, 'utf8');
    const next = applyVersion(html, version);
    if (next !== html) {
      writeFileSync(path, next);
      changed.push(relative(ROOT, path));
    }
  }

  console.log(
    changed.length
      ? `Версия ${version} проставлена в ${changed.length} страниц:\n  ${changed.join('\n  ')}`
      : `Версия ${version} уже проставлена во всех страницах.`,
  );
};

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  await main();
}
