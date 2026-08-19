<?php

declare(strict_types=1);

/**
 * Создание физических маршрутов.
 *
 * Запуск:  php tools/build-routes.php
 *          php tools/build-routes.php --dry-run   (только показать, что изменится)
 *
 * Что делает:
 *   1. Для каждой страницы из реестра создаёт каталог с index.php.
 *      Благодаря этому чистые URL работают без mod_rewrite: адрес — это
 *      настоящий каталог на диске.
 *   2. Удаляет каталоги, которые создал сам, но которых больше нет в реестре
 *      (например, после отключения раздела метро). Чужие файлы не трогает:
 *      удаляются только каталоги с нашей меткой в index.php.
 *   3. Обновляет строку Sitemap в robots.txt по домену из конфигурации.
 *   4. Записывает статический sitemap.xml, чтобы карта работала даже
 *      без правил перезаписи.
 *
 * Запускайте после: добавления страниц в контент, изменения списка территорий,
 * включения или выключения модулей, смены домена.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

use Potolki\Seo\PageIndex;
use Potolki\Seo\Sitemap;

const ROUTE_MARKER = 'ROUTE:auto-generated';

$dryRun = in_array('--dry-run', $argv, true);

$created = 0;
$updated = 0;
$removed = 0;
$kept = 0;

$expected = [];

foreach (PageIndex::all() as $page) {
    $url = $page['url'];

    if ($url === '/') {
        continue; // корневой index.php написан вручную
    }

    $relative = trim($url, '/');
    $dir = APP_ROOT . '/' . $relative;
    $file = $dir . '/index.php';
    $expected[$relative] = true;

    $content = renderStub($page);

    if (is_file($file) && !str_contains((string) file_get_contents($file), ROUTE_MARKER)) {
        // Файл написан вручную — не перезаписываем.
        $kept++;
        continue;
    }

    if (is_file($file) && file_get_contents($file) === $content) {
        continue;
    }

    $isNew = !is_file($file);

    if (!$dryRun) {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            fwrite(STDERR, "Не удалось создать каталог: {$dir}\n");
            continue;
        }
        file_put_contents($file, $content);
    }

    echo ($isNew ? '  + ' : '  ~ ') . $url . "\n";
    $isNew ? $created++ : $updated++;
}

// ── Удаление устаревших маршрутов ────────────────────────────────────────

$roots = ['services', 'ceilings', 'systems', 'lighting', 'rooms', 'portfolio', 'blog',
    'moskva', 'moskovskaya-oblast', 'metro', 'legal', 'about', 'contacts', 'faq',
    'prices', 'calculator', 'measurement', 'guarantees', 'payment', 'reviews',
    'promotions', 'geography', 'sitemap', 'thanks'];

foreach ($roots as $root) {
    $rootPath = APP_ROOT . '/' . $root;
    if (!is_dir($rootPath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $entry) {
        if (!$entry->isFile() || $entry->getFilename() !== 'index.php') {
            continue;
        }

        $relative = trim(str_replace(APP_ROOT, '', $entry->getPath()), '/');

        if (isset($expected[$relative])) {
            continue;
        }

        $body = (string) file_get_contents($entry->getPathname());
        if (!str_contains($body, ROUTE_MARKER)) {
            continue; // не наш файл — не трогаем
        }

        if (!$dryRun) {
            unlink($entry->getPathname());
            @rmdir($entry->getPath());
        }

        echo '  - /' . $relative . "/\n";
        $removed++;
    }

    // Пустые каталоги верхнего уровня тоже убираем.
    if (!$dryRun && is_dir($rootPath) && (glob($rootPath . '/*') ?: []) === []) {
        @rmdir($rootPath);
    }
}

// ── robots.txt и sitemap.xml ─────────────────────────────────────────────

$robotsFile = APP_ROOT . '/robots.txt';

if (is_file($robotsFile)) {
    $robots = (string) file_get_contents($robotsFile);
    $line = 'Sitemap: ' . Sitemap::fileUrl('/sitemap.xml');
    $robots = preg_replace('/^Sitemap:.*$/m', $line, $robots, 1, $count);

    if ((int) $count === 0) {
        $robots = rtrim($robots) . "\n\n" . $line . "\n";
    }

    if (!$dryRun) {
        file_put_contents($robotsFile, $robots);
    }
    echo "  robots.txt: " . $line . "\n";
}

if (!$dryRun) {
    file_put_contents(APP_ROOT . '/sitemap.xml', Sitemap::urlset());
}

// ── Итог ─────────────────────────────────────────────────────────────────

$indexable = count(PageIndex::indexable());
$total = count(PageIndex::all());

echo "\n";
echo $dryRun ? "Пробный запуск, ничего не изменено.\n" : "Готово.\n";
echo sprintf(
    "Создано: %d, обновлено: %d, удалено: %d, оставлено вручную написанных: %d\n",
    $created,
    $updated,
    $removed,
    $kept
);
echo sprintf("Всего страниц: %d, из них индексируется: %d\n", $total, $indexable);

/** Содержимое маршрутного файла. */
function renderStub(array $page): string
{
    $params = $page['params'] === []
        ? ''
        : ', ' . var_export($page['params'], true);

    $params = preg_replace('/\s+/', ' ', $params);
    $marker = ROUTE_MARKER;

    return <<<PHP
<?php
/** {$page['url']} — {$page['title']}. {$marker}. Создано tools/build-routes.php, не редактируйте вручную. */

\$d = __DIR__;
while (!is_file(\$d . '/includes/bootstrap.php') && \$d !== '/') {
    \$d = dirname(\$d);
}
require \$d . '/includes/bootstrap.php';

render('{$page['template']}'{$params});

PHP;
}
