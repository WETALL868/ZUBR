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
require_once __DIR__ . '/defaults.php';

/**
 * Путь к исходному файлу.
 *
 * Сначала ищем в снимке cms/install/legacy — он не меняется при установке
 * CMS. Если снимка нет, берём файл с сайта: так миграцию можно запустить и
 * до перехода, пока прежние страницы ещё на месте.
 */
function legacy(string $snapshot, string $live): string
{
    $path = __DIR__ . '/legacy/' . $snapshot;
    return is_file($path) ? $path : CMS_ROOT . '/' . ltrim($live, '/');
}

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

/**
 * Сокращённые характеристики карточек каталога и подписи к фото — они есть
 * только в разметке главной и страниц категорий.
 *
 * @return array<string,array{specs:array<string,string>,alt:string,figure:string,
 *                            tags:string[],lead:string,title:string,featured:bool}>
 */
function migrate_parse_cards(array $files): array
{
    $cards = [];

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $html = (string)file_get_contents($file);
        if (!preg_match_all('~<article class="model-card([^"]*)" id="([^"]+)"(.*?)</article>~s', $html, $matches, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($matches as $card) {
            $slug = $card[2];
            if (isset($cards[$slug])) {
                continue;   // одна и та же карточка есть и на главной, и в категории
            }
            $body = $card[3];

            $specs = [];
            if (preg_match_all('~<div><dt>(.*?)</dt><dd>(.*?)</dd></div>~s', $body, $rows, PREG_SET_ORDER)) {
                foreach ($rows as $row) {
                    $specs[trim(html_entity_decode(strip_tags($row[1]), ENT_QUOTES, 'UTF-8'))]
                        = trim(html_entity_decode(strip_tags($row[2]), ENT_QUOTES, 'UTF-8'));
                }
            }

            $tags = [];
            if (preg_match('~<ul class="model-tags"[^>]*>(.*?)</ul>~s', $body, $ul)
                && preg_match_all('~<li>(.*?)</li>~s', $ul[1], $li)) {
                $tags = array_map(static fn($t) => trim(html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8')), $li[1]);
            }

            $cards[$slug] = [
                'specs'    => $specs,
                'tags'     => $tags,
                'featured' => str_contains($card[1], 'featured'),
                'figure'   => preg_match('~<figure class="model-photo"[^>]*aria-label="([^"]*)"~', $body, $f) ? $f[1] : '',
                'alt'      => preg_match("~product_image_tag\\('[^']*', '([^']*)'~", $body, $a) ? $a[1] : '',
                'title'    => preg_match('~<h3>(.*?)</h3>~s', $body, $h) ? trim(strip_tags($h[1])) : '',
                'lead'     => preg_match('~</dl>|<p>(?!<)(.*?)</p>~s', $body, $p) ? '' : '',
            ];
            // Краткий текст карточки — абзац между ценой и списком характеристик.
            if (preg_match('~<\\?= prices_card_html\\([^)]*\\) \\?>\\s*<p>(.*?)</p>~s', $body, $lead)) {
                $cards[$slug]['lead'] = trim(html_entity_decode(strip_tags($lead[1]), ENT_QUOTES, 'UTF-8'));
            }
        }
    }

    return $cards;
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

    // Вводный текст под H1 и подписи секций — их видно только в разметке.
    $lead = null;
    if (preg_match('~<h1[^>]*>.*?</h1>\\s*<p>(.*?)</p>~s', $html, $m)) {
        // Пробелы не трогаем: абзац в разметке разбит на строки, и схлопывание
        // дало бы расхождение с прежней страницей.
        $lead = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }

    return array_filter([
        'seo_title'   => $grab('~<title>(.*?)</title>~s'),
        'seo_desc'    => $grab('~<meta name="description" content="(.*?)"~s'),
        'og_title'    => $grab('~<meta property="og:title" content="(.*?)"~s'),
        'og_desc'     => $grab('~<meta property="og:description" content="(.*?)"~s'),
        'h1'          => $grab('~<h1[^>]*>(.*?)</h1>~s'),
        'lead'        => $lead,
        'label'       => $grab('~<section class="section category-intro">\\s*<p class="section-label">(.*?)</p>~s'),
        'stock_title' => $grab('~<h2 id="cat-grid-title">(.*?)</h2>~s'),
        // ItemList в микроразметке категории имел свои name и description,
        // не совпадающие с названием категории.
        'list_name'   => $grab('~"@type":"ItemList","name":"(.*?)"~s'),
        'list_desc'   => $grab('~"@type":"ItemList","name":".*?","description":"(.*?)"~s'),
        // Второй абзац вступления — про подбор оборудования.
        'selection_text' => preg_match('~<h1[^>]*>.*?</h1>\\s*<p>.*?</p>\\s*<p>(.*?)</p>~s', $html, $m2)
            ? html_entity_decode($m2[1], ENT_QUOTES, 'UTF-8') : null,
        'request_goal'   => $grab('~request-link[^>]*data-goal="([^"]*)"~'),
        'grid_anchor'    => $grab('~<section class="section inventory" id="([^"]*)"~'),
        'request_badge'  => $grab('~<article class="model-card request-card">\\s*<div class="chip-sketch[^"]*"[^>]*><span>(.*?)</span>~s'),
        'request_status' => $grab('~<article class="model-card request-card">.*?</div>\\s*<span>(.*?)</span>~s'),
        'request_tags'   => preg_match('~<article class="model-card request-card">.*?<ul class="model-tags">(.*?)</ul>~s', $html, $rt)
            && preg_match_all('~<li>(.*?)</li>~s', $rt[1], $rl)
            ? implode(', ', array_map(static fn($t) => trim(strip_tags($t)), $rl[1])) : null,
        'request_title'  => $grab('~<article class="model-card request-card">.*?<h3>(.*?)</h3>~s'),
        'request_text'   => $grab('~<article class="model-card request-card">.*?<h3>.*?</h3>\\s*<p>(.*?)</p>~s'),
        'request_button' => $grab('~<a class="model-detail-button request-link"[^>]*>(.*?)</a>~s'),
        'footer_text'    => $grab('~<div class="footer-brand">.*?</a>\\s*<p>(.*?)</p>~s'),
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
        'seo_desc'  => $grab('~<meta\s+name="description"\s+content="(.*?)"~s'),
        'og_title'  => $grab('~<meta property="og:title" content="(.*?)"~s'),
        'og_desc'   => $grab('~<meta property="og:description" content="(.*?)"~s'),
        'h1'        => $grab('~<h1[^>]*>(.*?)</h1>~s'),
        // Каждая правовая страница заранее подгружает свой фон.
        'preload_image' => $grab('~<link rel="preload" as="image" href="(.*?)"~s'),
        'content'   => $content,
    ], static fn($v) => $v !== null && $v !== '');
}

/* ------------------------------------------------------------- подготовка */

$cards = migrate_parse_cards([
    legacy('home.php', 'index.php'),
    legacy('category-processors.php', 'processors/index.php'),
    legacy('category-drives.php', 'drives/index.php'),
]);

$catalog = require legacy('products.php', 'yandexmarket/products.php');
if (!is_array($catalog) || !$catalog) {
    exit("Не удалось прочитать yandexmarket/products.php\n");
}

$pricesRaw = json_decode((string)@file_get_contents(legacy('prices.json', 'data/prices.json')), true);
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

    $applied = cms_schema_apply(cms_db(), $driver);

    // Столбцы, появившиеся в схеме после того, как база была создана.
    // CREATE TABLE IF NOT EXISTS их не добавит: таблица-то уже есть.
    $addedColumns = cms_schema_sync_columns(cms_db(), $driver);

    say('Схема готова: ' . count(cms_schema()) . ' таблиц'
        . ($applied['skipped'] > 0 ? ' (уже существовало объектов: ' . $applied['skipped'] . ')' : '') . '.');
    if ($addedColumns) {
        say('Добавлены столбцы: ' . implode(', ', $addedColumns) . '.');
    }
    say();
}

/* ------------------------------------------------------------- категории */

$categoryMap = [];   // category_id из products.php -> id в базе
$categoryDefs = [
    1 => ['name' => 'Серверные процессоры', 'slug' => 'processors', 'page' => legacy('category-processors.php', 'processors/index.php'), 'yml' => 1, 'yml_name' => 'Процессоры Intel Xeon'],
    2 => ['name' => 'Диски и накопители',   'slug' => 'drives',     'page' => legacy('category-drives.php', 'drives/index.php'),     'yml' => 2, 'yml_name' => 'Жесткие диски Seagate'],
];

// Блок «другая категория» на странице A описывает категорию B — значит,
// текст для B нужно брать со страницы A.
$crossTexts = [];
foreach ($categoryDefs as $def) {
    $html = (string)@file_get_contents($def['page']);
    if (preg_match_all('~<article class="category-card">\s*<p class="section-label">Другая категория</p>\s*<h2>(.*?)</h2>\s*<p>(.*?)</p>\s*<a[^>]*href="/([^/"]+)/"[^>]*>(.*?)</a>~s', $html, $cm, PREG_SET_ORDER)) {
        foreach ($cm as $c) {
            $crossTexts[$c[3]] = [
                'text'   => trim(html_entity_decode(strip_tags($c[2]), ENT_QUOTES, 'UTF-8')),
                'button' => trim(html_entity_decode(strip_tags($c[4]), ENT_QUOTES, 'UTF-8')),
            ];
        }
    }
}

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
        'lead'            => $extra['lead'] ?? null,
        'label'           => $extra['label'] ?? null,
        'stock_label'     => $extra['stock_label'] ?? null,
        'stock_title'     => $extra['stock_title'] ?? null,
        'list_name'       => $extra['list_name'] ?? null,
        'list_desc'       => $extra['list_desc'] ?? null,
        'selection_text'  => $extra['selection_text'] ?? null,
        'request_goal'    => $extra['request_goal'] ?? null,
        'grid_anchor'     => $extra['grid_anchor'] ?? 'stock',
        'request_badge'   => $extra['request_badge'] ?? null,
        'request_status'  => $extra['request_status'] ?? null,
        'request_tags'    => $extra['request_tags'] ?? null,
        'request_title'   => $extra['request_title'] ?? null,
        'request_text'    => $extra['request_text'] ?? null,
        'request_button'  => $extra['request_button'] ?? null,
        'footer_text'     => $extra['footer_text'] ?? null,
        'cross_text'      => $crossTexts[$def['slug']]['text'] ?? null,
        'cross_button'    => $crossTexts[$def['slug']]['button'] ?? null,
        'seo_title'       => $extra['seo_title'] ?? null,
        'seo_desc'        => $extra['seo_desc'] ?? null,
        'og_title'        => $extra['og_title'] ?? null,
        'og_desc'         => $extra['og_desc'] ?? null,
        'yml_category_id' => $def['yml'],
        'yml_name'        => $def['yml_name'],
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

    $page = migrate_parse_product_page(legacy('products/' . $slug . '.php', 'products/' . $slug . '/index.php'));
    $card = $cards[$slug] ?? ['specs' => [], 'tags' => [], 'featured' => false, 'figure' => '', 'alt' => '', 'title' => '', 'lead' => ''];
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
        'is_featured'    => $card['featured'] ? 1 : 0,
        'card_title'     => $card['title'] ?: null,
        'card_lead'      => $card['lead'] ?: null,
        'card_alt'       => $card['alt'] ?: null,
        'card_figure_label' => $card['figure'] ?: null,
        'card_tags'      => $card['tags'] ? implode(', ', $card['tags']) : null,
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

    // Сокращённые характеристики карточки каталога
    cms_query('DELETE FROM product_card_specs WHERE product_id = ?', [$productId]);
    $order = 0;
    foreach ($card['specs'] as $name => $value) {
        cms_insert('product_card_specs', [
            'product_id' => $productId,
            'name'       => (string)$name,
            'value'      => (string)$value,
            'sort_order' => $order += 10,
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
    ['slug' => 'privacy_policy',            'file' => legacy('page-privacy_policy.html', 'privacy_policy/index.html'),            'title' => 'Политика конфиденциальности', 'sort' => 10],
    ['slug' => 'polzovatelskoe-soglashenie', 'file' => legacy('page-polzovatelskoe-soglashenie.html', 'polzovatelskoe-soglashenie/index.html'), 'title' => 'Пользовательское соглашение', 'sort' => 20],
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
        'template'  => 'legal',
        'seo_title' => $parsed['seo_title'] ?? null,
        'seo_desc'  => $parsed['seo_desc'] ?? null,
        'og_title'  => $parsed['og_title'] ?? null,
        'og_desc'   => $parsed['og_desc'] ?? null,
        'preload_image' => $parsed['preload_image'] ?? null,
        'status'    => 'published',
        'in_footer' => 1,
        'sort_order' => $def['sort'],
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

// Способы доставки: названия и подписи берём из разметки корзины, цены — из
// prices.json. В исходном файле цена подставлялась PHP-вставкой, поэтому из
// разметки достаём только неизменяемый текст («Курьер или транспортная
// компания · от»), а число подставляем отдельно.
$deliveryDefs = [];
$cartHtml = (string)@file_get_contents(legacy('category-processors.php', 'processors/index.php'));
if (preg_match_all(
    '~<input type="radio" name="cartDelivery" value="([^"]*)"[^>]*?data-delivery-price="([^"]*)"'
    . '[^>]*?data-delivery-title="([^"]*)"([^>]*)>\s*<span>\s*<strong>(.*?)</strong>\s*<em>(.*?)</em>~s',
    $cartHtml, $matches, PREG_SET_ORDER
)) {
    $codes = ['Самовывоз' => 'pickup', 'Доставка по Москве' => 'moscow',
              'Доставка по Московской области' => 'mo', 'Доставка по России' => 'russia'];
    $priceKeys = ['pickup' => 'самовывоз', 'moscow' => 'москва',
                  'mo' => 'московская_область', 'russia' => 'россия'];

    // Убирает PHP-вставки и приводит пробелы в порядок.
    $plain = static function (string $value): string {
        $value = preg_replace('~<\?.*?\?>~s', '', $value) ?? '';
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8')) ?? '');
    };

    // То же, но вставка с ценой превращается в метку {цена}. Строка
    // «Курьер или транспортная компания · от {цена}» сохраняет и слово «от»,
    // и место для стоимости — иначе при сборке из кусков терялось то одно,
    // то другое.
    $withPrice = static function (string $value): string {
        $value = preg_replace('~<\?[^?]*prices_delivery_money[^?]*\?>~s', '{цена}', $value) ?? '';
        $value = preg_replace('~<\?.*?\?>~s', '', $value) ?? '';
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8')) ?? '');
    };

    foreach ($matches as $m) {
        $code = $codes[$m[1]] ?? mb_substr(preg_replace('~[^a-z0-9]+~', '_', mb_strtolower($m[1])), 0, 50);
        $raw = $pricesRaw['доставка'][$priceKeys[$code] ?? ''] ?? null;

        $deliveryDefs[] = [
            'code'  => $code,
            'title' => $plain($m[5]),
            'desc'  => $withPrice($m[6]),
            'opt'   => $plain($m[3]),
            'price' => ($raw === '' || $raw === null) ? null : (float)$raw,
            'addr'  => str_contains($m[4], 'data-delivery-details') ? 1 : 0,
        ];
    }
}

$order = 0;
foreach ($deliveryDefs as $def) {
    $price = $def['price'];
    say(sprintf('Доставка: %-32s %-18s «%s»', $def['title'],
        $price === null ? 'по тарифам службы' : cms_money($price), $def['desc']));

    if (!$apply) {
        continue;
    }

    $data = [
        'code' => $def['code'], 'title' => $def['title'], 'description' => $def['desc'],
        'option_title' => $def['opt'],
        'price' => $price, 'needs_city' => $def['addr'], 'needs_address' => $def['addr'],
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

/* ---------------------------------------------------------------- оплата */

if ($apply) {
    $addedPayments = cms_seed_payment_methods();
    say($addedPayments
        ? 'Способы оплаты добавлены: ' . implode(', ', $addedPayments) . '.'
        : 'Способы оплаты уже настроены — не трогаем.');
} else {
    foreach (cms_default_payment_methods() as $method) {
        say(sprintf('Оплата: %-38s (%s)', $method['title'], $method['audience']));
    }
}
say();

/* ------------------------------------------------------------- главная */

// Главная — не набор товаров, а посадочная страница из семнадцати непохожих
// друг на друга секций. Втискивать их в общий шаблон «заголовок + текст»
// значило бы потерять вёрстку, поэтому каждая секция переносится в
// home_blocks как есть, а витрины процессоров и дисков разрезаются на
// «до сетки» и «после сетки»: сами карточки собираются из базы.

$homeFile = legacy('home.php', 'index.php');
$homeHtml = (string)@file_get_contents($homeFile);

if ($homeHtml === '') {
    say('Главная не найдена: ' . $homeFile);
} else {
    $meta = static function (string $pattern) use ($homeHtml): ?string {
        return preg_match($pattern, $homeHtml, $m)
            ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'))
            : null;
    };

    $homePage = [
        'title'      => 'Главная',
        'menu_title' => 'Главная',
        'slug'       => '',
        'template'   => 'home',
        'seo_title'  => $meta('~<title>(.*?)</title>~s'),
        'seo_desc'   => $meta('~<meta\s+name="description"\s+content="(.*?)"~s'),
        'keywords'   => $meta('~<meta\s+name="keywords"\s+content="(.*?)"~s'),
        'og_title'   => $meta('~<meta property="og:title" content="(.*?)"~s'),
        'og_desc'    => $meta('~<meta\s+property="og:description"\s+content="(.*?)"~s'),
        'og_image'   => $meta('~<meta property="og:image" content="(.*?)"~s'),
        'tw_title'   => $meta('~<meta name="twitter:title" content="(.*?)"~s'),
        'tw_desc'    => $meta('~<meta\s+name="twitter:description"\s+content="(.*?)"~s'),
        'canonical'  => $meta('~<link rel="canonical" href="(.*?)"~s'),
        'status'     => 'published',
        'in_footer'  => 0,
        'sort_order' => 0,
        'updated_at' => cms_now(),
    ];
    say(sprintf('Главная: title «%s», описание %d симв., keywords %d симв.',
        mb_substr((string)$homePage['seo_title'], 0, 48),
        mb_strlen((string)$homePage['seo_desc']), mb_strlen((string)$homePage['keywords'])));

    // Секции.
    $homeBlocks = [];
    if (preg_match('~<main id="top">(.*)\n    </main>~s', $homeHtml, $m)) {
        $sections = preg_split('~(?m)^(?=      <section )~', $m[1]);
        array_shift($sections);   // перевод строки перед первой секцией

        // Человеческие коды и подписи: по ним секция и будет искаться в
        // административной панели.
        $names = [
            'hero'       => 'Первый экран',
            'trust'      => 'Полоса преимуществ',
            'categories' => 'Две категории каталога',
            'stock'      => 'Витрина процессоров',
            'hdd'        => 'Витрина дисков',
            'seo'        => 'Текст о каталоге',
            'selection'  => 'Помощь в подборе',
            'about'      => 'О компании',
            'testing'    => 'Проверка совместимости',
            'kits'       => 'Готовые сценарии',
            'corporate'  => 'Для организаций',
            'delivery'   => 'Доставка',
            'contact'    => 'Адрес и карта',
            'faq'        => 'Частые вопросы',
            'order'      => 'Форма заказа',
            'requisites' => 'Реквизиты',
            'privacy'    => 'Правовые страницы',
        ];
        // Код секции берём из её id; у трёх секций без id — из класса.
        $byClass = [
            'hero'           => 'hero',
            'trust-strip'    => 'trust',
            'category-split' => 'categories',
            'seo-panel'      => 'seo',
            'kits'           => 'kits',
        ];
        $codeFor = static function (string $section) use ($byClass): string {
            if (preg_match('~<section[^>]*\sid="([^"]+)"~', $section, $mm)) {
                return $mm[1];
            }
            if (preg_match('~<section class="(?:section )?([a-z0-9-]+)~', $section, $mm)) {
                return $byClass[$mm[1]] ?? str_replace('-', '_', $mm[1]);
            }
            return 'block';
        };

        $sortOrder = 0;
        foreach ($sections as $section) {
            $code = $codeFor($section);
            $sortOrder += 10;

            // Витрина: до сетки, карточки из базы, после сетки.
            $gridOpen = "\n        <div class=\"model-grid\">\n";
            $requestAt = mb_strpos($section, '          <article class="model-card request-card">');
            $gridAt = mb_strpos($section, $gridOpen);

            if ($gridAt !== false && $requestAt !== false && ($code === 'stock' || $code === 'hdd')) {
                $homeBlocks[] = [
                    'code'       => $code,
                    'kind'       => 'products',
                    'title'      => $names[$code] ?? $code,
                    'body'       => mb_substr($section, 0, $gridAt + mb_strlen($gridOpen)),
                    'body_after' => mb_substr($section, $requestAt),
                    'settings'   => json_encode(
                        ['category' => $code === 'stock' ? 'processors' : 'drives'],
                        JSON_UNESCAPED_UNICODE
                    ),
                    'sort_order' => $sortOrder,
                ];
                continue;
            }

            /*
             * Секция заказа: текст до формы, форма из файла, закрывающий тег.
             *
             * Форма разбита на поля с проверками и переключателем «физическое
             * лицо / юридическое лицо», её собирает templates/partials/
             * order-form.php. В базе остаётся только текст вокруг неё —
             * заголовок и абзац, которые владелец правит в панели.
             */
            if ($code === 'order') {
                $intro = preg_match('~<div class="order-copy">.*?</div>\s*</div>~s', $section, $im)
                    ? $im[0]
                    : '';
                $markup = cms_order_block_markup($intro);

                $homeBlocks[] = [
                    'code'       => 'order',
                    'kind'       => 'form',
                    'title'      => $names['order'] ?? 'Оформление заказа',
                    'body'       => $markup['body'],
                    'body_after' => $markup['body_after'],
                    'settings'   => null,
                    'sort_order' => $sortOrder,
                ];
                continue;
            }

            $homeBlocks[] = [
                'code'       => $code,
                'kind'       => 'html',
                'title'      => $names[$code] ?? $code,
                'body'       => $section,
                'body_after' => null,
                'settings'   => null,
                'sort_order' => $sortOrder,
            ];
        }
    }

    foreach ($homeBlocks as $block) {
        say(sprintf('  блок %-12s %-9s %5d симв.%s', $block['code'], $block['kind'],
            mb_strlen((string)$block['body']),
            $block['body_after'] !== null ? ' + ' . mb_strlen($block['body_after']) . ' после витрины' : ''));
    }

    // Данные организации для микроразметки LocalBusiness.
    $business = [];
    if (preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $homeHtml, $ld)) {
        foreach ($ld[1] as $raw) {
            $decoded = json_decode(trim($raw), true);
            if (!is_array($decoded)) {
                continue;
            }
            if (($decoded['@type'] ?? '') === 'LocalBusiness') {
                $business = $decoded;
            }
            if (($decoded['@type'] ?? '') === 'ItemList') {
                $homeList = ['name' => $decoded['name'] ?? '', 'description' => $decoded['description'] ?? ''];
            }
        }
    }

    if ($apply) {
        $existing = cms_value('SELECT id FROM pages WHERE slug = ?', ['']);
        if ($existing) {
            cms_update('pages', $homePage, 'id = :id', ['id' => $existing]);
        } else {
            $homePage['created_at'] = cms_now();
            cms_insert('pages', $homePage);
        }

        foreach ($homeBlocks as $block) {
            $block['is_enabled'] = 1;
            $block['updated_at'] = cms_now();
            $was = cms_value('SELECT id FROM home_blocks WHERE code = ?', [$block['code']]);
            if ($was) {
                cms_update('home_blocks', $block, 'id = :id', ['id' => $was]);
            } else {
                cms_insert('home_blocks', $block);
            }
        }

        if ($business) {
            $address = $business['address'] ?? [];
            foreach ([
                'legal_name'   => $business['legalName'] ?? '',
                'description'  => $business['description'] ?? '',
                'tax_id'       => $business['taxID'] ?? '',
                'street'       => $address['streetAddress'] ?? '',
                'locality'     => $address['addressLocality'] ?? '',
                'postal_code'  => $address['postalCode'] ?? '',
                'country'      => $address['addressCountry'] ?? 'RU',
                'opening_hours' => $business['openingHours'] ?? '',
            ] as $key => $value) {
                if ($value !== '') {
                    cms_setting_save('business', $key, (string)$value);
                }
            }
        }
        if (!empty($homeList)) {
            cms_setting_save('home', 'list_name', (string)$homeList['name']);
            cms_setting_save('home', 'list_desc', (string)$homeList['description']);
        }

        // Подпись под логотипом в подвале главной отличается от той, что
        // стоит на страницах товаров, — сохраняем обе.
        if (preg_match('~<div class="footer-brand">.*?</a>\s*<p>(.*?)</p>~s', $homeHtml, $fm)) {
            cms_setting_save('home', 'footer_text',
                trim(preg_replace('~\s+~u', ' ', html_entity_decode($fm[1], ENT_QUOTES, 'UTF-8')) ?? ''));
        }
        say('Главная перенесена: ' . count($homeBlocks) . ' блок(ов), данные организации и список товаров.');
    }
}
say();

/* -------------------------------------------------------------- настройки */

if ($apply) {
    $defaults = [
        ['site', 'base_url', 'https://comp-uter.ru'],
        ['site', 'name', 'Comp-Uter'],
        ['site', 'legal_name', 'ИП Михайловский Виталий Геннадьевич'],
        ['site', 'legal_short', 'ИП Михайловский В.Г.'],
        ['site', 'footer_text', 'Процессоры Intel Xeon и жесткие диски Seagate для серверных платформ, X99, рабочих станций, домашних сборок и корпоративных закупок.'],
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
        ['cookie', 'title', 'Cookie и персональные данные'],
        ['cookie', 'text', "Мы используем cookie для работы сайта и обработки заявок. Нажимая «Согласен», вы подтверждаете согласие\n          с использованием cookie и можете ознакомиться с <a href=\"/privacy_policy/\">политикой конфиденциальности</a>."],
        ['cookie', 'button', 'Согласен'],
        ['yml', 'cache_ttl_seconds', '60'],
        ['yml', 'default_country', 'Малайзия'],
        ['yml', 'sales_notes', 'Доставка по России: СДЭК, Яндекс Маркет, Ozon, Wildberries и другими транспортными компаниями; условия уточняются у менеджера.'],
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
say('Создайте себе вход:   php cms/install/create-admin.php');
say('Затем откройте панель управления и загляните в раздел «Обслуживание»:');
say('там перечислено всё, что стоит проверить после установки.');
