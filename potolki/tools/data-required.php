<?php

declare(strict_types=1);

/**
 * Обновляет таблицу прайса в DATA_REQUIRED.md.
 *
 * Запуск: php tools/data-required.php
 *
 * Таблица собирается из config/prices.php, поэтому список позиций
 * для утверждения владельцем всегда совпадает с тем, что реально
 * считает калькулятор.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$p = config('prices');
$approved = $p['approved_by_owner'] === true ? '☑ подтверждено' : '☐ не подтверждено';

$rows = [];

foreach ($p['canvas'] as $key => $canvas) {
    $rows[] = ['Полотно: ' . $canvas['title'], $canvas['material'], '₽/м²', 'калькулятор, прайс'];
    if ((float) $canvas['install_extra'] > 0) {
        $rows[] = ['Надбавка к монтажу: ' . $canvas['title'], $canvas['install_extra'], '₽/м²', 'калькулятор'];
    }
}

$rows[] = ['Монтаж полотна, базовая ставка', $p['install']['base_per_m2'], '₽/м²', 'калькулятор, прайс'];
$rows[] = ['Минимальная оплачиваемая площадь', $p['install']['min_billable_area_m2'], 'м²', 'калькулятор'];
$rows[] = ['Минимальная сумма заказа', $p['minimum_order'], '₽', 'калькулятор, прайс'];

foreach ($p['mounting'] as $mounting) {
    $rows[] = ['Примыкание: ' . $mounting['title'], $mounting['rate'], '₽/пог. м', 'калькулятор, прайс'];
}

$rows[] = ['Дополнительный угол', $p['extra_corner'], '₽/шт.', 'калькулятор'];

$lighting = [
    'spot_platform'       => 'Точечный светильник: закладная',
    'spot_cutout'         => 'Точечный светильник: раскрой',
    'spot_install'        => 'Точечный светильник: установка',
    'chandelier_platform' => 'Люстра: закладная',
    'chandelier_install'  => 'Люстра: монтаж',
    'light_line_per_m'    => 'Световая линия',
    'track_per_m'         => 'Встроенный трек',
    'track_surface_per_m' => 'Накладной трек',
    'led_strip_per_m'     => 'Светодиодная лента',
];

foreach ($lighting as $key => $title) {
    $unit = str_contains($key, 'per_m') ? '₽/пог. м' : '₽/шт.';
    $rows[] = [$title, $p['lighting'][$key], $unit, 'калькулятор, прайс'];
}

$rows[] = ['Блок питания ' . $p['lighting']['power_supply']['capacity_w'] . ' Вт', $p['lighting']['power_supply']['price'], '₽/шт.', 'калькулятор'];
$rows[] = ['Мощность ленты на метр', $p['lighting']['led_power_per_m'], 'Вт/пог. м', 'подбор блоков питания'];

$structures = [
    'hidden_cornice_per_m'   => ['Скрытый карниз', '₽/пог. м'],
    'electric_cornice_per_m' => ['Электрокарниз', '₽/пог. м'],
    'second_level_per_m'     => ['Второй уровень', '₽/пог. м'],
    'photo_print_per_m2'     => ['Фотопечать', '₽/м²'],
    'pipe_bypass'            => ['Обход трубы', '₽/шт.'],
    'cabinet_bypass'         => ['Обход шкафа или колонны', '₽/шт.'],
    'vent'                   => ['Вентиляционная решётка', '₽/шт.'],
    'hatch'                  => ['Ревизионный люк', '₽/шт.'],
    'removal_per_m2'         => ['Демонтаж старого потолка', '₽/м²'],
];

foreach ($structures as $key => [$title, $unit]) {
    $rows[] = [$title, $p['structures'][$key], $unit, 'калькулятор, прайс'];
}

$rows[] = ['Бесплатная зона выезда', $p['travel']['free_km'], 'км от МКАД', 'калькулятор, гео-страницы'];
$rows[] = ['Стоимость километра сверх зоны', $p['travel']['rate_per_km'], '₽/км', 'калькулятор, прайс'];
$rows[] = ['Максимальное расстояние выезда', $p['travel']['max_km'], 'км', 'калькулятор'];

foreach ($p['factors']['height'] as $step) {
    $rows[] = [
        'Коэффициент по высоте: ' . $step['title'],
        $step['factor'] === null ? 'индивидуально' : number_format((float) $step['factor'], 2, ',', ' '),
        'коэффициент',
        'калькулятор (только работы)',
    ];
}

foreach ($p['factors']['wall'] as $wall) {
    $rows[] = [
        'Коэффициент по стенам: ' . $wall['title'],
        number_format((float) $wall['factor'], 2, ',', ' '),
        'коэффициент',
        'калькулятор (только работы)',
    ];
}

$rows[] = ['Разброс быстрого расчёта', $p['uncertainty_percent'], '%', 'быстрый расчёт'];
$rows[] = ['Скидка', $p['discount']['percent'], '%', 'калькулятор'];
$rows[] = ['Срок изготовления, минимум', $p['terms_days']['production_min'], 'дней', 'сайт, письма'];
$rows[] = ['Срок изготовления, максимум', $p['terms_days']['production_max'], 'дней', 'сайт, письма'];

$table = [];
$table[] = sprintf('Версия прайса: **%s**. Статус: **%s**.', $p['version'], $approved);
$table[] = '';
$table[] = '| Позиция | Текущая ставка | Единица | Где используется | Подтверждено |';
$table[] = '|---|---:|---|---|---|';

foreach ($rows as [$title, $value, $unit, $where]) {
    $table[] = sprintf('| %s | %s | %s | %s | ☐ |', $title, $value, $unit, $where);
}

$table[] = '';
$table[] = sprintf('Всего позиций к утверждению: **%d**.', count($rows));

$file = APP_ROOT . '/DATA_REQUIRED.md';
$content = (string) file_get_contents($file);

$updated = preg_replace(
    '/(<!-- PRICE-TABLE:START -->).*?(<!-- PRICE-TABLE:END -->)/s',
    "$1\n" . implode("\n", $table) . "\n$2",
    $content
);

if ($updated === null || $updated === $content) {
    echo "Таблица не изменилась.\n";
} else {
    file_put_contents($file, $updated);
    echo "DATA_REQUIRED.md обновлён: " . count($rows) . " позиций прайса.\n";
}
