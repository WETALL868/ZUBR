<?php

/**
 * Карта сайта.
 *
 * Раньше sitemap.xml был обычным файлом: добавив товар, о нём надо было
 * вспомнить и вписать адрес руками, а забытая строка означала, что страницы
 * для поисковых систем не существует. Теперь список строится из базы, и
 * новый товар, категория или страница попадают в карту сами.
 *
 * Страницы, помеченные «не индексировать», в карту не попадают.
 */

declare(strict_types=1);

require_once __DIR__ . '/cms/repo.php';

/** Дата последнего изменения строки в формате карты сайта. */
function sitemap_date(?string $value): string
{
    $stamp = $value ? strtotime($value) : false;

    return date('Y-m-d', $stamp ?: time());
}

$urls = [];

$home = repo_page('');
$urls[] = ['/', sitemap_date($home['updated_at'] ?? null), 'weekly', '1.0'];

foreach (repo_categories() as $category) {
    if ((int)$category['noindex'] === 1) {
        continue;
    }
    $urls[] = [repo_category_url($category), sitemap_date($category['updated_at']), 'weekly', '0.9'];
}

foreach (repo_products() as $product) {
    if ((int)$product['noindex'] === 1) {
        continue;
    }
    $urls[] = [repo_product_url($product), sitemap_date($product['updated_at']), 'weekly', '0.9'];
}

foreach (cms_all(
    'SELECT slug, updated_at FROM pages WHERE status = ? AND noindex = 0 AND slug <> ? ORDER BY sort_order, id',
    ['published', '']
) as $page) {
    $urls[] = ['/' . $page['slug'] . '/', sitemap_date($page['updated_at']), 'monthly', '0.4'];
}

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
    . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($urls as [$path, $lastmod, $freq, $priority]) {
    $xml .= "  <url>\n"
        . '    <loc>' . htmlspecialchars(cms_absolute_url($path), ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n"
        . '    <lastmod>' . $lastmod . "</lastmod>\n"
        . '    <changefreq>' . $freq . "</changefreq>\n"
        . '    <priority>' . $priority . "</priority>\n"
        . "  </url>\n";
}

$xml .= "</urlset>\n";

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo $xml;
