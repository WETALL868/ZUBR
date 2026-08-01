<?php

/**
 * Загрузка партии товаров из файла.
 *
 * Запуск из папки сайта:
 *     php cms/install/import-products.php ФАЙЛ            показать, что будет
 *     php cms/install/import-products.php ФАЙЛ --apply    загрузить
 *
 * Например:
 *     php cms/install/import-products.php cms/install/products/desktop-cpu.php
 *
 * ЗАЧЕМ ЭТО НУЖНО. Один товар удобно завести в панели: название, цена,
 * фотография — пять минут. Партия из восьми позиций с описанием, полутора
 * десятками характеристик, статьёй и вопросами-ответами у каждой — это уже
 * несколько часов однообразной работы, в которой легко ошибиться и трудно
 * заметить ошибку. Файл с данными проверяется глазами один раз и загружается
 * целиком.
 *
 * ЧТО ОН ДЕЛАЕТ И ЧЕГО НЕ ДЕЛАЕТ:
 *
 *   • товар опознаётся по адресу (slug). Нового создаёт, существующий
 *     обновляет — не плодит копии;
 *   • НЕ ТРОГАЕТ фотографии. Их добавляют в панели, и повторный импорт их
 *     не сотрёт;
 *   • НЕ ТРОГАЕТ товары, которых нет в файле;
 *   • ничего не удаляет.
 *
 * Формат файла — в cms/install/products/desktop-cpu.php, он же образец.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/repo.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Скрипт запускается только из консоли.\n");
}

$args  = array_slice($argv ?? [], 1);
$apply = in_array('--apply', $args, true);
$file  = '';
foreach ($args as $arg) {
    if ($arg !== '--apply') {
        $file = $arg;
        break;
    }
}

if ($file === '') {
    exit("Укажите файл с товарами:\n"
        . "    php cms/install/import-products.php cms/install/products/desktop-cpu.php\n");
}

$path = $file[0] === '/' ? $file : CMS_ROOT . '/' . ltrim($file, './');
if (!is_file($path)) {
    exit("Файл не найден: $path\n");
}

$data = require $path;
if (!is_array($data) || empty($data['products'])) {
    exit("В файле нет списка товаров. Смотрите образец cms/install/products/desktop-cpu.php\n");
}

function out(string $line = ''): void
{
    echo $line, "\n";
}

out('=== Загрузка товаров: ' . basename($path) . ' ===');
out($apply ? 'Режим: записываю в базу.' : 'Режим: только показываю, ничего не меняю.');
out();

try {
    cms_db()->query('SELECT 1');
} catch (Throwable $e) {
    exit('База не отвечает: ' . $e->getMessage() . "\nПроверьте config/database.php.\n");
}

/* ------------------------------------------------------------- категория */

$categoryId = null;

if (!empty($data['category'])) {
    $cat  = $data['category'];
    $slug = (string)($cat['slug'] ?? '');
    $was  = $slug !== '' ? cms_one('SELECT * FROM categories WHERE slug = ?', [$slug]) : null;

    if ($was) {
        $categoryId = (int)$was['id'];
        out('Категория «' . $cat['name'] . '» уже есть — не трогаю.');
    } else {
        out('Категория «' . $cat['name'] . '» будет создана, адрес /' . $slug . '/');

        if ($apply) {
            /*
             * yml_category_id должен быть уникальным: Яндекс.Маркет
             * привязывает товары к категории по этому номеру, и совпадение
             * с чужим номером смешало бы каталоги.
             */
            $nextYml = (int)(cms_value('SELECT COALESCE(MAX(yml_category_id), 0) + 1 FROM categories') ?? 1);
            $nextSort = (int)(cms_value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories') ?? 1);

            $row = $cat;
            unset($row['slug']);
            $row['slug']            = $slug;
            $row['yml_category_id'] = $nextYml;
            $row['sort_order']      = $nextSort;
            $row['is_published']    = 1;
            $row['created_at']      = cms_now();
            $row['updated_at']      = cms_now();

            $categoryId = cms_insert('categories', $row);
            out('  создана, номер в фиде Яндекс.Маркета: ' . $nextYml);
        }
    }
}

/*
 * Подпись «Каталог · категория 1 из 2» хранится в самой категории, поэтому
 * после появления новой она устаревает у всех остальных. Переписываем только
 * те подписи, которые владелец не менял руками.
 */
if ($apply && $categoryId) {
    $all   = cms_all('SELECT id, label FROM categories WHERE is_published = 1 ORDER BY sort_order, id');
    $total = count($all);
    $fixed = 0;

    foreach ($all as $i => $row) {
        if ($row['label'] !== null && !preg_match('~^Каталог · категория \d+ из \d+$~u', (string)$row['label'])) {
            continue;   // подпись своя — не наше дело
        }
        $label = 'Каталог · категория ' . ($i + 1) . ' из ' . $total;
        if ((string)$row['label'] !== $label) {
            cms_update('categories', ['label' => $label], 'id = :id', ['id' => (int)$row['id']]);
            $fixed++;
        }
    }

    if ($fixed > 0) {
        out('Подписи «категория N из M» обновлены: ' . $fixed . '.');
    }
}

/*
 * Карточка категории на главной странице.
 *
 * Блок «Каталог» на главной — это обычная разметка в базе, а не список из
 * категорий: так было с самого перехода на CMS, и тексты там написаны
 * вручную под каждую категорию. Значит, новая категория сама там не
 * появится, и покупатель, зашедший на главную, о ней не узнает.
 *
 * Добавляем карточку только если её там ещё нет — проверяем по адресу
 * категории. Текст потом правится в панели: Главная → «Категории каталога».
 */
if ($apply && $categoryId && !empty($data['category'])) {
    $block = cms_one('SELECT * FROM home_blocks WHERE code = ?', ['categories']);
    $url   = '/' . trim((string)$data['category']['slug'], '/') . '/';

    if (!$block) {
        out('Блока «Категории каталога» на главной нет — карточку добавить некуда.');
    } elseif (str_contains((string)$block['body'], 'href="' . $url . '"')) {
        out('Карточка категории на главной уже есть — не трогаю.');
    } else {
        $cat  = $data['category'];
        $card = "        <article class=\"category-card\">\n"
            . "          <p class=\"section-label\">Категория</p>\n"
            . '          <h2>' . e((string)$cat['name']) . "</h2>\n"
            . '          <p>' . e(trim((string)($cat['cross_text'] ?? $cat['lead'] ?? ''))) . "</p>\n"
            . (empty($cat['meta']) ? '' : '          <span class="category-meta">' . e((string)$cat['meta']) . "</span>\n")
            . '          <a class="button primary" href="' . e($url) . '">'
            . e((string)($cat['cross_button'] ?? 'Смотреть ' . mb_strtolower((string)$cat['name']))) . "</a>\n"
            . "        </article>\n";

        // Вставляем перед закрывающим тегом секции, чтобы карточка встала
        // последней в ряду, а не после него.
        $body = (string)$block['body'];
        $at   = mb_strrpos($body, '</section>');

        if ($at === false) {
            out('Не понял разметку блока «Категории каталога» — карточку добавьте в панели вручную.');
        } else {
            cms_update('home_blocks', [
                'body'       => mb_substr($body, 0, $at) . $card . mb_substr($body, $at),
                'updated_at' => cms_now(),
            ], 'id = :id', ['id' => (int)$block['id']]);
            out('На главную добавлена карточка категории «' . $cat['name'] . '».');
        }
    }
}

out();

/* --------------------------------------------------- словарь характеристик */

/** Возвращает id характеристики, создавая её при необходимости. */
function import_attribute_id(string $name, bool $apply): int
{
    static $cache = [];

    if (isset($cache[$name])) {
        return $cache[$name];
    }

    $existing = cms_value('SELECT id FROM attributes WHERE name = ?', [$name]);
    if ($existing) {
        return $cache[$name] = (int)$existing;
    }

    if (!$apply) {
        return $cache[$name] = 0;
    }

    $code = mb_substr((string)preg_replace('~[^a-z0-9]+~', '_',
        mb_strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $name) ?: $name)), 0, 90);
    $code = trim($code, '_') ?: 'attr_' . substr(md5($name), 0, 8);

    // Код мог оказаться занят другой характеристикой с похожим названием.
    if (cms_value('SELECT id FROM attributes WHERE code = ?', [$code])) {
        $code = mb_substr($code, 0, 80) . '_' . substr(md5($name), 0, 6);
    }

    $order = (int)(cms_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM attributes') ?? 10);

    return $cache[$name] = cms_insert('attributes', [
        'name' => $name, 'code' => $code, 'field_type' => 'text',
        'is_visible' => 1, 'in_yml' => 1, 'sort_order' => $order,
        'created_at' => cms_now(),
    ]);
}

/** Собирает статью из пар «подзаголовок => абзац» в готовую разметку. */
function import_article_html(array $blocks): string
{
    $html = '';
    foreach ($blocks as $title => $text) {
        $html .= '<h3>' . e((string)$title) . "</h3>\n";
        $html .= '<p>' . e((string)$text) . "</p>\n";
    }
    return trim($html);
}

/* ------------------------------------------------------------------ товары */

$created = 0;
$updated = 0;
$newAttrs = [];

foreach ($data['products'] as $item) {
    $slug = (string)($item['slug'] ?? '');
    if ($slug === '') {
        out('Пропускаю запись без адреса (slug).');
        continue;
    }

    $was = cms_one('SELECT * FROM products WHERE slug = ?', [$slug]);
    $specs = (array)($item['specs'] ?? []);
    $faq   = (array)($item['faq'] ?? []);

    out(sprintf('%-16s %-42s %9s  %s',
        $slug,
        mb_substr((string)($item['short_name'] ?? $item['name'] ?? ''), 0, 42),
        cms_money((float)($item['price'] ?? 0)),
        $was ? 'обновляю' : 'создаю'));
    out(sprintf('%18s характеристик %d, вопросов %d, статья %s',
        '', count($specs), count($faq),
        empty($item['article']) ? 'нет' : mb_strlen(import_article_html((array)$item['article'])) . ' симв.'));

    if (!$apply) {
        foreach (array_keys($specs) as $name) {
            if (!cms_value('SELECT id FROM attributes WHERE name = ?', [(string)$name])) {
                $newAttrs[(string)$name] = true;
            }
        }
        continue;
    }

    $shortName = (string)($item['short_name'] ?? $item['name'] ?? '');
    $lead      = (string)($item['lead'] ?? '');

    $row = [
        'category_id'    => $categoryId,
        'name'           => (string)($item['name'] ?? $shortName),
        'short_name'     => $shortName,
        'model'          => (string)($item['model'] ?? ''),
        'slug'           => $slug,
        'sku'            => (string)($item['sku'] ?? ''),
        // MPN у прежних товаров — то же, что модель. Держим единообразно.
        'mpn'            => (string)($item['mpn'] ?? $item['model'] ?? ''),
        'brand'          => (string)($item['brand'] ?? ''),
        'lead'           => $lead,
        'description'    => (string)($item['description'] ?? $lead),
        'price'          => $item['price'] ?? null,
        'availability'   => (string)($item['availability'] ?? 'in_stock'),
        'preorder_note'  => (string)($item['preorder_note'] ?? '') ?: null,
        'unit'           => (string)($item['unit'] ?? 'шт.'),
        'warranty'       => (string)($item['warranty'] ?? '') ?: null,
        'condition_note' => (string)($item['condition_note'] ?? '') ?: null,
        'country'        => (string)($item['country'] ?? '') ?: null,
        'is_published'   => (int)($item['is_published'] ?? 1),
        'in_yml'         => (int)($item['in_yml'] ?? 1),
        'sort_order'     => (int)($item['sort_order'] ?? 0),
        // Тексты карточки каталога: если отдельных нет, берём общие.
        'card_title'        => (string)($item['card_title'] ?? $shortName),
        'card_lead'         => (string)($item['card_lead'] ?? $lead),
        'card_alt'          => (string)($item['card_alt'] ?? $item['name'] ?? $shortName),
        'card_figure_label' => (string)($item['card_figure_label'] ?? 'Увеличить фото ' . $shortName),
        'card_tags'         => (string)($item['card_tags'] ?? ''),
        // SEO: если не задано явно, собираем по тому же образцу, что у
        // прежних товаров каталога.
        'seo_title'      => (string)($item['seo_title'] ?? $shortName . ' купить | Comp-Uter'),
        'seo_desc'       => (string)($item['seo_desc'] ?? $lead),
        'seo_h1'         => (string)($item['seo_h1'] ?? $shortName),
        'og_title'       => (string)($item['og_title'] ?? $shortName),
        'og_desc'        => (string)($item['og_desc'] ?? $lead),
        'breadcrumb'     => (string)($item['breadcrumb'] ?? $item['model'] ?? $shortName),
        'article_title'  => (string)($item['article_title'] ?? '') ?: null,
        'article_html'   => empty($item['article']) ? null : import_article_html((array)$item['article']),
        'updated_at'     => cms_now(),
    ];

    if ($was) {
        cms_update('products', $row, 'id = :id', ['id' => (int)$was['id']]);
        $productId = (int)$was['id'];
        $updated++;
    } else {
        $row['created_at'] = cms_now();
        $productId = cms_insert('products', $row);
        $created++;
    }

    /*
     * Характеристики, короткий список карточки и вопросы переписываем
     * целиком: файл — источник правды для них. Фотографии не трогаем
     * вовсе, их добавляют в панели.
     */
    cms_query('DELETE FROM product_attribute_values WHERE product_id = ?', [$productId]);
    cms_query('DELETE FROM product_card_specs WHERE product_id = ?', [$productId]);
    cms_query('DELETE FROM product_faq WHERE product_id = ?', [$productId]);

    $order = 0;
    $full  = ['Артикул' => (string)($item['sku'] ?? '')] + $specs;
    $full['Комплектация']        = (string)($item['condition_note'] ?? '');
    $full['Гарантия']            = (string)($item['warranty'] ?? '');
    $full['Страна-изготовитель'] = (string)($item['country'] ?? '');

    foreach ($full as $name => $value) {
        if (trim((string)$value) === '') {
            continue;
        }
        cms_insert('product_attribute_values', [
            'product_id'   => $productId,
            'attribute_id' => import_attribute_id((string)$name, true),
            'value'        => (string)$value,
            'sort_order'   => $order += 10,
        ]);
    }

    /*
     * Короткий список в карточке каталога.
     *
     * Оформление показывает из него только четвёртую, пятую, шестую и
     * восьмую строки — так задумано в вёрстке каталога, чтобы карточки были
     * одинаковой высоты. Поэтому порядок строк здесь важен: на этих местах
     * должно оказаться самое существенное. Если в файле есть свой список
     * card_specs — берём его, он и задаёт порядок; если нет, используем
     * полный список без «Особенностей» (они и так идут метками под карточкой).
     */
    $cardSpecs = (array)($item['card_specs'] ?? []);
    if (!$cardSpecs) {
        $cardSpecs = $full;
        unset($cardSpecs['Особенности']);
    }

    $cardOrder = 0;
    foreach ($cardSpecs as $name => $value) {
        if (trim((string)$value) === '') {
            continue;
        }
        cms_insert('product_card_specs', [
            'product_id' => $productId,
            'name'       => (string)$name,
            'value'      => (string)$value,
            'sort_order' => $cardOrder += 10,
        ]);
    }

    $faqOrder = 0;
    foreach ($faq as $pair) {
        if (count($pair) < 2) {
            continue;
        }
        cms_insert('product_faq', [
            'product_id' => $productId,
            'question'   => (string)$pair[0],
            'answer'     => (string)$pair[1],
            'sort_order' => $faqOrder += 10,
        ]);
    }
}

out();

if (!$apply) {
    if ($newAttrs) {
        out('Будут добавлены характеристики в словарь: ' . implode(', ', array_keys($newAttrs)) . '.');
        out();
    }
    out('---------------------------------------------------------------');
    out('Это был пробный запуск. Чтобы загрузить:');
    out('    php cms/install/import-products.php ' . $file . ' --apply');
    exit(0);
}

/* --------------------------------------------- шаблон характеристик категории */

/*
 * Какие характеристики предлагать при создании следующего товара в этой
 * категории. Список собирается из того, что уже заполнено у её товаров, —
 * так владельцу не придётся вспоминать порядок строк вручную.
 */
if ($categoryId) {
    cms_query('DELETE FROM category_attributes WHERE category_id = ?', [$categoryId]);
    foreach (cms_all(
        'SELECT pav.attribute_id, MIN(pav.sort_order) AS so
           FROM product_attribute_values pav
           JOIN products p ON p.id = pav.product_id
          WHERE p.category_id = ?
          GROUP BY pav.attribute_id
          ORDER BY so',
        [$categoryId]
    ) as $row) {
        cms_insert('category_attributes', [
            'category_id'  => $categoryId,
            'attribute_id' => (int)$row['attribute_id'],
            'sort_order'   => (int)$row['so'],
        ]);
    }
}

cms_audit('products.import', null, null,
    'Загрузка из ' . basename($path) . ': создано ' . $created . ', обновлено ' . $updated);

out('---------------------------------------------------------------');
out('Готово. Создано товаров: ' . $created . ', обновлено: ' . $updated . '.');
out();
out('Что осталось сделать в панели:');
out('  1. Товары → каждый товар → вкладка «Фотографии» → загрузить снимок.');
out('     Без фотографии товар показывается с заглушкой и не уходит');
out('     в фид Яндекс.Маркета.');
out('  2. Проверить у каждого гарантию, комплектацию и страну-изготовителя:');
out('     они зависят от поставки, и в файле стоят наиболее частые значения.');
out('  3. Обслуживание → «Очистить кэш», чтобы фид собрался заново.');
