<?php

declare(strict_types=1);

function yml_text(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function yml_base_url(array $config): string
{
    $base_url = trim((string)($config['base_url'] ?? ''));
    if ($base_url !== '') {
        return rtrim($base_url, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return 'https://comp-uter.ru';
    }

    $forwarded_proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded_proto === 'https';

    return ($is_https ? 'https://' : 'http://') . $host;
}

function yml_absolute_url(string $base_url, string $path): string
{
    // Product images may already be absolute (imported from an external CDN).
    // Prefixing those again produced https://comp-uter.ru/https://cdn... and
    // Yandex.Market rejects an offer whose <picture> is not a valid URL.
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }

    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

function yml_product_url(string $base_url, array $product, string $utm): string
{
    $slug = (string)($product['slug'] ?? '');
    $url = rtrim($base_url, '/') . '/products/' . rawurlencode($slug) . '/';
    if ($utm !== '') {
        $url .= '?' . ltrim($utm, '?&');
    }

    return $url;
}

function yml_offer(array $config, array $product, string $base_url): string
{
    $currency_id = (string)($config['currency_id'] ?? 'RUR');
    $category_id = (string)($product['category_id'] ?? ($config['default_category_id'] ?? 1));
    $vendor = (string)($product['brand'] ?? 'Intel');
    $country = (string)($product['params']['Страна-изготовитель'] ?? 'Малайзия');
    $utm = (string)($config['utm'] ?? '');
    $params = '';

    foreach (($product['params'] ?? []) as $name => $value) {
        $params .= '      <param name="' . yml_text((string)$name) . '">' . yml_text((string)$value) . "</param>\n";
    }

    return '    <offer id="' . yml_text((string)$product['id']) . "\" available=\"true\">\n"
        . '      <url>' . yml_text(yml_product_url($base_url, $product, $utm)) . "</url>\n"
        . '      <price>' . yml_text((string)$product['price']) . "</price>\n"
        . '      <currencyId>' . yml_text($currency_id) . "</currencyId>\n"
        . '      <categoryId>' . yml_text($category_id) . "</categoryId>\n"
        . '      <picture>' . yml_text(yml_absolute_url($base_url, (string)$product['picture'])) . "</picture>\n"
        . "      <store>true</store>\n"
        . "      <pickup>true</pickup>\n"
        . "      <delivery>true</delivery>\n"
        . '      <name>' . yml_text((string)$product['name']) . "</name>\n"
        . '      <vendor>' . yml_text($vendor) . "</vendor>\n"
        . '      <vendorCode>' . yml_text((string)$product['id']) . "</vendorCode>\n"
        . '      <description>' . yml_text((string)$product['description']) . "</description>\n"
        . "      <sales_notes>Доставка по России: СДЭК, Яндекс Маркет, Ozon, Wildberries и другими транспортными компаниями; условия уточняются у менеджера.</sales_notes>\n"
        . '      <country_of_origin>' . yml_text($country) . "</country_of_origin>\n"
        . $params
        . "    </offer>\n";
}

function yml_generate_catalog(array $config, array $products): string
{
    $base_url = yml_base_url($config);
    $shop_name = (string)($config['shop_name'] ?? 'Comp-Uter');
    $company = (string)($config['company'] ?? $shop_name);
    $currency_id = (string)($config['currency_id'] ?? 'RUR');
    $categories = (array)($config['categories'] ?? [1 => 'Процессоры Intel Xeon']);

    $categoriesXml = '';
    foreach ($categories as $id => $name) {
        $categoriesXml .= '      <category id="' . yml_text((string)$id) . '">' . yml_text((string)$name) . "</category>\n";
    }

    $offers = '';
    foreach ($products as $product) {
        $offers .= yml_offer($config, $product, $base_url);
    }

    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . '<yml_catalog date="' . date('Y-m-d H:i') . "\">\n"
        . "  <shop>\n"
        . '    <name>' . yml_text($shop_name) . "</name>\n"
        . '    <company>' . yml_text($company) . "</company>\n"
        . '    <url>' . yml_text($base_url) . "</url>\n"
        . "    <currencies>\n"
        . '      <currency id="' . yml_text($currency_id) . "\" rate=\"1\" />\n"
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

function yml_cached_catalog(array $config, array $products): array
{
    $ttl = max(60, (int)($config['cache_ttl_seconds'] ?? 60));
    $cache_dir = __DIR__ . '/cache';
    $cache_file = $cache_dir . '/yandexmarket.xml';

    if (is_file($cache_file) && is_readable($cache_file) && (time() - filemtime($cache_file) < $ttl)) {
        return [file_get_contents($cache_file), 'hit'];
    }

    $xml = yml_generate_catalog($config, $products);

    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    if (is_dir($cache_dir) && is_writable($cache_dir)) {
        @file_put_contents($cache_file, $xml, LOCK_EX);
    }

    return [$xml, 'miss'];
}
