<?php

declare(strict_types=1);

/**
 * Приём заявок из всех форм сайта.
 *
 * Работает в двух режимах:
 *  • с JavaScript — принимает fetch-запрос и отвечает JSON без перезагрузки;
 *  • без JavaScript — обычный POST формы, ответ через redirect (шаблон PRG),
 *    чтобы обновление страницы не отправляло заявку повторно.
 */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

use Potolki\Forms\Lead;

session_start_safe();

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('X-Robots-Tag: noindex, nofollow');
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errors' => ['form' => 'Метод не поддерживается.']], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . site_url('/'));
    }
    exit;
}

// Защита от гигантских запросов: форма столько данных не отправляет.
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 200000) {
    http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'errors' => ['form' => 'Слишком большой запрос.']], JSON_UNESCAPED_UNICODE);
    exit;
}

$post = $_POST;

// Расчёт может прийти JSON-строкой из скрытого поля формы.
if (!empty($post['calc_json']) && is_string($post['calc_json'])) {
    $decoded = json_decode($post['calc_json'], true);
    if (is_array($decoded)) {
        $post['calc'] = $decoded;
    }
}

$result = (new Lead())->handle($post);

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if ($wantsJson) {
    http_response_code($result['status'] ?? 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Режим без JavaScript ──────────────────────────────────────────────────

$back = Potolki\Security\Sanitize::path($post['page'] ?? '/');

if ($result['ok']) {
    $_SESSION['lead_flash'] = [
        'id'      => $result['lead_id'],
        'message' => $result['message'],
        'email'   => $result['client_email_sent'] ?? false,
    ];
    header('Location: ' . site_url('/spasibo'), true, 303);
    exit;
}

$_SESSION['lead_errors'] = $result['errors'];
$_SESSION['lead_old'] = [
    'name'    => $post['name'] ?? '',
    'phone'   => $post['phone'] ?? '',
    'email'   => $post['email'] ?? '',
    'comment' => $post['comment'] ?? '',
];

header('Location: ' . rtrim(site_url($back), '/') . '/?form=error#form', true, 303);
exit;
