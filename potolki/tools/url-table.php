<?php

declare(strict_types=1);

/**
 * Выгрузка таблицы всех URL с мета-данными.
 *
 *   php tools/url-table.php            — Markdown в docs/urls.md
 *   php tools/url-table.php --csv      — дополнительно CSV в docs/urls.csv
 *
 * Таблица нужна для контроля SEO-структуры: по ней видно, какие страницы
 * индексируются, какие ждут наполнения и нет ли пересечения интентов
 * (несколько страниц под один и тот же запрос).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

use Potolki\Seo\GeoQuality;
use Potolki\Seo\PageIndex;

$withCsv = in_array('--csv', $argv, true);

$typeNames = [
    'home' => 'Главная', 'prices' => 'Цены', 'tool' => 'Инструмент',
    'service' => 'Услуга', 'hub' => 'Хаб раздела', 'page' => 'Страница',
    'ceiling' => 'Полотно', 'system' => 'Конструкция', 'lighting' => 'Освещение',
    'room' => 'Помещение', 'article' => 'Статья', 'case' => 'Кейс',
    'legal' => 'Документ', 'service-page' => 'Служебная',
    'geo-hub' => 'Гео-хаб', 'geo-city' => 'Москва', 'geo-region' => 'Область',
    'geo-okrug' => 'Округ', 'geo-rajon' => 'Район', 'geo-mo_city' => 'Город области',
    'geo-metro' => 'Метро',
];

$rows = [];

foreach (PageIndex::all() as $page) {
    $intent = '';
    $notes = [];
    $parent = '';

    if (str_starts_with($page['type'], 'geo-') && isset($page['params']['slug'])) {
        $loc = location((string) $page['params']['slug']);
        if ($loc !== null) {
            $intent = 'натяжные потолки ' . $loc['name_pred'];
            $parent = (string) ($loc['parent'] ?? '');
            $issues = GeoQuality::issues($loc);
            if ($issues !== []) {
                $notes[] = 'требует наполнения: ' . implode(', ', $issues);
            }
        }
    } elseif ($page['type'] === 'ceiling' || $page['type'] === 'system' || $page['type'] === 'lighting') {
        $intent = mb_strtolower($page['title']) . ' — цена и монтаж';
    } elseif ($page['type'] === 'room') {
        $intent = mb_strtolower($page['title']);
    } elseif ($page['type'] === 'article') {
        $intent = 'информационный';
    }

    if ($page['index'] === false && $notes === []) {
        $notes[] = $page['type'] === 'service-page' ? 'служебная страница' : 'закрыта от индексации';
    }

    $rows[] = [
        'url'       => $page['url'],
        'type'      => $typeNames[$page['type']] ?? $page['type'],
        'title'     => $page['title'],
        'intent'    => $intent,
        'parent'    => $parent,
        'index'     => $page['index'] ? 'да' : 'нет',
        'status'    => $page['status'],
        'stage'     => $page['stage'] ?? '',
        'template'  => $page['template'],
        'notes'     => implode('; ', $notes),
    ];
}

// ── Markdown ─────────────────────────────────────────────────────────────

$total = count($rows);
$indexed = count(array_filter($rows, static fn (array $r): bool => $r['index'] === 'да'));

$md = [];
$md[] = '# Таблица URL и SEO-статусов';
$md[] = '';
$md[] = 'Файл создаётся командой `php tools/url-table.php`. Не редактируйте вручную.';
$md[] = '';
$md[] = sprintf('Дата выгрузки: %s · Всего страниц: **%d** · Индексируется: **%d**', date('d.m.Y'), $total, $indexed);
$md[] = '';
$md[] = '| URL | Тип | Заголовок | Основной интент | Родитель | Индексируется | Статус | Этап | Шаблон | Примечание |';
$md[] = '|---|---|---|---|---|---|---|---|---|---|';

foreach ($rows as $row) {
    $md[] = sprintf(
        '| `%s` | %s | %s | %s | %s | %s | %s | %s | %s | %s |',
        $row['url'],
        $row['type'],
        str_replace('|', '\\|', $row['title']),
        $row['intent'],
        $row['parent'],
        $row['index'],
        $row['status'],
        $row['stage'],
        $row['template'],
        $row['notes']
    );
}

$md[] = '';
$md[] = '## Сводка по типам';
$md[] = '';
$md[] = '| Тип | Всего | Индексируется |';
$md[] = '|---|---:|---:|';

$byType = [];
foreach ($rows as $row) {
    $byType[$row['type']]['total'] = ($byType[$row['type']]['total'] ?? 0) + 1;
    $byType[$row['type']]['indexed'] = ($byType[$row['type']]['indexed'] ?? 0) + ($row['index'] === 'да' ? 1 : 0);
}

foreach ($byType as $type => $counts) {
    $md[] = sprintf('| %s | %d | %d |', $type, $counts['total'], $counts['indexed']);
}

if (!is_dir(APP_ROOT . '/docs')) {
    mkdir(APP_ROOT . '/docs', 0755, true);
}

file_put_contents(APP_ROOT . '/docs/urls.md', implode("\n", $md) . "\n");
echo "docs/urls.md — {$total} страниц, индексируется {$indexed}\n";

// ── CSV ──────────────────────────────────────────────────────────────────

if ($withCsv) {
    $handle = fopen(APP_ROOT . '/docs/urls.csv', 'w');
    fwrite($handle, "\xEF\xBB\xBF"); // BOM, чтобы Excel открыл кириллицу
    fputcsv($handle, ['URL', 'Тип', 'Заголовок', 'Интент', 'Родитель', 'Индексируется', 'Статус', 'Этап', 'Шаблон', 'Примечание'], ';');

    foreach ($rows as $row) {
        fputcsv($handle, array_values($row), ';');
    }

    fclose($handle);
    echo "docs/urls.csv — выгружено\n";
}
