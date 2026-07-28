<?php

/**
 * Создание входа в панель управления.
 *
 * Запуск из папки сайта:
 *     php cms/install/create-admin.php
 *
 * Пароль спрашивается в консоли и в базе хранится только хешем
 * (password_hash с текущим алгоритмом PHP). Ни в одном файле проекта пароля
 * нет — ни в исходниках, ни в архиве.
 *
 * Тем же скриптом меняют забытый пароль: укажите существующий логин.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Скрипт запускается только из консоли.\n");
}

function ask(string $question, bool $hidden = false): string
{
    echo $question;

    if ($hidden && function_exists('shell_exec') && stripos(PHP_OS_FAMILY, 'win') === false) {
        // Пароль не должен оставаться на экране и в истории терминала.
        @shell_exec('stty -echo 2>/dev/null');
        $value = (string)fgets(STDIN);
        @shell_exec('stty echo 2>/dev/null');
        echo "\n";
    } else {
        $value = (string)fgets(STDIN);
    }

    return trim($value);
}

try {
    cms_db()->query('SELECT 1 FROM admin_users LIMIT 1');
} catch (Throwable) {
    exit("Таблицы ещё не созданы. Сначала выполните:\n    php cms/install/migrate.php --apply\n");
}

echo "=== Создание входа в панель управления ===\n\n";

$login = ask('Логин: ');
if ($login === '') {
    exit("Логин не может быть пустым.\n");
}

$existing = cms_one('SELECT * FROM admin_users WHERE login = ?', [$login]);
if ($existing) {
    echo "Такой пользователь уже есть — пароль будет изменён.\n";
}

$name = $existing['name'] ?? '';
if ($name === '') {
    $name = ask('Имя (как показывать в панели): ');
    $name = $name !== '' ? $name : $login;
}

$password = ask('Пароль: ', true);
$repeat   = ask('Пароль ещё раз: ', true);

if ($password !== $repeat) {
    exit("Пароли не совпадают. Ничего не изменено.\n");
}

// Короткий пароль к панели, из которой правят цены и видят заказы, — это
// не мелочь: перебор десяти тысяч вариантов занимает минуты.
if (mb_strlen($password) < 10) {
    exit("Пароль слишком короткий: нужно не меньше 10 символов.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existing) {
    cms_update('admin_users', [
        'password_hash' => $hash,
        'name'          => $name,
        'is_active'     => 1,
        // Смена пароля завершает все открытые сессии этого пользователя.
        'session_epoch' => (int)$existing['session_epoch'] + 1,
        'updated_at'    => cms_now(),
    ], 'id = :id', ['id' => (int)$existing['id']]);

    echo "\nПароль изменён. Все ранее открытые сессии завершены.\n";
} else {
    // Первый созданный пользователь — владелец: только он видит журнал
    // действий и управляет остальными.
    $isFirst = (int)cms_value('SELECT COUNT(*) FROM admin_users') === 0;

    cms_insert('admin_users', [
        'name'          => $name,
        'login'         => $login,
        'password_hash' => $hash,
        'role'          => $isFirst ? 'owner' : 'manager',
        'is_active'     => 1,
        'session_epoch' => 1,
        'created_at'    => cms_now(),
    ]);

    echo "\nПользователь создан" . ($isFirst ? " с правами владельца.\n" : ".\n");
}

echo "Вход: " . cms_base_url() . "/admin/\n";
