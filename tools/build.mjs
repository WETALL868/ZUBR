/**
 * Сборка архивов для передачи.
 *
 * Собираются два архива:
 *   dist/novaya-iskan-site.zip     — то, что распаковывается в корень
 *                                    хостинга (готовый сайт);
 *   dist/novaya-iskan-project.zip  — полный проект с инструментами
 *                                    и тестами, без node_modules.
 *
 * Запуск: npm run build
 */
import { execFileSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, rmSync, readFileSync, writeFileSync, statSync } from 'node:fs';
import { glob } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const DIST = join(ROOT, 'dist');

/** Что попадает на хостинг. */
const SITE_ENTRIES = [
  '.htaccess',
  '.user.ini',
  'INSTALL_FIRSTVDS.txt',
  '_next',
  '_private',
  'about',
  'admin',
  'api',
  'appeal',
  'assets/admin.css',
  'assets/site.css',
  'assets/site.js',
  'assets/img',
  'contacts',
  'documents',
  'favicon.svg',
  'index.html',
  'infrastructure',
  'legal',
  'meetings',
  'news',
];

/**
 * Что дополнительно входит в полный проект.
 *
 * Оригиналы сканов (assets/source/documents) намеренно не включаются:
 * они есть у владельца, а архив из-за них вырос бы втрое. Для сборки
 * готовых PDF они нужны только один раз.
 */
const PROJECT_EXTRA = [
  'assets/source/hero-road-production.png',
  'assets/source/novaya-iskan-logo.png',
  'assets/source/og.png',
  'docs',
  'eslint.config.mjs',
  'package.json',
  'package-lock.json',
  'tests',
  'tools',
  'tsconfig.json',
  '.gitignore',
  'config.example.php',
];

const mb = (path) => (statSync(path).size / 1024 / 1024).toFixed(2);

/** Копирует список файлов и папок в целевую директорию. */
const collect = (entries, target) => {
  mkdirSync(target, { recursive: true });
  for (const entry of entries) {
    const from = join(ROOT, entry);
    if (!existsSync(from)) {
      console.warn(`  пропущено (нет файла): ${entry}`);
      continue;
    }
    const to = join(target, entry);
    mkdirSync(dirname(to), { recursive: true });
    cpSync(from, to, { recursive: true });
  }
};

/** Проверяет, что в сборку не попали настоящие пароли. */
const assertNoSecrets = async (dir) => {
  const found = [];
  for await (const path of glob(`${dir}/**/*.php`)) {
    const text = readFileSync(path, 'utf8');
    if (/'admin_password'\s*=>\s*'(?!ЗАМЕНИТЕ)/.test(text)) {
      found.push(path.replace(`${dir}/`, ''));
    }
  }
  if (found.length) {
    throw new Error(
      `В сборку попал файл с настоящим паролем: ${found.join(', ')}. ` +
        'Пароль передаётся отдельно, в архиве его быть не должно.',
    );
  }
};

const zip = (folder, name) => {
  execFileSync('zip', ['-r', '-q', '-X', join(DIST, name), folder], { cwd: DIST });
  return join(DIST, name);
};

const main = async () => {
  rmSync(DIST, { recursive: true, force: true });
  mkdirSync(DIST, { recursive: true });

  console.log('Сборка архива для хостинга…');
  const siteDir = join(DIST, 'novaya-iskan-site');
  collect(SITE_ENTRIES, siteDir);

  // Обращения посетителей в архив не попадают.
  rmSync(join(siteDir, '_private', 'submissions'), { recursive: true, force: true });

  // На хостинге нужен config.php; в архив кладётся образец без пароля.
  cpSync(join(ROOT, 'config.example.php'), join(siteDir, 'config.php'));
  cpSync(join(ROOT, 'config.example.php'), join(siteDir, 'config.example.php'));

  // Папка для файлов документов создаётся заранее, чтобы её было видно.
  const filesDir = join(siteDir, 'documents', 'files');
  mkdirSync(filesDir, { recursive: true });
  if (!existsSync(join(filesDir, 'README.txt'))) {
    writeFileSync(
      join(filesDir, 'README.txt'),
      'Сюда кладутся файлы документов каталога.\n' +
        'Имена файлов указаны в описании карточек в documents/index.html\n' +
        '(атрибут data-file). Как только файл появится, у карточки\n' +
        'автоматически появится кнопка «Скачать».\n',
    );
  }

  await assertNoSecrets(siteDir);
  const sitePath = zip('novaya-iskan-site', 'novaya-iskan-site.zip');

  console.log('Сборка полного архива проекта…');
  const projectDir = join(DIST, 'novaya-iskan-project');
  collect([...SITE_ENTRIES, ...PROJECT_EXTRA], projectDir);
  rmSync(join(projectDir, '_private', 'submissions'), { recursive: true, force: true });
  await assertNoSecrets(projectDir);
  const projectPath = zip('novaya-iskan-project', 'novaya-iskan-project.zip');

  rmSync(siteDir, { recursive: true, force: true });
  rmSync(projectDir, { recursive: true, force: true });

  console.table([
    { Архив: 'novaya-iskan-site.zip', 'Размер, МБ': mb(sitePath), Назначение: 'распаковать в корень хостинга' },
    { Архив: 'novaya-iskan-project.zip', 'Размер, МБ': mb(projectPath), Назначение: 'полный проект с инструментами' },
  ]);
};

await main();
