/**
 * Генерация короткого номера обращения.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, rmSync, writeFileSync, readdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { runPhp, ROOT } from './helpers/site.mjs';

const REQUIRE = `require '${join(ROOT, 'api', 'appeals', 'appeal-id.php')}';`;

test('номер состоит из одной заглавной латинской буквы и пяти цифр', () => {
  const output = runPhp(`${REQUIRE} for ($i = 0; $i < 500; $i++) echo build_appeal_id(), "\\n";`);
  const ids = output.split('\n');

  assert.equal(ids.length, 500);
  for (const id of ids) {
    assert.equal(id.length, 6, `номер «${id}» должен быть длиной 6 символов`);
    assert.match(id, /^[A-Z][0-9]{5}$/, `номер «${id}» не соответствует формату`);
  }
});

test('в номере не используются буквы I и O — их путают с 1 и 0', () => {
  const output = runPhp(`${REQUIRE} for ($i = 0; $i < 500; $i++) echo build_appeal_id(), "\\n";`);
  for (const id of output.split('\n')) {
    assert.ok(!'IO'.includes(id[0]), `номер «${id}» содержит спорную букву`);
  }
});

test('номера различаются между собой', () => {
  const output = runPhp(`${REQUIRE} for ($i = 0; $i < 300; $i++) echo build_appeal_id(), "\\n";`);
  const ids = output.split('\n');
  const unique = new Set(ids);

  // Пространство номеров — 2,4 млн, поэтому на 300 выдачах совпадений быть не должно.
  assert.equal(unique.size, ids.length);
});

test('занятый номер не выдаётся повторно', () => {
  const dir = mkdtempSync(join(tmpdir(), 'appeal-id-'));
  try {
    // Занимаем часть номеров и проверяем, что подбор обходит их.
    const busy = [];
    for (let i = 0; i < 40; i++) {
      const id = `A${String(10000 + i).padStart(5, '0')}`;
      busy.push(id);
      writeFileSync(join(dir, `${id}.record.php`), '<?php exit; ?>\n');
    }

    // Номер, который точно занят, повторно не бронируется.
    const output = runPhp(
      `${REQUIRE} $r = reserve_appeal_id('${dir}'); echo $r === null ? 'NULL' : $r[0];`,
    );

    assert.match(output, /^[A-Z][0-9]{5}$/);
    assert.ok(!busy.includes(output), 'подбор вернул уже занятый номер');

    // Файл занят сразу же — это и есть проверка уникальности.
    const files = readdirSync(dir).filter((name) => name.endsWith('.record.php'));
    assert.ok(files.includes(`${output}.record.php`), 'номер не был закреплён файлом');
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
});

test('при полностью занятом пространстве номер не выдаётся, а не дублируется', () => {
  const dir = mkdtempSync(join(tmpdir(), 'appeal-id-full-'));
  try {
    // Подбор с нулём попыток обязан честно вернуть «номер не найден».
    const output = runPhp(
      `${REQUIRE} $r = reserve_appeal_id('${dir}', 0); echo $r === null ? 'NULL' : $r[0];`,
    );
    assert.equal(output, 'NULL');
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
});

test('номера прежнего формата продолжают распознаваться', () => {
  const output = runPhp(
    `${REQUIRE} ` +
      `echo is_appeal_id('N48271') ? '1' : '0';` +
      `echo is_appeal_id('НИ-20260802-A1B2C3D4') ? '1' : '0';` +
      `echo is_appeal_id('../../etc/passwd') ? '1' : '0';` +
      `echo is_appeal_id('n48271') ? '1' : '0';` +
      `echo is_appeal_id('N4827') ? '1' : '0';`,
  );

  assert.equal(output, '11000', 'ожидались: новый формат, старый формат, затем три отказа');
});
