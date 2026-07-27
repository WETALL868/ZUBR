<?php

/**
 * Проверка файла цен /data/prices.json.
 *
 * Открыть в браузере:  https://comp-uter.ru/tools/check-prices.php
 * Запустить в консоли:  php tools/check-prices.php
 *
 * Показывает:
 *   - сколько товаров в каталоге сайта;
 *   - у скольких есть цена;
 *   - у каких товаров цены нет;
 *   - какие записи в файле цен лишние (не совпали ни с одним товаром);
 *   - повторяющиеся идентификаторы и артикулы;
 *   - неправильно записанные значения.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/prices.php';

$catalog = require dirname(__DIR__) . '/yandexmarket/products.php';
$report  = prices_diagnostics(is_array($catalog) ? $catalog : []);

$hasProblems = $report['missing']
    || $report['problems']
    || $report['orphans']
    || $report['duplicates']
    || $report['mode'] !== 'json';

/* ------------------------------------------------------------------ CLI */

if (PHP_SAPI === 'cli') {
    echo "Файл цен: {$report['file']}\n";
    echo 'Товаров в каталоге: ' . $report['catalog'] . "\n";
    echo 'С ценой: ' . $report['priced'] . ' из ' . $report['catalog'] . "\n\n";

    foreach ($report['notes'] as $note) {
        echo "! {$note}\n";
    }
    foreach (['missing' => 'Без цены', 'problems' => 'Проблемы в значениях', 'orphans' => 'Лишние записи в файле цен', 'duplicates' => 'Повторы'] as $key => $title) {
        if (!$report[$key]) {
            continue;
        }
        echo "\n{$title}:\n";
        foreach ($report[$key] as $line) {
            echo "  - {$line}\n";
        }
    }

    echo "\n";
    foreach ($report['rows'] as $row) {
        printf(
            "%-16s %-16s %-22s %s\n",
            $row['slug'],
            $row['sku'],
            $row['price'] === null ? 'ЦЕНЫ НЕТ' : prices_format($row['price']),
            $row['stock'] === null ? '' : (PRICES_STOCK_LABELS[$row['stock']] ?? '')
        );
    }

    echo "\n" . ($hasProblems ? "Есть замечания — смотрите выше.\n" : "Всё в порядке: у каждого товара есть цена.\n");
    exit($hasProblems ? 1 : 0);
}

/* --------------------------------------------------------------- браузер */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

function check_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function check_list(string $title, array $items, string $tone): void
{
    if (!$items) {
        return;
    }
    echo '<section class="block ' . $tone . '"><h2>' . check_text($title) . ' (' . count($items) . ')</h2><ul>';
    foreach ($items as $item) {
        echo '<li>' . check_text((string)$item) . '</li>';
    }
    echo '</ul></section>';
}

?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Проверка цен | Comp-Uter</title>
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
      th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #232b36; }
      th { color: #8b97a8; font-weight: 600; }
      td.no { color: #ff8b8b; font-weight: 700; }
      .wrap { overflow-x: auto; }
      .hint { color: #8b97a8; font-size: 13px; margin-top: 18px; }
    </style>
  </head>
  <body>
    <h1>Проверка цен</h1>
    <p class="file">Файл цен: <?= check_text($report['file']) ?></p>

    <div class="summary">
      <div class="tile"><b><?= (int)$report['catalog'] ?></b><span>товаров на сайте</span></div>
      <div class="tile"><b><?= (int)$report['priced'] ?></b><span>из них с ценой</span></div>
      <div class="tile"><b><?= count($report['missing']) ?></b><span>без цены</span></div>
      <div class="tile"><b><?= count($report['orphans']) ?></b><span>лишних записей</span></div>
    </div>

    <?php if (!$hasProblems): ?>
      <section class="block ok"><h2>Всё в порядке</h2>
        <p style="margin:0">У каждого товара на сайте есть цена, повторов и лишних записей нет.</p>
      </section>
    <?php endif; ?>

    <?php
      foreach ($report['notes'] as $note) {
          echo '<section class="block bad"><h2>Файл цен</h2><p style="margin:0">' . check_text($note) . '</p></section>';
      }
      check_list('Товары без цены — на сайте показывается «' . PRICES_NO_PRICE_TEXT . '»', $report['missing'], 'bad');
      check_list('Неправильно записанные значения', $report['problems'], 'warn');
      check_list('Повторяющиеся идентификаторы или артикулы', $report['duplicates'], 'bad');
      check_list('Лишние записи в файле цен (такого товара на сайте нет)', $report['orphans'], 'warn');
    ?>

    <section class="block">
      <h2>Все товары</h2>
      <div class="wrap">
        <table>
          <thead>
            <tr><th>Идентификатор</th><th>Артикул</th><th>Название</th><th>Цена</th><th>Старая цена</th><th>Наличие</th><th>Ед.</th></tr>
          </thead>
          <tbody>
            <?php foreach ($report['rows'] as $row): ?>
              <tr>
                <td><?= check_text($row['slug']) ?></td>
                <td><?= check_text($row['sku']) ?></td>
                <td><?= check_text($row['name']) ?></td>
                <td<?= $row['price'] === null ? ' class="no"' : '' ?>><?= check_text($row['price'] === null ? 'ЦЕНЫ НЕТ' : prices_format($row['price'])) ?></td>
                <td><?= check_text($row['old_price'] === null ? '—' : prices_format($row['old_price'])) ?></td>
                <td><?= check_text($row['stock'] === null ? '—' : (PRICES_STOCK_LABELS[$row['stock']] ?? '')) ?></td>
                <td><?= check_text((string)($row['unit'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <p class="hint">
      Цены редактируются в файле <b>/data/prices.json</b>. Порядок действий — в файле
      PRICE_MANAGEMENT_INSTRUCTION.txt в корне сайта.
    </p>
  </body>
</html>
