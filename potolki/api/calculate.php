<?php

declare(strict_types=1);

/**
 * Серверный расчёт сметы.
 *
 * Браузер показывает предварительный итог мгновенно, но окончательные цифры
 * всегда приходят отсюда: цены есть только на сервере, и подменить сумму
 * из браузера нельзя.
 */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

use Potolki\Calculator\Calculator;
use Potolki\Security\RateLimit;

header('X-Robots-Tag: noindex, nofollow');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'errors' => ['form' => 'Метод не поддерживается.']], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 100000) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'errors' => ['form' => 'Слишком большой запрос.']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Расчёт не отправляет писем и не сохраняет данные, но защита от перебора нужна.
$limiter = new RateLimit(APP_ROOT . '/storage/rate-limit/calc', 120, 600);
if (!$limiter->hit(RateLimit::identity())) {
    http_response_code(429);
    echo json_encode([
        'ok' => false,
        'errors' => ['form' => 'Слишком много расчётов подряд. Подождите немного или позвоните нам.'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$input = [];

if ($raw !== '' && str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
} else {
    $input = $_POST;
    if (!empty($input['calc_json']) && is_string($input['calc_json'])) {
        $decoded = json_decode($input['calc_json'], true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

$result = Calculator::fromConfig()->calculate([
    'rooms'       => $input['rooms'] ?? [],
    'distance_km' => $input['distance_km'] ?? 0,
]);

http_response_code($result['ok'] ? 200 : 422);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
