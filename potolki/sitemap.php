<?php
/**
 * XML-карта сайта.
 *
 * Собирается из реестра страниц: попадают только те, что реально
 * существуют и разрешены к индексации. Черновики территорий,
 * служебные страницы и выключенные модули исключаются автоматически.
 *
 * Доступна по /sitemap.xml (через правило в .htaccess) и напрямую.
 * При большом числе URL отдаётся индекс карт: ?part=pages|geo|blog.
 */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

use Potolki\Seo\Sitemap;

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

// Демонстрационная версия не отдаёт список страниц: индексировать нечего.
if (is_staging()) {
    echo Sitemap::stagingUrlset();
    exit;
}

$part = isset($_GET['part']) && is_string($_GET['part']) ? $_GET['part'] : null;

if ($part !== null && isset(Sitemap::parts()[$part])) {
    echo Sitemap::urlset($part);
    exit;
}

echo Sitemap::needsSplit() ? Sitemap::index() : Sitemap::urlset();
