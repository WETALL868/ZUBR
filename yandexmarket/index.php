<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$products = require __DIR__ . '/products.php';
require __DIR__ . '/feed.php';

if (!is_array($config) || !is_array($products)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'YML feed configuration error.';
    exit;
}

[$xml, $cache_status] = yml_cached_catalog($config, $products);

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-YML-Cache: ' . $cache_status);

echo $xml;
