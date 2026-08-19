<?php

declare(strict_types=1);

/**
 * Проверка синтаксиса всех PHP-файлов проекта.
 *
 * Дешёвая, но полезная проверка: опечатка в шаблоне на 200-й странице
 * обнаруживается до, а не после загрузки на хостинг.
 */

T::suite('Синтаксис PHP');

$directories = ['config', 'includes', 'src', 'templates', 'content', 'api', 'tools', 'tests'];
$files = [APP_ROOT . '/index.php', APP_ROOT . '/404.php', APP_ROOT . '/sitemap.php'];

foreach ($directories as $directory) {
    $path = APP_ROOT . '/' . $directory;
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

// Маршруты создаются генератором, но проверить их тоже нужно.
$routeFiles = glob(APP_ROOT . '/*/index.php') ?: [];
$routeFiles = array_merge($routeFiles, glob(APP_ROOT . '/*/*/index.php') ?: []);
$routeFiles = array_merge($routeFiles, glob(APP_ROOT . '/*/*/*/index.php') ?: []);
$files = array_merge($files, $routeFiles);

$files = array_values(array_unique($files));
$errors = [];

foreach ($files as $file) {
    $output = [];
    $status = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

    if ($status !== 0) {
        $errors[] = str_replace(APP_ROOT . '/', '', $file) . ': ' . implode(' ', $output);
    }
}

T::ok($errors === [], sprintf('синтаксис корректен во всех %d файлах', count($files)), implode(' | ', array_slice($errors, 0, 5)));

T::suite('Отладочный код');

// Границы слова важны: иначе «dd(» найдётся внутри «add(», а «die(» — внутри «$die(».
$debugPatterns = [
    'var_dump' => '/(?<![\w$>])var_dump\s*\(/',
    'print_r'  => '/(?<![\w$>])print_r\s*\(/',
    'dd'       => '/(?<![\w$>])dd\s*\(/',
    'die'      => '/(?<![\w$>])die\s*\(/',
    'exit;'    => '/(?<![\w$>])exit\s*\(\s*[\'"]отладка/u',
];
$found = [];

foreach ($files as $file) {
    if (str_contains($file, '/tests/') || str_contains($file, '/tools/')) {
        continue;
    }
    $body = (string) file_get_contents($file);
    foreach ($debugPatterns as $name => $pattern) {
        if (preg_match($pattern, $body) === 1) {
            $found[] = str_replace(APP_ROOT . '/', '', $file) . ': ' . $name;
        }
    }
}

T::eq($found, [], 'в рабочем коде не осталось отладочных вызовов');

$jsFound = [];
foreach (glob(APP_ROOT . '/assets/js/*.js') ?: [] as $file) {
    $body = (string) file_get_contents($file);
    if (str_contains($body, 'console.log(') || str_contains($body, 'debugger')) {
        $jsFound[] = basename($file);
    }
}
T::eq($jsFound, [], 'в JavaScript не осталось отладочного вывода');
