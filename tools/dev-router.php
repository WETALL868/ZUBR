<?php
/**
 * Роутер для встроенного сервера PHP (php -S), повторяющий поведение Apache
 * на хостинге: DirectoryIndex index.html index.php и Options -Indexes.
 * Используется только для локальной разработки и тестов, на хостинг не влияет.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

// Запрет обхода каталогов и доступа к закрытым папкам (как .htaccess в _private).
if (str_contains($path, '..') || preg_match('#^/_private(/|$)#', $path)) {
    http_response_code(403);
    exit('403 Forbidden');
}

$target = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);

if (is_dir($target)) {
    foreach (['index.html', 'index.php'] as $indexFile) {
        $candidate = rtrim($target, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $indexFile;
        if (is_file($candidate)) {
            if (str_ends_with($candidate, '.php')) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                require $candidate;
                return true;
            }
            header('Content-Type: text/html; charset=utf-8');
            readfile($candidate);
            return true;
        }
    }
    http_response_code(403);
    exit('403 Forbidden');
}

if (is_file($target)) {
    return false; // отдаётся встроенным сервером как статика
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
$notFound = $root . DIRECTORY_SEPARATOR . '404.html';
if (is_file($notFound)) {
    readfile($notFound);
    return true;
}
echo '404 Not Found';
return true;
