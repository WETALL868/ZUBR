/**
 * Общая обвязка для тестов: поднимает сайт на встроенном сервере PHP
 * и открывает браузер. Обращения тестов пишутся во временную папку,
 * поэтому реальные данные в _private не затрагиваются.
 */
import { spawn, execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync, cpSync, writeFileSync, readdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const ROOT = dirname(dirname(dirname(fileURLToPath(import.meta.url))));

/**
 * Страница браузера с накопленным списком ошибок консоли:
 * тесты проверяют его после перехода.
 * @typedef {import('playwright').Page & { consoleErrors: string[] }} TestPage
 */

/** Учётные данные административного раздела внутри тестовой копии сайта. */
export const TEST_ADMIN = { login: 'test-admin', password: 'test-password-2026' };

/** Ждёт, пока сервер начнёт отвечать. */
const waitForServer = async (baseUrl, timeoutMs = 15000) => {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    try {
      const response = await fetch(`${baseUrl}/`);
      if (response.ok) return;
    } catch {
      // сервер ещё не поднялся
    }
    await new Promise((resolve) => setTimeout(resolve, 120));
  }
  throw new Error(`Сервер не ответил за ${timeoutMs} мс`);
};

/**
 * Копия сайта во временной папке — чтобы тесты могли свободно
 * создавать и удалять обращения.
 */
export const startSite = async () => {
  const workdir = mkdtempSync(join(tmpdir(), 'novaya-iskan-test-'));

  for (const entry of readdirSync(ROOT)) {
    if (['node_modules', '.git', 'screenshots', 'dist'].includes(entry)) continue;
    cpSync(join(ROOT, entry), join(workdir, entry), { recursive: true });
  }

  // Во временной копии всегда свои учётные данные: тесты не зависят
  // от рабочего config.php и не раскрывают настоящий пароль.
  writeFileSync(
    join(workdir, 'config.php'),
    `<?php\nreturn [\n` +
      `    'admin_login' => '${TEST_ADMIN.login}',\n` +
      `    'admin_password' => '${TEST_ADMIN.password}',\n` +
      `    'recipient_email' => '',\n` +
      `];\n`,
  );

  const port = 8100 + Math.floor(Math.random() * 800);
  // Пределы задаются явно теми же значениями, что стоят в .user.ini
  // и .htaccess сайта: иначе тесты вложений проверяли бы настройки
  // машины разработчика, а не настройки проекта.
  const server = spawn(
    'php',
    [
      '-d', 'upload_max_filesize=6M',
      '-d', 'post_max_size=7M',
      '-d', 'max_file_uploads=1',
      '-S', `127.0.0.1:${port}`,
      '-t', workdir,
      join(workdir, 'tools', 'dev-router.php'),
    ],
    { cwd: workdir, stdio: 'ignore' },
  );

  const baseUrl = `http://127.0.0.1:${port}`;
  await waitForServer(baseUrl);

  // Полная сборка Chromium, а не облегчённая: в headless shell нет
  // встроенного модуля просмотра PDF, и navigator.pdfViewerEnabled
  // всегда false. Тогда проверка просмотра документов ушла бы
  // по запасному пути и ничего бы не проверила.
  const browser = await chromium.launch({ channel: 'chromium' });

  return {
    baseUrl,
    workdir,
    browser,
    submissionsDir: join(workdir, '_private', 'submissions'),
    /**
     * @param {{width: number, height: number}} viewport
     * @returns {Promise<TestPage>}
     */
    async page(viewport = { width: 375, height: 812 }) {
      const context = await browser.newContext({
        viewport,
        isMobile: viewport.width < 768,
        hasTouch: viewport.width < 768,
        deviceScaleFactor: 2,
      });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', (error) => errors.push(String(error.message)));
      page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
      });
      const testPage = /** @type {TestPage} */ (page);
      testPage.consoleErrors = errors;
      return testPage;
    },
    async stop() {
      await browser.close();
      server.kill('SIGTERM');
      rmSync(workdir, { recursive: true, force: true });
    },
  };
};

/** Список всех HTML-страниц сайта (без служебных папок). */
export const sitePages = [
  '/',
  '/about',
  '/news',
  '/documents',
  '/meetings',
  '/contacts',
  '/appeal',
  '/legal/privacy',
  '/legal/cookies',
  '/legal/user-agreement',
  '/legal/personal-data-consent',
];

/**
 * Запускает фрагмент PHP в контексте проекта и возвращает вывод.
 * @param {string} code
 */
export const runPhp = (code) =>
  execFileSync('php', ['-r', code], { cwd: ROOT, encoding: 'utf8' }).trim();

export { ROOT };
