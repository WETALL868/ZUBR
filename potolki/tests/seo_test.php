<?php

declare(strict_types=1);

use Potolki\Seo\PageIndex;
use Potolki\Seo\Sitemap;

/**
 * SEO: уникальность мета-тегов, корректность sitemap и robots,
 * отсутствие конкуренции страниц за один и тот же запрос.
 */

T::suite('SEO: реестр страниц');

$pages = PageIndex::all();
$urls = array_column($pages, 'url');

T::ok(count($pages) > 50, 'реестр страниц заполнен (' . count($pages) . ' страниц)');
T::eq(count($urls), count(array_unique($urls)), 'нет двух страниц с одинаковым URL');

$badUrls = array_filter($urls, static fn (string $u): bool => !str_starts_with($u, '/') || !str_ends_with($u, '/'));
T::eq($badUrls, [], 'все адреса начинаются и заканчиваются слэшем');

$upper = array_filter($urls, static fn (string $u): bool => preg_match('/[A-ZА-Я_ ]/u', $u) === 1);
T::eq($upper, [], 'в адресах нет заглавных букв, пробелов и подчёркиваний');

$indexable = PageIndex::indexable();
T::ok(count($indexable) > 30, 'индексируемых страниц достаточно (' . count($indexable) . ')');
T::ok(count($indexable) < count($pages), 'часть страниц закрыта от индексации осознанно');

$service = PageIndex::byUrl('/thanks/');
T::ok($service !== null && $service['index'] === false, 'страница благодарности закрыта от индексации');

T::suite('SEO: уникальность заголовков и описаний');

$titles = [];
$descriptions = [];

foreach (['ceilings', 'systems', 'lighting', 'rooms', 'services'] as $set) {
    foreach (content($set) as $slug => $item) {
        if (($item['enabled'] ?? true) === false) {
            continue;
        }
        $titles[$set . '/' . $slug] = $item['seo']['title'];
        $descriptions[$set . '/' . $slug] = $item['seo']['description'];
    }
}

foreach (content('blog') as $slug => $post) {
    $titles['blog/' . $slug] = $post['seo']['title'];
    $descriptions['blog/' . $slug] = $post['seo']['description'];
}

foreach (config('locations') as $slug => $location) {
    if (($location['status'] ?? 'draft') !== 'published') {
        continue;
    }
    $titles['geo/' . $slug] = $location['seo']['title'];
    $descriptions['geo/' . $slug] = $location['seo']['description'];
}

$duplicateTitles = array_keys(array_filter(array_count_values($titles), static fn (int $n): bool => $n > 1));
T::eq($duplicateTitles, [], 'все title уникальны');

$duplicateDescriptions = array_keys(array_filter(array_count_values($descriptions), static fn (int $n): bool => $n > 1));
T::eq($duplicateDescriptions, [], 'все description уникальны');

$longTitles = array_filter($titles, static fn (string $t): bool => mb_strlen($t) > 75);
T::eq($longTitles, [], 'ни один title не длиннее 75 символов');

$shortDescriptions = array_filter($descriptions, static fn (string $d): bool => mb_strlen($d) < 70);
T::eq($shortDescriptions, [], 'все description содержательны (от 70 символов)');

$longDescriptions = array_filter($descriptions, static fn (string $d): bool => mb_strlen($d) > 200);
T::eq($longDescriptions, [], 'ни один description не длиннее 200 символов');

T::suite('SEO: пересечение интентов');

/**
 * Страницы Москвы, округа, района и метро не должны продвигаться
 * по одному и тому же запросу: у каждой свой топоним в H1.
 */
$h1 = [];
foreach (config('locations') as $slug => $location) {
    $h1[$slug] = $location['seo']['h1'];
}
T::eq(count($h1), count(array_unique($h1)), 'у каждой территории собственный H1');

$geoPages = array_filter(PageIndex::all(), static fn (array $p): bool => str_starts_with($p['type'], 'geo-'));
$indexableGeo = array_filter($geoPages, static fn (array $p): bool => $p['index'] === true);

T::ok(count($indexableGeo) > 0, 'часть географических страниц опубликована');
T::ok(
    count($indexableGeo) < count($geoPages),
    'массовая публикация географических страниц не производится: опубликовано '
        . count($indexableGeo) . ' из ' . count($geoPages)
);

T::suite('SEO: sitemap');

$xml = Sitemap::urlset();
$parsed = @simplexml_load_string($xml);

T::ok($parsed !== false, 'sitemap — валидный XML');
T::eq((int) ($parsed !== false ? count($parsed->url) : 0), count($indexable), 'в sitemap ровно столько адресов, сколько индексируемых страниц');

$sitemapUrls = [];
if ($parsed !== false) {
    foreach ($parsed->url as $url) {
        $sitemapUrls[] = (string) $url->loc;
    }
}

$draftInSitemap = [];
foreach ($pages as $page) {
    if ($page['index'] === false && in_array(absolute_url($page['url']), $sitemapUrls, true)) {
        $draftInSitemap[] = $page['url'];
    }
}
T::eq($draftInSitemap, [], 'черновики и служебные страницы не попадают в sitemap');

$absolute = array_filter($sitemapUrls, static fn (string $u): bool => !str_starts_with($u, 'http'));
T::eq($absolute, [], 'все адреса в sitemap абсолютные');

T::suite('SEO: robots.txt');

$robots = (string) @file_get_contents(APP_ROOT . '/robots.txt');

T::ok($robots !== '', 'robots.txt существует');

if (is_staging()) {
    T::ok(str_contains($robots, 'User-agent: *'), 'в robots.txt есть директива для всех роботов');
    T::ok(str_contains($robots, 'Disallow: /'), 'демонстрационный режим: индексация запрещена целиком');
    T::ok(!str_contains($robots, 'Sitemap: '), 'демонстрационный режим: карта сайта не публикуется');
    T::ok(!str_contains($robots, 'Allow: /'), 'демонстрационный режим: разрешающих правил нет');
} else {
    T::ok(str_contains($robots, 'Sitemap: '), 'в robots.txt указана карта сайта');
    T::ok(str_contains($robots, 'Disallow: /api/'), 'служебные точки API закрыты');
    T::ok(str_contains($robots, 'Disallow: /config/'), 'каталог конфигурации закрыт');
    T::ok(str_contains($robots, 'Disallow: /thanks/'), 'страница благодарности закрыта');
    T::ok(str_contains($robots, 'Clean-param:'), 'параметры отсечены директивой Clean-param');
}

T::suite('SEO: юридические страницы');

$legalPages = PageIndex::ofType('legal');
T::ok(count($legalPages) >= 6, 'опубликованы все обязательные юридические документы');

foreach (['privacy-policy', 'personal-data-consent', 'advertising-consent', 'cookie-policy', 'user-agreement', 'requisites'] as $slug) {
    T::ok(content_item('legal', $slug) !== null, 'документ /legal/' . $slug . '/ существует');
}
