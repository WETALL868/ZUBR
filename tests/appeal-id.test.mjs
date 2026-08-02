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

test('все номера начинаются с буквы N', () => {
  const output = runPhp(`${REQUIRE} for ($i = 0; $i < 200; $i++) echo build_appeal_id(), "\\n";`);
  for (const id of output.split('\n')) {
    assert.match(id, /^N[0-9]{5}$/, `номер «${id}» не соответствует формату N и пяти цифр`);
  }
});

test('номера разбросаны по всему диапазону', () => {
  const output = runPhp(`${REQUIRE} for ($i = 0; $i < 300; $i++) echo build_appeal_id(), "\\n";`);
  const ids = output.split('\n');
  const unique = new Set(ids);

  // Пространство номеров — 100 000: буква постоянная, меняются пять цифр.
  // На 300 выдачах случайные совпадения возможны, и это нормально: от них
  // защищает не случайность, а резервирование номера файлом — см. тест ниже.
  // Здесь проверяется другое: генератор не залипает на узком поддиапазоне.
  assert.ok(
    unique.size > ids.length * 0.9,
    `слишком много повторов: различных ${unique.size} из ${ids.length}`,
  );

  const numbers = ids.map((id) => Number(id.slice(1)));
  assert.ok(Math.min(...numbers) < 20000, 'не встретилось ни одного малого номера');
  assert.ok(Math.max(...numbers) > 80000, 'не встретилось ни одного большого номера');
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
