<?php

/**
 * Проверка фотографий товаров.
 *
 * Открыть в браузере:  https://comp-uter.ru/tools/check-images.php
 * Запустить в консоли:  php tools/check-images.php
 *
 * Показывает:
 *   - у всех ли товаров есть локальная фотография;
 *   - где вместо фото пока стоит заглушка и какой файл нужно загрузить;
 *   - не осталось ли в данных внешних адресов картинок;
 *   - есть ли WebP-версия для быстрой загрузки;
 *   - какие файлы в папке лишние.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/images.php';

$report = product_images_diagnostics();
$hasProblems = $report['missing'] || $report['external'] || !$report['dir_exists'];

/* ------------------------------------------------------------------ CLI */

if (PHP_SAPI === 'cli') {
    echo 'Папка фотографий: ' . $report['dir'] . ($report['dir_exists'] ? '' : '  — НЕ НАЙДЕНА') . "\n";
    echo 'Товаров: ' . $report['total'] . ',  с локальным фото: ' . $report['ok'] . "\n\n";

    foreach ([
        'missing'  => 'Нет фотографии (показывается заглушка)',
        'external' => 'Внешние адреса картинок в данных',
        'no_webp'  => 'Нет WebP-версии (сайт покажет JPG, это допустимо)',
        'orphans'  => 'Лишние файлы в папке',
    ] as $key => $title) {
        if (!$report[$key]) {
            continue;
        }
        echo $title . ":\n";
        foreach ($report[$key] as $line) {
            echo '  - ' . $line . "\n";
        }
        echo "\n";
    }

    foreach ($report['rows'] as $row) {
        printf(
            "%-16s %-46s %-10s %s\n",
            $row['slug'],
            $row['web'],
            $row['size'],
            $row['placeholder'] ? 'ЗАГЛУШКА' : 'ок'
        );
    }

    echo "\n" . ($hasProblems ? "Есть замечания — смотрите выше.\n" : "Все фотографии на месте и хранятся локально.\n");
    exit($hasProblems ? 1 : 0);
}

/* --------------------------------------------------------------- браузер */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

function img_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function img_list(string $title, array $items, string $tone): void
{
    if (!$items) {
        return;
    }
    echo '<section class="block ' . $tone . '"><h2>' . img_text($title) . ' (' . count($items) . ')</h2><ul>';
    foreach ($items as $item) {
        echo '<li>' . img_text((string)$item) . '</li>';
    }
    echo '</ul></section>';
}

?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Проверка фотографий товаров | Comp-Uter</title>
    <style>
      body { margin: 0; padding: 24px; background: #0e1116; color: #e8edf4;
             font: 15px/1.55 -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
      h1 { font-size: 22px; margin: 0 0 4px; }
      .file { color: #8b97a8; font-size: 13px; margin: 0 0 20px; word-break: break-all; }
      .summary { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
      .tile { flex: 1 1 150px; padding: 14px 16px; border: 1px solid #232b36;
              border-radius: 10px; background: #141922; }
      .tile b { display: block; font-size: 26px; line-height: 1.1; }
      .tile span { color: #8b97a8; font-size: 13px; }
      .block { padding: 14px 16px; border-radius: 10px; margin-bottom: 14px;
               border: 1px solid #232b36; background: #141922; }
      .block h2 { font-size: 16px; margin: 0 0 8px; }
      .block ul { margin: 0; padding-left: 20px; }
      .bad { border-color: #6d2b2b; background: #1d1315; }
      .warn { border-color: #6b5620; background: #1c1810; }
      .ok { border-color: #2b5f45; background: #111d17; }
      table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
      th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #232b36; vertical-align: middle; }
      th { color: #8b97a8; font-weight: 600; }
      td.no { color: #ff8b8b; font-weight: 700; }
      td img { display: block; width: 92px; height: auto; border-radius: 6px; border: 1px solid #232b36; }
      .wrap { overflow-x: auto; }
      .hint { color: #8b97a8; font-size: 13px; margin-top: 18px; }
      code { background: #1b212b; padding: 1px 5px; border-radius: 4px; }
    </style>
  </head>
  <body>
    <h1>Проверка фотографий товаров</h1>
    <p class="file">Папка: <?= img_text($report['dir']) ?><?= $report['dir_exists'] ? '' : ' — ПАПКА НЕ НАЙДЕНА' ?></p>

    <div class="summary">
      <div class="tile"><b><?= (int)$report['total'] ?></b><span>товаров на сайте</span></div>
      <div class="tile"><b><?= (int)$report['ok'] ?></b><span>с локальным фото</span></div>
      <div class="tile"><b><?= count($report['missing']) ?></b><span>без фото (заглушка)</span></div>
      <div class="tile"><b><?= count($report['external']) ?></b><span>внешних адресов</span></div>
    </div>

    <?php if (!$hasProblems): ?>
      <section class="block ok"><h2>Всё в порядке</h2>
        <p style="margin:0">У каждого товара есть своя фотография, все файлы хранятся на нашем сервере.</p>
      </section>
    <?php endif; ?>

    <?php
      img_list('Нет фотографии — сейчас показывается заглушка. Загрузите эти файлы', $report['missing'], 'bad');
      img_list('Внешние адреса картинок в данных', $report['external'], 'bad');
      img_list('Нет WebP-версии (сайт покажет JPG — это допустимо)', $report['no_webp'], 'warn');
      img_list('Лишние файлы в папке (ни один товар их не использует)', $report['orphans'], 'warn');
    ?>

    <section class="block">
      <h2>Все товары</h2>
      <div class="wrap">
        <table>
          <thead>
            <tr><th>Фото</th><th>Идентификатор</th><th>Артикул</th><th>Файл на сайте</th><th>Размер</th><th>Вес</th><th>Состояние</th></tr>
          </thead>
          <tbody>
            <?php foreach ($report['rows'] as $row): ?>
              <tr>
                <td><img src="<?= img_text($row['web']) ?>" alt="" /></td>
                <td><?= img_text($row['slug']) ?></td>
                <td><?= img_text($row['sku']) ?></td>
                <td><code><?= img_text($row['web']) ?></code></td>
                <td><?= img_text($row['size']) ?></td>
                <td><?= $row['bytes'] ? img_text(round($row['bytes'] / 1024) . ' КБ') : '—' ?></td>
                <td<?= $row['placeholder'] ? ' class="no"' : '' ?>><?= $row['placeholder'] ? 'ЗАГЛУШКА' : 'ок' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <p class="hint">
      Чтобы заменить фото товара — положите файл <code>&lt;идентификатор&gt;.jpg</code> в папку
      <code><?= img_text($report['dir']) ?></code> и обновите страницу.
      Подробности — в файле IMAGE_MANAGEMENT_INSTRUCTION.txt в корне сайта.
    </p>
  </body>
</html>
