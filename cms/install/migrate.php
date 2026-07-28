<?php

/**
 * Перенос существующих данных сайта в базу CMS.
 *
 * Читает то, что сейчас разбросано по проекту, и складывает в одну базу:
 *
 *   yandexmarket/products.php   названия, артикулы, MPN, бренды, категории,
 *                               описания, характеристики, пути к фото
 *   data/prices.json            цены, старые цены, наличие, единицы, доставка
 *   products/<slug>/index.php   title, description, og:*, H1, хлебные крошки,
 *                               SEO-статья целиком, FAQ, блок «похожие товары»
 *   processors/, drives/        тексты категорий
 *   privacy_policy/, polzovatelskoe-soglashenie/   страницы
 *
 * Запуск из корня сайта:
 *     php cms/install/migrate.php            показать, что будет перенесено
 *     php cms/install/migrate.php --apply    перенести
 *     php cms/install/migrate.php --apply --fresh   очистить базу и перенести заново
 *
 * Безопасно запускать повторно: товары сопоставляются по slug и обновляются,
 * а не дублируются.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$apply = in_array('--apply', $argv ?? [], true);
$fresh = in_array('--fresh', $argv ?? [], true);

function say(string $line = ''): void
{
    echo $line, "\n";
}

/* ------------------------------------------------------- разбор старых файлов */

/** Достаёт из HTML страницы товара всё, чего нет в products.php. */
function migrate_parse_product_page(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $html = (string)file_get_contents($file);
    $out = [];

    $grab = static function (string $pattern) use ($html): ?string {
        return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : null;
    };

    $out['seo_title']  = $grab('~<title>(.*?)</title>~s');
    $out['seo_desc']   = $grab('~<meta name="description" content="(.*?)"~s');
    $out['og_title']   = $grab('~<meta property="og:title" content="(.*?)"~s');
    $out['og_desc']    = $grab('~<meta property="og:description" content="(.*?)"~s');
    $out['seo_h1']     = $grab('~<h1>(.*?)</h1>~s');
    // Видимая крошка и имя в JSON-LD на прежних страницах различались:
    // у процессоров в разметке стояла короткая модель, у дисков — полное
    // название. Берём именно значение из JSON-LD, чтобы не менять то, что
    // уже проиндексировано.
    $out['breadcrumb'] = null;
    if (preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $blocks)) {
        foreach ($blocks[1] as $block) {
            $data = json_decode($block, true);
            if (($data['@type'] ?? '') === 'BreadcrumbList') {
                $last = end($data['itemListElement']);
                $out['breadcrumb'] = $last['name'] ?? null;
            }
        }
    }
    $out['lead']       = $grab('~<p class="product-dialog-lead">(.*?)</p>~s');

    // SEO-статья: сохраняем ВНУТРЕННОСТЬ секции без заголовка и без кнопки
    // «Показать полностью» — обёртку соберёт шаблон, содержимое редактируется.
    if (preg_match('~<section class="cpu-article[^"]*"[^>]*>(.*?)</section>~s', $html, $m)) {
        $article = $m[1];
        $out['article_title'] = preg_match('~<h2[^>]*>(.*?)</h2>~s', $article, $h)
            ? trim(strip_tags($h[1]))
            : null;
        $article = preg_replace('~<h2[^>]*>.*?</h2>~s', '', $article);
        $article = preg_replace('~<button[^>]*data-article-toggle.*?</button>~s', '', (string)$article);
        // Внутренняя обёртка <div class="cpu-article-body"> тоже принадлежит
        // шаблону, а не тексту.
        if (preg_match('~<div class="cpu-article-body"[^>]*>(.*)</div>~s', (string)$article, $b)) {
            $article = $b[1];
        }
        $out['article_html'] = trim((string)$article);
    }

    // FAQ: пары «вопрос — ответ».
    $out['faq'] = [];
    if (preg_match_all('~<details class="faq-item">\s*<summary>(.*?)</summary>\s*<p>(.*?)</p>~s', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $pair) {
            $out['faq'][] = [
                'q' => trim(html_entity_decode(strip_tags($pair[1]), ENT_QUOTES, 'UTF-8')),
                'a' => trim(html_entity_decode(strip_tags($pair[2]), ENT_QUOTES, 'UTF-8')),
            ];
        }
    }

    // Похожие товары — по ссылкам блока.
    // Подписи в блоке «похожие» написаны вручную под каждую пару товаров
    // («Тот же Broadwell-E, но 18 ядер вместо 14») и не выводятся из описания
    // товара — их надо сохранить отдельно, иначе текст потеряется.
    $out['related'] = [];
    if (preg_match_all('~related-product-card" href="/products/([^/"]+)/">.*?<p>(.*?)</p>~s', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $pair) {
            $out['related'][$pair[1]] = trim(html_entity_decode(strip_tags($pair[2]), ENT_QUOTES, 'UTF-8'));
        }
    }

    return $out;
}

/** Тексты категории из processors/index.php или drives/index.php. */
function migrate_parse_category_page(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $html = (string)file_get_contents($file);
    $grab = static function (string $pattern) use ($html): ?string {
        return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : null;
    };

    return array_filter([
        'seo_title' => $grab('~<title>(.*?)</title>~s'),
        'seo_desc'  => $grab('~<meta name="description" content="(.*?)"~s'),
        'og_title'  => $grab('~<meta property="og:title" content="(.*?)"~s'),
        'og_desc'   => $grab('~<meta property="og:description" content="(.*?)"~s'),
        'h1'        => $grab('~<h1[^>]*>(.*?)</h1>~s'),
    ], static fn($v) => $v !== null && $v !== '');
}

/** Содержимое простой страницы (политика, соглашение). */
function migrate_parse_simple_page(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $html = (string)file_get_contents($file);
    $grab = static function (string $pattern) use ($html): ?string {
        return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : null;
    };

    $content = null;
    if (preg_match('~<main[^>]*>(.*?)</main>~s', $html, $m)) {
        $content = trim($m[1]);
    }

    return array_filter([
        'title'     => $grab('~<title>(.*?)</title>~s'),
        'seo_title' => $grab('~<title>(.*?)</title>~s'),
        'seo_desc'  => $grab('~<meta name="description" content="(.*?)"~s'),
        'h1'        => $grab('~<h1[^>]*>(.*?)</h1>~s'),
        'content'   => $content,
    ], static fn($v) => $v !== null && $v !== '');
}

/* ------------------------------------------------------------- подготовка */

$catalog = require CMS_ROOT . '/yandexmarket/products.php';
if (!is_array($catalog) || !$catalog) {
    exit("Не удалось прочитать yandexmarket/products.php\n");
}

$pricesRaw = json_decode((string)@file_get_contents(CMS_ROOT . '/data/prices.json'), true);
$prices = [];
foreach (($pricesRaw['товары'] ?? []) as $row) {
    $prices[mb_strtolower((string)($row['id'] ?? ''))] = $row;
}

$parsePrice = static function ($value): ?float {
    if (is_int($value) || is_float($value)) {
        return $value > 0 ? round((float)$value, 2) : null;
    }
    $text = preg_replace('~[\s\x{00A0}]+~u', '', (string)$value);
    $text = str_replace(',', '.', (string)$text);
    return preg_match('~^\d+(\.\d+)?$~', (string)$text) && (float)$text > 0 ? round((float)$text, 2) : null;
};

$stockCode = static function ($value): string {
    $t = mb_strtolower(trim((string)$value));
    if (str_contains($t, 'под заказ')) {
        return 'preorder';
    }
    if (str_contains($t, 'нет')) {
        return 'out_of_stock';
    }
    return 'in_stock';
};

say('=== ПЕРЕНОС ДАННЫХ В БАЗУ ' . ($apply ? '' : '(пробный запуск, база не меняется)'));
say('Драйвер базы: ' . (cms_config()['driver'] ?? 'mysql'));
say();

/* ---------------------------------------------------------- создание схемы */

if ($apply) {
    $driver = cms_config()['driver'] ?? 'mysql';

    if ($fresh) {
        say('Очистка базы (--fresh)...');
        foreach (array_reverse(array_keys(cms_schema())) as $table) {
            try {
                cms_db()->exec('DROP TABLE IF EXISTS ' . $table);
            } catch (Throwable $e) {
                say('  не удалось удалить ' . $table . ': ' . $e->getMessage());
            }
        }
    }

    foreach (cms_schema_sql($driver) as $query) {
        cms_db()->exec($query);
    }
    say('Схема создана: ' . count(cms_schema()) . ' таблиц.');
    say();
}

/* ------------------------------------------------------------- категории */

$categoryMap = [];   // category_id из products.php -> id в базе
$categoryDefs = [
    1 => ['name' => 'Серверные процессоры', 'slug' => 'processors', 'page' => CMS_ROOT . '/processors/index.php', 'yml' => 1],
    2 => ['name' => 'Диски и накопители',   'slug' => 'drives',     'page' => CMS_ROOT . '/drives/index.php',     'yml' => 2],
];

foreach ($categoryDefs as $oldId => $def) {
    $extra = migrate_parse_category_page($def['page']);
    say(sprintf('Категория: %-22s slug=%-11s %s', $def['name'], $def['slug'],
        $extra ? '(тексты со страницы перенесены)' : ''));

    if (!$apply) {
        continue;
    }

    $existing = cms_value('SELECT id FROM categories WHERE slug = ?', [$def['slug']]);
    $data = [
        'name'            => $def['name'],
        'slug'            => $def['slug'],
        'h1'              => $extra['h1'] ?? $def['name'],
        'seo_title'       => $extra['seo_title'] ?? null,
        'seo_desc'        => $extra['seo_desc'] ?? null,
        'og_title'        => $extra['og_title'] ?? null,
        'og_desc'         => $extra['og_desc'] ?? null,
        'yml_category_id' => $def['yml'],
        'sort_order'      => $oldId,
        'is_published'    => 1,
        'updated_at'      => cms_now(),
    ];

    if ($existing) {
        cms_update('categories', $data, 'id = :id', ['id' => $existing]);
        $categoryMap[$oldId] = (int)$existing;
    } else {
        $data['created_at'] = cms_now();
        $categoryMap[$oldId] = cms_insert('categories', $data);
    }
}
say();

/* ---------------------------------------------------- характеристики (словарь) */

$attributeMap = [];
$attrOrder = 0;
foreach ($catalog as $entry) {
    foreach (array_keys((array)($entry['params'] ?? [])) as $name) {
        $name = (string)$name;
        if (isset($attributeMap[$name])) {
            continue;
        }
        $attrOrder += 10;
        $code = mb_substr(preg_replace('~[^a-z0-9]+~', '_',
            mb_strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $name) ?: $name)), 0, 90);
        $code = trim((string)$code, '_') ?: 'attr_' . $attrOrder;

        if ($apply) {
            $existing = cms_value('SELECT id FROM attributes WHERE code = ?', [$code]);
            $attributeMap[$name] = $existing
                ? (int)$existing
                : cms_insert('attributes', [
                    'name' => $name, 'code' => $code, 'field_type' => 'text',
                    'is_visible' => 1, 'in_yml' => 1, 'sort_order' => $attrOrder,
                    'created_at' => cms_now(),
                ]);
        } else {
            $attributeMap[$name] = 0;
        }
    }
}
say('Характеристик в словаре: ' . count($attributeMap));
say();

/* ---------------------------------------------------------------- товары */

$productMap = [];
$relationsQueue = [];
$stats = ['products' => 0, 'images' => 0, 'attrs' => 0, 'faq' => 0, 'articles' => 0];

foreach ($catalog as $entry) {
    $slug = (string)($entry['slug'] ?? '');
    if ($slug === '') {
        continue;
    }

    $page = migrate_parse_product_page(CMS_ROOT . '/products/' . $slug . '/index.php');
    $price = $prices[mb_strtolower($slug)] ?? [];
    $params = (array)($entry['params'] ?? []);

    $articleLen = isset($page['article_html']) ? mb_strlen($page['article_html']) : 0;
    say(sprintf(
        '  %-16s цена %-9s характеристик %-3d FAQ %d  статья %s  похожих %d',
        $slug,
        $parsePrice($price['цена'] ?? null) !== null ? cms_money($parsePrice($price['цена'] ?? null)) : 'нет',
        count($params),
        count($page['faq'] ?? []),
        $articleLen ? $articleLen . ' симв.' : 'НЕТ',
        count($page['related'] ?? [])
    ));

    if ($articleLen) {
        $stats['articles']++;
    }
    $stats['faq'] += count($page['faq'] ?? []);
    $stats['attrs'] += count($params);

    if (!$apply) {
        continue;
    }

    $data = [
        'category_id'    => $categoryMap[(int)($entry['category_id'] ?? 1)] ?? null,
        'name'           => (string)$entry['name'],
        'short_name'     => trim((string)($entry['display_prefix'] ?? '') . ' ' . (string)($entry['model'] ?? '')),
        'model'          => (string)($entry['model'] ?? ''),
        'slug'           => $slug,
        'sku'            => (string)($entry['id'] ?? ''),
        'mpn'            => (string)($entry['mpn'] ?? ''),
        'brand'          => (string)($entry['brand'] ?? ''),
        'lead'           => $page['lead'] ?? (string)($entry['description'] ?? ''),
        'description'    => (string)($entry['description'] ?? ''),
        'article_title'  => $page['article_title'] ?? null,
        'article_html'   => $page['article_html'] ?? null,
        'price'          => $parsePrice($price['цена'] ?? null),
        'old_price'      => $parsePrice($price['старая_цена'] ?? null),
        'availability'   => $stockCode($price['наличие'] ?? 'в наличии'),
        'unit'           => (string)($price['единица'] ?? 'шт.'),
        'warranty'       => (string)($params['Гарантия'] ?? ''),
        'condition_note' => (string)($params['Комплектация'] ?? ''),
        'country'        => (string)($params['Страна-изготовитель'] ?? ''),
        'is_published'   => 1,
        'in_yml'         => 1,
        'seo_title'      => $page['seo_title'] ?? null,
        'seo_desc'       => $page['seo_desc'] ?? null,
        'seo_h1'         => $page['seo_h1'] ?? null,
        'og_title'       => $page['og_title'] ?? null,
        'og_desc'        => $page['og_desc'] ?? null,
        'breadcrumb'     => $page['breadcrumb'] ?? null,
        'canonical'      => '/products/' . $slug . '/',
        'sort_order'     => ++$stats['products'] * 10,
        'updated_at'     => cms_now(),
    ];

    $existing = cms_value('SELECT id FROM products WHERE slug = ?', [$slug]);
    if ($existing) {
        cms_update('products', $data, 'id = :id', ['id' => $existing]);
        $productId = (int)$existing;
    } else {
        $data['created_at'] = cms_now();
        $productId = cms_insert('products', $data);
    }
    $productMap[$slug] = $productId;

    // Характеристики
    cms_query('DELETE FROM product_attribute_values WHERE product_id = ?', [$productId]);
    $order = 0;
    foreach ($params as $name => $value) {
        if (!isset($attributeMap[(string)$name])) {
            continue;
        }
        cms_insert('product_attribute_values', [
            'product_id'   => $productId,
            'attribute_id' => $attributeMap[(string)$name],
            'value'        => (string)$value,
            'sort_order'   => $order += 10,
        ]);
    }

    // Фотография
    $picture = (string)($entry['picture'] ?? '');
    if ($picture !== '' && !preg_match('~^https?://~i', $picture)) {
        cms_query('DELETE FROM product_images WHERE product_id = ?', [$productId]);
        $abs = CMS_ROOT . '/' . ltrim($picture, '/');
        $size = is_file($abs) ? @getimagesize($abs) : false;
        cms_insert('product_images', [
            'product_id' => $productId,
            'path'       => $picture,
            'alt'        => (string)$entry['name'],
            'is_main'    => 1,
            'width'      => is_array($size) ? (int)$size[0] : null,
            'height'     => is_array($size) ? (int)$size[1] : null,
            'filesize'   => is_file($abs) ? (int)filesize($abs) : null,
            'sort_order' => 0,
            'created_at' => cms_now(),
        ]);
        $stats['images']++;
    }

    // FAQ
    cms_query('DELETE FROM product_faq WHERE product_id = ?', [$productId]);
    $order = 0;
    foreach (($page['faq'] ?? []) as $item) {
        cms_insert('product_faq', [
            'product_id' => $productId,
            'question'   => $item['q'],
            'answer'     => $item['a'],
            'sort_order' => $order += 10,
        ]);
    }

    if (!empty($page['related'])) {
        $relationsQueue[$slug] = $page['related'];
    }
}

/* --------------------------------------------------- связи «похожие товары» */

if ($apply) {
    foreach ($relationsQueue as $slug => $related) {
        if (!isset($productMap[$slug])) {
            continue;
        }
        cms_query('DELETE FROM product_relations WHERE product_id = ? AND relation = ?', [$productMap[$slug], 'similar']);
        $order = 0;
        foreach ($related as $target => $note) {
            if (!isset($productMap[$target])) {
                continue;
            }
            cms_insert('product_relations', [
                'product_id' => $productMap[$slug],
                'related_id' => $productMap[$target],
                'relation'   => 'similar',
                'note'       => $note !== '' ? $note : null,
                'sort_order' => $order += 10,
            ]);
        }
    }

    // Шаблон характеристик категории — из того, что реально заполнено у товаров.
    cms_query('DELETE FROM category_attributes', []);
    foreach (cms_all(
        'SELECT p.category_id, pav.attribute_id, MIN(pav.sort_order) AS so
           FROM product_attribute_values pav
           JOIN products p ON p.id = pav.product_id
          WHERE p.category_id IS NOT NULL
          GROUP BY p.category_id, pav.attribute_id'
    ) as $row) {
        cms_insert('category_attributes', [
            'category_id'  => (int)$row['category_id'],
            'attribute_id' => (int)$row['attribute_id'],
            'sort_order'   => (int)$row['so'],
        ]);
    }
}

say();
say(sprintf(
    'Товаров %d, характеристик %d, фотографий %d, вопросов FAQ %d, SEO-статей %d',
    $stats['products'] ?: count($catalog), $stats['attrs'], $stats['images'], $stats['faq'], $stats['articles']
));
say();

/* -------------------------------------------------------------- страницы */

$pageDefs = [
    ['slug' => 'privacy_policy',            'file' => CMS_ROOT . '/privacy_policy/index.html',            'title' => 'Политика конфиденциальности'],
    ['slug' => 'polzovatelskoe-soglashenie', 'file' => CMS_ROOT . '/polzovatelskoe-soglashenie/index.html', 'title' => 'Пользовательское соглашение'],
];

foreach ($pageDefs as $def) {
    $parsed = migrate_parse_simple_page($def['file']);
    say(sprintf('Страница: %-30s %s', '/' . $def['slug'] . '/',
        isset($parsed['content']) ? mb_strlen($parsed['content']) . ' симв.' : 'файл не найден'));

    if (!$apply || !$parsed) {
        continue;
    }

    $data = [
        'title'      => $parsed['title'] ?? $def['title'],
        'menu_title' => $def['title'],
        'slug'       => $def['slug'],
        'h1'        => $parsed['h1'] ?? $def['title'],
        'content'   => $parsed['content'] ?? null,
        'seo_title' => $parsed['seo_title'] ?? null,
        'seo_desc'  => $parsed['seo_desc'] ?? null,
        'status'    => 'published',
        'in_footer' => 1,
        'updated_at' => cms_now(),
    ];
    $existing = cms_value('SELECT id FROM pages WHERE slug = ?', [$def['slug']]);
    if ($existing) {
        cms_update('pages', $data, 'id = :id', ['id' => $existing]);
    } else {
        $data['created_at'] = cms_now();
        cms_insert('pages', $data);
    }
}
say();

/* -------------------------------------------------------------- доставка */

$deliveryDefs = [
    ['code' => 'pickup',  'title' => 'Самовывоз',                      'key' => 'самовывоз',          'city' => 0, 'addr' => 0,
     'desc' => 'Москва, м. Варшавская, Болотниковская ул., д.5к3 · бесплатно'],
    ['code' => 'moscow',  'title' => 'Доставка по Москве',             'key' => 'москва',             'city' => 1, 'addr' => 1,
     'desc' => 'Курьерская доставка по адресу'],
    ['code' => 'mo',      'title' => 'Доставка по Московской области', 'key' => 'московская_область', 'city' => 1, 'addr' => 1,
     'desc' => 'Курьер или транспортная компания'],
    ['code' => 'russia',  'title' => 'Доставка по России',             'key' => 'россия',             'city' => 1, 'addr' => 1,
     'desc' => 'СДЭК, транспортная компания, Яндекс Маркет, Ozon или Wildberries'],
];

$order = 0;
foreach ($deliveryDefs as $def) {
    $raw = $pricesRaw['доставка'][$def['key']] ?? null;
    $price = ($raw === '' || $raw === null) ? null : (float)$raw;
    say(sprintf('Доставка: %-32s %s', $def['title'], $price === null ? 'по тарифам службы' : cms_money($price)));

    if (!$apply) {
        continue;
    }

    $data = [
        'code' => $def['code'], 'title' => $def['title'], 'description' => $def['desc'],
        'price' => $price, 'needs_city' => $def['city'], 'needs_address' => $def['addr'],
        'is_active' => 1, 'sort_order' => $order += 10,
    ];
    $existing = cms_value('SELECT id FROM delivery_methods WHERE code = ?', [$def['code']]);
    if ($existing) {
        cms_update('delivery_methods', $data, 'id = :id', ['id' => $existing]);
    } else {
        cms_insert('delivery_methods', $data);
    }
}
say();

/* -------------------------------------------------------------- настройки */

if ($apply) {
    $defaults = [
        ['site', 'base_url', 'https://comp-uter.ru'],
        ['site', 'name', 'Comp-Uter'],
        ['site', 'legal_name', 'ИП Михайловский Виталий Геннадьевич'],
        ['contacts', 'phone', '+7 (499) 322-13-11'],
        ['contacts', 'phone_raw', '+74993221311'],
        ['contacts', 'email', 'info@comp-uter.ru'],
        ['contacts', 'address', 'Болотниковская ул., д.5к3, Москва, 117556'],
        ['contacts', 'hours', 'Понедельник-пятница, с 9:00 до 18:00'],
        ['seo', 'yandex_verification', '624ceca67ba02e2a'],
        ['seo', 'metrika_id', '110948351'],
        ['yml', 'shop_name', 'Comp-Uter'],
        ['yml', 'utm', 'utm_source=yandexmarket&utm_medium=cpc&utm_campaign=xeon_feed'],
        ['yml', 'currency', 'RUR'],
    ];
    foreach ($defaults as [$g, $k, $v]) {
        if (cms_value('SELECT id FROM settings WHERE group_code = ? AND key_code = ?', [$g, $k]) === null) {
            cms_setting_save($g, $k, $v);
        }
    }
    say('Настройки сайта, контактов, SEO и фида записаны.');
}

say();
say('---------------------------------------------------------------');
if (!$apply) {
    say('Это был пробный запуск. Чтобы перенести данные:');
    say('    php cms/install/migrate.php --apply');
    exit(0);
}

say('Перенос завершён.');
say('Проверьте результат:  php cms/install/verify.php');
