<?php

/**
 * Единый источник изображений товаров сайта comp-uter.ru.
 *
 * Путь к фотографии каждого товара задаётся ОДИН раз — в поле 'picture'
 * файла yandexmarket/products.php. Отсюда его берут все места сайта:
 * карточки каталога, страницы товаров, Open Graph, Twitter Card, JSON-LD и
 * фид Яндекс.Маркета. Внешних адресов (CDN Ozon и т.п.) в проекте больше нет.
 *
 * Как это работает:
 *   'picture' => '/public/images/products/e5-2699-v4.jpg'
 *
 *   - в <img> на сайте подставляется .webp, если такой файл лежит рядом
 *     (грузится быстрее), иначе — сам .jpg;
 *   - в og:image, JSON-LD и фид Яндекс.Маркета всегда уходит .jpg по
 *     абсолютному адресу: не все внешние сервисы понимают WebP;
 *   - если файла на сервере нет, показывается заглушка placeholder.jpg,
 *     а не «битая» картинка.
 *
 * Проверить, у всех ли товаров есть фото: /tools/check-images.php
 */

declare(strict_types=1);

if (!defined('PRODUCT_IMAGES_DIR')) {
    define('PRODUCT_IMAGES_DIR', '/public/images/products');
}

const PRODUCT_IMAGE_PLACEHOLDER = PRODUCT_IMAGES_DIR . '/placeholder.jpg';
const PRODUCT_IMAGE_BASE_URL = 'https://comp-uter.ru';

/** Корень сайта на диске. */
function product_image_root(): string
{
    return dirname(__DIR__);
}

/** Каталог товаров (названия, характеристики, пути к фото). Читается один раз. */
function product_catalog(): array
{
    static $catalog = null;
    if ($catalog === null) {
        $file = product_image_root() . '/yandexmarket/products.php';
        $data = is_file($file) ? require $file : [];
        $catalog = is_array($data) ? $data : [];
    }

    return $catalog;
}

/** Запись каталога по внутреннему идентификатору (slug) или по артикулу. */
function product_entry(?string $key): ?array
{
    $key = mb_strtolower(trim((string)$key));
    if ($key === '') {
        return null;
    }

    foreach (product_catalog() as $entry) {
        if (mb_strtolower((string)($entry['slug'] ?? '')) === $key
            || mb_strtolower((string)($entry['id'] ?? '')) === $key) {
            return $entry;
        }
    }

    return null;
}

/** Файл существует на диске? Пути всегда вида /public/images/... */
function product_image_exists(string $path): bool
{
    if ($path === '' || preg_match('~^(https?:)?//~i', $path)) {
        return false;
    }

    return is_file(product_image_root() . '/' . ltrim($path, '/'));
}

/**
 * Путь к оригиналу (JPG/PNG) для микроразметки, Open Graph и фида.
 * Внешний адрес, оставшийся в данных, намеренно игнорируется — товарные
 * фотографии должны лежать только на нашем сервере.
 */
function product_image_original(?string $key): string
{
    $entry = product_entry($key);
    $path  = (string)($entry['picture'] ?? '');

    if (preg_match('~^(https?:)?//~i', $path)) {
        // Данные ещё содержат внешнюю ссылку: пробуем локальный файл по slug.
        $path = PRODUCT_IMAGES_DIR . '/' . mb_strtolower((string)($entry['slug'] ?? '')) . '.jpg';
    }

    if (product_image_exists($path)) {
        return $path;
    }

    // Иногда оригинал сохраняют в PNG (например, с прозрачностью).
    $png = preg_replace('~\.(jpe?g)$~i', '.png', $path);
    if (is_string($png) && product_image_exists($png)) {
        return $png;
    }

    return PRODUCT_IMAGE_PLACEHOLDER;
}

/**
 * Путь для <img> на сайте: рядом лежащий .webp, если он есть, иначе оригинал.
 */
function product_image_web(?string $key): string
{
    $original = product_image_original($key);
    $webp = preg_replace('~\.(jpe?g|png)$~i', '.webp', $original);

    if (is_string($webp) && $webp !== $original && product_image_exists($webp)) {
        return $webp;
    }

    return $original;
}

/** Абсолютный адрес оригинала — для og:image, JSON-LD и Яндекс.Маркета. */
function product_image_absolute(?string $key, string $base = PRODUCT_IMAGE_BASE_URL): string
{
    return rtrim($base, '/') . '/' . ltrim(product_image_original($key), '/');
}

/** Показывается ли сейчас заглушка вместо настоящего фото. */
function product_image_is_placeholder(?string $key): bool
{
    return product_image_original($key) === PRODUCT_IMAGE_PLACEHOLDER;
}

function product_image_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Уменьшенные копии для телефонов: <slug>-600.webp и <slug>-900.webp. */
const PRODUCT_IMAGE_WIDTHS = [600, 900];

/**
 * srcset из подготовленных копий. Телефон скачивает вариант на 600 точек
 * (около 45 КБ) вместо полного файла на 1200 точек — страница каталога из
 * двенадцати карточек становится легче в несколько раз.
 */
function product_image_srcset(string $web): string
{
    $parts = [];
    foreach (PRODUCT_IMAGE_WIDTHS as $width) {
        $variant = preg_replace('~\.(webp|jpe?g|png)$~i', '-' . $width . '.webp', $web);
        if (is_string($variant) && product_image_exists($variant)) {
            $parts[] = $variant . ' ' . $width . 'w';
        }
    }

    if (!$parts) {
        return '';
    }

    $size = @getimagesize(product_image_root() . '/' . ltrim($web, '/'));
    if (is_array($size) && !empty($size[0])) {
        $parts[] = $web . ' ' . (int)$size[0] . 'w';
    }

    return implode(', ', $parts);
}

/**
 * Готовый тег <img>. Размеры проставляются из самого файла, чтобы браузер не
 * дёргал вёрстку при загрузке; если размеры прочитать не удалось, атрибуты
 * просто не выводятся и разметка остаётся прежней.
 *
 * data-full — путь к полноразмерному файлу: его открывает галерея увеличения,
 * чтобы на весь экран показывалась качественная версия, а не маленькая копия.
 */
function product_image_tag(?string $key, string $alt, bool $lazy = false, string $sizes = ''): string
{
    $src  = product_image_web($key);
    $html = '<img src="' . product_image_escape($src) . '" alt="' . product_image_escape($alt) . '"';

    $srcset = product_image_srcset($src);
    if ($srcset !== '') {
        $html .= ' srcset="' . product_image_escape($srcset) . '"';
        $html .= ' sizes="' . product_image_escape($sizes !== '' ? $sizes : '(max-width: 720px) 92vw, 320px') . '"';
    }

    $html .= ' data-full="' . product_image_escape(product_image_web($key)) . '"';

    $size = @getimagesize(product_image_root() . '/' . ltrim($src, '/'));
    if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
        $html .= ' width="' . (int)$size[0] . '" height="' . (int)$size[1] . '"';
    }

    // Главное фото первого экрана грузится сразу; всё, что ниже — лениво.
    $html .= $lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high" decoding="async"';

    return $html . ' />';
}

/* --------------------------------------------------------- диагностика */

/** Отчёт для /tools/check-images.php. */
function product_images_diagnostics(): array
{
    $report = [
        'dir'         => PRODUCT_IMAGES_DIR,
        'dir_exists'  => is_dir(product_image_root() . PRODUCT_IMAGES_DIR),
        'total'       => 0,
        'ok'          => 0,
        'missing'     => [],
        'no_webp'     => [],
        'external'    => [],
        'orphans'     => [],
        'rows'        => [],
    ];

    $expected = [];
    foreach (product_catalog() as $entry) {
        $slug = (string)($entry['slug'] ?? '');
        $raw  = (string)($entry['picture'] ?? '');
        $report['total']++;

        if (preg_match('~^(https?:)?//~i', $raw)) {
            $report['external'][] = $slug . ' — ' . $raw;
        }

        $original = product_image_original($slug);
        $web      = product_image_web($slug);
        $isPlaceholder = $original === PRODUCT_IMAGE_PLACEHOLDER;

        if ($isPlaceholder) {
            $report['missing'][] = $slug . ' — нужен файл ' . PRODUCT_IMAGES_DIR . '/' . $slug . '.jpg';
        } else {
            $report['ok']++;
            $expected[basename($original)] = true;
            if ($web === $original) {
                $report['no_webp'][] = $slug;
            } else {
                $expected[basename($web)] = true;
            }
        }

        $size = @getimagesize(product_image_root() . '/' . ltrim($original, '/'));
        $report['rows'][] = [
            'slug'        => $slug,
            'sku'         => (string)($entry['id'] ?? ''),
            'name'        => (string)($entry['name'] ?? ''),
            'original'    => $original,
            'web'         => $web,
            'placeholder' => $isPlaceholder,
            'external'    => (bool)preg_match('~^(https?:)?//~i', $raw),
            'size'        => is_array($size) ? $size[0] . '×' . $size[1] : '—',
            'bytes'       => @filesize(product_image_root() . '/' . ltrim($original, '/')) ?: 0,
        ];
    }

    // Лишние файлы в папке: не относятся ни к одному товару.
    $dir = product_image_root() . PRODUCT_IMAGES_DIR;
    foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $file) {
        $name = basename($file);
        // Уменьшенные копии (-600.webp, -900.webp) — часть комплекта, не мусор.
        $base = preg_replace('~-(\d+)\.webp$~i', '', $name);
        if (str_starts_with($name, 'placeholder.')
            || isset($expected[$name])
            || isset($expected[$base . '.webp'])
            || isset($expected[$base . '.jpg'])) {
            continue;
        }
        $report['orphans'][] = $name;
    }

    return $report;
}
