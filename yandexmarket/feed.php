<?php

/**
 * Фид Яндекс.Маркета.
 *
 * Товары, цены, наличие, характеристики и фотографии берутся из базы CMS —
 * из тех же таблиц, что и страницы сайта. Отдельного файла с каталогом
 * больше нет, поэтому фид не может разойтись с витриной: цена, изменённая
 * в административной панели, попадает и на сайт, и в маркетплейс.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/cms/repo.php';

function yml_text(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function yml_base_url(): string
{
    $base = trim((string)cms_setting('site', 'base_url', ''));
    if ($base !== '') {
        return rtrim($base, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return 'https://comp-uter.ru';
    }

    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded === 'https';

    return ($https ? 'https://' : 'http://') . $host;
}

function yml_absolute_url(string $base, string $path): string
{
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function yml_product_url(string $base, array $product, string $utm): string
{
    $url = rtrim($base, '/') . '/products/' . rawurlencode((string)$product['slug']) . '/';
    if ($utm !== '') {
        $url .= '?' . ltrim($utm, '?&');
    }

    return $url;
}

function yml_offer(array $product, string $base): string
{
    $price = repo_price($product);
    $params = repo_product_feed_params((int)$product['id']);
    $category = repo_category_by_id($product['category_id'] ? (int)$product['category_id'] : null);

    $available = $product['availability'] !== 'out_of_stock' ? 'true' : 'false';
    $categoryId = (string)($category['yml_category_id'] ?? $category['id'] ?? 1);
    $country = (string)($params['Страна-изготовитель'] ?? cms_setting('yml', 'default_country', 'Малайзия'));
    $utm = (string)cms_setting('yml', 'utm', '');

    $paramsXml = '';
    foreach ($params as $name => $value) {
        $paramsXml .= '      <param name="' . yml_text((string)$name) . '">' . yml_text((string)$value) . "</param>\n";
    }

    return '    <offer id="' . yml_text((string)$product['sku']) . '" available="' . $available . "\">\n"
        . '      <url>' . yml_text(yml_product_url($base, $product, $utm)) . "</url>\n"
        . '      <price>' . yml_text(cms_money_machine($price)) . "</price>\n"
        . '      <currencyId>' . yml_text((string)cms_setting('yml', 'currency', 'RUR')) . "</currencyId>\n"
        . '      <categoryId>' . yml_text($categoryId) . "</categoryId>\n"
        . '      <picture>' . yml_text(yml_absolute_url($base, repo_image_original($product))) . "</picture>\n"
        . "      <store>true</store>\n"
        . "      <pickup>true</pickup>\n"
        . "      <delivery>true</delivery>\n"
        . '      <name>' . yml_text((string)$product['name']) . "</name>\n"
        . '      <vendor>' . yml_text((string)($product['brand'] ?: 'Intel')) . "</vendor>\n"
        . '      <vendorCode>' . yml_text((string)$product['sku']) . "</vendorCode>\n"
        . '      <description>' . yml_text((string)$product['description']) . "</description>\n"
        . '      <sales_notes>' . yml_text((string)cms_setting('yml', 'sales_notes', '')) . "</sales_notes>\n"
        . '      <country_of_origin>' . yml_text($country) . "</country_of_origin>\n"
        . $paramsXml
        . "    </offer>\n";
}

function yml_generate_catalog(): string
{
    $base = yml_base_url();
    $shopName = (string)cms_setting('yml', 'shop_name', 'Comp-Uter');
    $company = (string)cms_setting('site', 'legal_name', $shopName);
    $currency = (string)cms_setting('yml', 'currency', 'RUR');

    $categoriesXml = '';
    foreach (repo_categories() as $category) {
        $id = (string)($category['yml_category_id'] ?: $category['id']);
        $name = (string)($category['yml_name'] ?: $category['name']);
        $categoriesXml .= '      <category id="' . yml_text($id) . '">' . yml_text($name) . "</category>\n";
    }

    // Позиция без цены в фид не попадает: Яндекс.Маркет отклоняет предложение
    // без <price>, а пустой тег сломал бы весь фид.
    //
    // Позиция без настоящей фотографии — тоже: отдавать в маркетплейс картинку
    // «Фото товара готовится» хуже, чем на день не показать позицию. Как только
    // фотография появится в карточке товара, он вернётся в фид сам.
    $offers = '';
    foreach (repo_products() as $product) {
        if ((int)$product['in_yml'] !== 1) {
            continue;
        }
        if (repo_price($product) === null || repo_image_is_placeholder($product)) {
            continue;
        }
        $offers .= yml_offer($product, $base);
    }

    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . '<yml_catalog date="' . date('Y-m-d H:i') . "\">\n"
        . "  <shop>\n"
        . '    <name>' . yml_text($shopName) . "</name>\n"
        . '    <company>' . yml_text($company) . "</company>\n"
        . '    <url>' . yml_text($base) . "</url>\n"
        . "    <currencies>\n"
        . '      <currency id="' . yml_text($currency) . "\" rate=\"1\" />\n"
        . "    </currencies>\n"
        . "    <categories>\n"
        . $categoriesXml
        . "    </categories>\n"
        . "    <offers>\n"
        . $offers
        . "    </offers>\n"
        . "  </shop>\n"
        . "</yml_catalog>\n";
}

/**
 * Фид с кэшем на диске.
 *
 * Кэш сбрасывается и по времени, и как только в базе меняется товар: иначе
 * после правки цены маркетплейс ещё минуту получал бы старую.
 */
function yml_cached_catalog(): array
{
    $ttl = max(60, (int)cms_setting('yml', 'cache_ttl_seconds', 60));
    $cacheDir = CMS_ROOT . '/storage/cache';
    $cacheFile = $cacheDir . '/yandexmarket.xml';

    $touched = (string)cms_value('SELECT MAX(updated_at) FROM products');
    $stamp = $touched !== '' ? (int)strtotime($touched) : 0;

    if (is_file($cacheFile) && is_readable($cacheFile)) {
        $age = (int)filemtime($cacheFile);
        if ((time() - $age < $ttl) && $age >= $stamp) {
            return [(string)file_get_contents($cacheFile), 'hit'];
        }
    }

    $xml = yml_generate_catalog();

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    if (is_dir($cacheDir) && is_writable($cacheDir)) {
        @file_put_contents($cacheFile, $xml, LOCK_EX);
    }

    return [$xml, 'miss'];
}
