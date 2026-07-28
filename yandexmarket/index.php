<?php

/**
 * Отдача фида Яндекс.Маркета по адресу /yandexmarket/.
 *
 * Адрес не изменился — изменился источник: вместо файла с каталогом фид
 * собирается из базы CMS.
 */

declare(strict_types=1);

require __DIR__ . '/feed.php';

[$xml, $cacheStatus] = yml_cached_catalog();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-YML-Cache: ' . $cacheStatus);

echo $xml;
