<?php

declare(strict_types=1);

/**
 * Запуск всех проверок:  php tests/run.php
 * Отдельный набор:       php tests/run.php calculator
 *
 * Код возврата 0 — всё в порядке, 1 — есть провалившиеся проверки.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Тесты запускаются только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/lib.php';

$only = $argv[1] ?? null;

$suites = [
    'calculator' => __DIR__ . '/calculator_test.php',
    'forms'      => __DIR__ . '/forms_test.php',
    'seo'        => __DIR__ . '/seo_test.php',
    'geo'        => __DIR__ . '/geo_quality_test.php',
    'links'      => __DIR__ . '/links_test.php',
    'syntax'     => __DIR__ . '/syntax_test.php',
];

echo "Проверки сайта «" . config('brand.name') . "»\n";
echo "PHP " . PHP_VERSION . ", прайс " . config('prices.version') . "\n";

foreach ($suites as $name => $file) {
    if ($only !== null && $only !== $name) {
        continue;
    }
    if (!is_file($file)) {
        continue;
    }
    require $file;
}

exit(T::summary());
