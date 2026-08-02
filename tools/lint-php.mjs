/**
 * Проверка синтаксиса всех файлов PHP (php -l).
 * Запуск: npm run lint:php
 */
import { execFileSync } from 'node:child_process';
import { glob } from 'node:fs/promises';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));

const files = [];
for await (const path of glob(`${ROOT}/**/*.php`)) {
  if (path.includes('node_modules') || path.includes('/_private/')) continue;
  files.push(path);
}
files.sort();

let failed = 0;

for (const file of files) {
  const name = file.replace(`${ROOT}/`, '');
  try {
    execFileSync('php', ['-l', file], { stdio: 'pipe' });
    console.log(`  ok  ${name}`);
  } catch (error) {
    failed += 1;
    console.error(`ОШИБКА ${name}`);
    console.error(String(error.stdout || error.message));
  }
}

console.log(`\nПроверено файлов PHP: ${files.length}, с ошибками: ${failed}`);
process.exit(failed === 0 ? 0 : 1);
