<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('novaya_iskan_admin');
session_set_cookie_params([
    'lifetime' => 43200,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function is_admin(): bool
{
    return !empty($_SESSION['novaya_iskan_admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /admin/');
        exit;
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function storage_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'submissions';
}

function load_record(string $path): ?array
{
    $contents = @file_get_contents($path);
    if ($contents === false) return null;
    $position = strpos($contents, "\n");
    if ($position === false) return null;
    $decoded = base64_decode(substr($contents, $position + 1), true);
    if ($decoded === false) return null;
    $record = @unserialize($decoded, ['allowed_classes' => false]);
    return is_array($record) ? $record : null;
}
