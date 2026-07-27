<?php

/**
 * Единый источник цен сайта comp-uter.ru.
 *
 * Все цены на сайте берутся ТОЛЬКО из файла /data/prices.json.
 * Этот файл — читатель этого JSON: он разбирает его максимально терпимо,
 * приводит значения к нормальному виду и отдаёт готовые куски разметки
 * (карточка каталога, карточка товара, блок «похожие», JSON-LD, YML-фид).
 *
 * Принципы устойчивости (см. PRICE_MANAGEMENT_INSTRUCTION.txt):
 *   - товара нет в prices.json  -> «Цена по запросу», без undefined/NaN;
 *   - цена одного товара кривая -> страдает только этот товар;
 *   - весь JSON сломан          -> цены восстанавливаются построчно регуляркой,
 *                                  сайт продолжает работать;
 *   - старая цена показывается, только если она реально заполнена и больше текущей.
 */

declare(strict_types=1);

if (!defined('PRICES_FILE')) {
    define('PRICES_FILE', dirname(__DIR__) . '/data/prices.json');
}

const PRICES_NBSP = "\u{00A0}";
const PRICES_NO_PRICE_TEXT = 'Цена по запросу';

const PRICES_STOCK_LABELS = [
    'in_stock'     => 'В наличии',
    'preorder'     => 'Под заказ',
    'out_of_stock' => 'Нет в наличии',
];

const PRICES_SCHEMA_AVAILABILITY = [
    'in_stock'     => 'https://schema.org/InStock',
    'preorder'     => 'https://schema.org/PreOrder',
    'out_of_stock' => 'https://schema.org/OutOfStock',
];

// Синонимы полей: файл можно вести и по-русски, и по-английски.
const PRICES_FIELD_ALIASES = [
    'id'         => ['id', 'ид', 'идентификатор', 'slug', 'код'],
    'sku'        => ['артикул', 'sku', 'vendorcode', 'vendor_code'],
    'name'       => ['название', 'наименование', 'name', 'title'],
    'price'      => ['цена', 'price', 'текущая_цена', 'новая_цена'],
    'old_price'  => ['старая_цена', 'старая цена', 'old_price', 'oldprice', 'цена_до_скидки'],
    'stock'      => ['наличие', 'статус', 'availability', 'status', 'в_наличии'],
    'unit'       => ['единица', 'единица_измерения', 'unit', 'ед', 'ед_изм'],
];

/* ------------------------------------------------------------------ чтение */

/**
 * Читает и разбирает /data/prices.json.
 * Возвращает ['records' => [...], 'notes' => [...], 'mode' => 'json|repaired|recovered|missing'].
 * Результат кэшируется в статической переменной на время одного запроса.
 */
function prices_state(): array
{
    static $state = null;
    if ($state !== null) {
        return $state;
    }

    $state = ['records' => [], 'notes' => [], 'mode' => 'missing', 'file' => PRICES_FILE];

    if (!is_file(PRICES_FILE) || !is_readable(PRICES_FILE)) {
        $state['notes'][] = 'Файл цен не найден: ' . PRICES_FILE;
        return $state;
    }

    $raw = (string)@file_get_contents(PRICES_FILE);
    if (trim($raw) === '') {
        $state['notes'][] = 'Файл цен пустой.';
        return $state;
    }

    // Убираем BOM, который добавляют некоторые редакторы Windows: с ним
    // json_decode() отказывается разбирать совершенно корректный файл.
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

    $rows = null;
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $rows = prices_extract_rows($decoded);
        $state['mode'] = 'json';
    }

    // Попытка №2: типовые ручные ошибки — лишняя запятая перед } или ],
    // комментарии // и /* */, которых в JSON быть не должно.
    if ($rows === null) {
        $repaired = preg_replace('~/\*.*?\*/~s', '', $raw);
        $repaired = preg_replace('~(^|[\s,{\[])//[^\n\r]*~', '$1', (string)$repaired);
        $repaired = preg_replace('~,\s*([}\]])~', '$1', (string)$repaired);
        $decoded = json_decode((string)$repaired, true);
        if (is_array($decoded)) {
            $rows = prices_extract_rows($decoded);
            $state['mode'] = 'repaired';
            $state['notes'][] = 'В файле цен есть синтаксическая ошибка (лишняя запятая или комментарий). Цены прочитаны, но файл лучше поправить.';
        }
    }

    // Попытка №3: файл сломан настолько, что JSON не читается вовсе.
    // Вытаскиваем пары «ключ: значение» построчно, чтобы сайт не остался без цен.
    if ($rows === null) {
        $rows = prices_recover_rows($raw);
        $state['mode'] = $rows ? 'recovered' : 'broken';
        $state['notes'][] = $rows
            ? 'Файл цен повреждён и прочитан аварийным способом. Проверьте его на https://jsonlint.com и исправьте.'
            : 'Файл цен повреждён и не читается. Сайт показывает «' . PRICES_NO_PRICE_TEXT . '».';
    }

    foreach ($rows as $row) {
        $record = prices_normalize_row($row);
        if ($record === null) {
            continue;
        }
        // Товар доступен и по внутреннему идентификатору, и по артикулу.
        $state['records'][$record['key']] = $record;
    }

    return $state;
}

/** Достаёт список товаров из любого разумного корня файла. */
function prices_extract_rows(array $decoded): ?array
{
    foreach (['товары', 'products', 'items', 'позиции', 'прайс'] as $key) {
        if (isset($decoded[$key]) && is_array($decoded[$key])) {
            return array_values(array_filter($decoded[$key], 'is_array'));
        }
    }

    // Корень — сразу массив товаров.
    $rows = array_values(array_filter($decoded, 'is_array'));
    return $rows ?: null;
}

/**
 * Аварийное чтение: находит блоки { ... } и вытаскивает из них пары
 * "ключ": значение. Работает, даже если между товарами потеряна запятая.
 */
function prices_recover_rows(string $raw): array
{
    $rows = [];
    if (!preg_match_all('~\{[^{}]*\}~u', $raw, $blocks)) {
        return $rows;
    }

    foreach ($blocks[0] as $block) {
        if (!preg_match_all('~"([^"]+)"\s*:\s*("(?:[^"\\\\]|\\\\.)*"|-?\d+(?:[.,]\d+)?|true|false|null)~u', $block, $pairs, PREG_SET_ORDER)) {
            continue;
        }
        $row = [];
        foreach ($pairs as $pair) {
            $value = $pair[2];
            if (str_starts_with($value, '"')) {
                $value = stripcslashes(substr($value, 1, -1));
            }
            $row[$pair[1]] = $value;
        }
        if ($row) {
            $rows[] = $row;
        }
    }

    return $rows;
}

/* -------------------------------------------------------- нормализация */

function prices_field(array $row, string $field)
{
    foreach (PRICES_FIELD_ALIASES[$field] as $alias) {
        foreach ($row as $key => $value) {
            if (mb_strtolower(trim((string)$key)) === $alias) {
                return $value;
            }
        }
    }

    return null;
}

/**
 * Приводит цену к числу. Понимает 15500, "15500", "15 500", "15 500 ₽",
 * "15500,50" и "15500.50". Всё остальное — не цена (вернёт null).
 */
function prices_parse_amount($value): ?float
{
    if (is_int($value) || is_float($value)) {
        $number = (float)$value;
        return $number > 0 ? round($number, 2) : null;
    }

    $text = trim((string)($value ?? ''));
    if ($text === '' || $text === '-') {
        return null;
    }

    // Убираем пробелы (в т.ч. неразрывные), знак рубля и слово «руб».
    $text = preg_replace('~[\s\x{00A0}\x{202F}]+~u', '', $text);
    $text = preg_replace('~(₽|руб\.?|rub|r)$~ui', '', (string)$text);
    $text = str_replace(',', '.', (string)$text);

    if (!preg_match('~^\d+(\.\d+)?$~', $text)) {
        return null;
    }

    $number = (float)$text;
    return $number > 0 ? round($number, 2) : null;
}

function prices_parse_stock($value): string
{
    $text = mb_strtolower(trim((string)($value ?? '')));
    if ($text === '') {
        return 'in_stock';
    }
    if (str_contains($text, 'под заказ') || str_contains($text, 'preorder') || str_contains($text, 'заказ')) {
        return 'preorder';
    }
    if (str_contains($text, 'нет') || str_contains($text, 'отсут') || str_contains($text, 'out') || $text === 'false' || $text === '0') {
        return 'out_of_stock';
    }

    return 'in_stock';
}

function prices_normalize_row(array $row): ?array
{
    $id  = trim((string)(prices_field($row, 'id') ?? ''));
    $sku = trim((string)(prices_field($row, 'sku') ?? ''));
    if ($id === '' && $sku === '') {
        return null;
    }

    $rawPrice = prices_field($row, 'price');
    $price    = prices_parse_amount($rawPrice);
    $oldPrice = prices_parse_amount(prices_field($row, 'old_price'));
    $unit     = trim((string)(prices_field($row, 'unit') ?? ''));

    $problems = [];
    $rawPriceText = is_scalar($rawPrice) ? trim((string)$rawPrice) : '';
    if ($price === null) {
        $problems[] = $rawPriceText === ''
            ? 'цена не заполнена'
            : 'цену не удалось прочитать: «' . $rawPriceText . '»';
    }
    // Зачёркнутая цена имеет смысл, только если она выше текущей.
    if ($oldPrice !== null && $price !== null && $oldPrice <= $price) {
        $oldPrice = null;
        $problems[] = 'старая цена не больше текущей — зачёркнутая цена не показывается';
    }

    return [
        'key'       => mb_strtolower($id !== '' ? $id : $sku),
        'id'        => $id,
        'sku'       => $sku,
        'name'      => trim((string)(prices_field($row, 'name') ?? '')),
        'price'     => $price,
        'old_price' => $oldPrice,
        'stock'     => prices_parse_stock(prices_field($row, 'stock')),
        'unit'      => $unit !== '' ? $unit : 'шт.',
        'problems'  => $problems,
    ];
}

/* ------------------------------------------------------------- доступ */

/** Все товары из файла цен: ключ — внутренний идентификатор. */
function prices_all(): array
{
    return prices_state()['records'];
}

/** Товар по внутреннему идентификатору ИЛИ по артикулу. Названия не участвуют. */
function prices_get(?string $key): ?array
{
    $key = mb_strtolower(trim((string)$key));
    if ($key === '') {
        return null;
    }

    $records = prices_all();
    if (isset($records[$key])) {
        return $records[$key];
    }

    foreach ($records as $record) {
        if (mb_strtolower($record['sku']) === $key || mb_strtolower($record['id']) === $key) {
            return $record;
        }
    }

    return null;
}

/** Цена товара или null, если её нет. */
function prices_value(?string $key): ?float
{
    return prices_get($key)['price'] ?? null;
}

function prices_stock(?string $key): string
{
    return prices_get($key)['stock'] ?? 'in_stock';
}

function prices_unit(?string $key): string
{
    return prices_get($key)['unit'] ?? 'шт.';
}

/* --------------------------------------------------------- форматирование */

/** 15500 -> «15 500 ₽», 15500.5 -> «15 500,50 ₽». Разделители неразрывные. */
function prices_format(?float $value): string
{
    if ($value === null) {
        return PRICES_NO_PRICE_TEXT;
    }

    $isWhole = abs($value - round($value)) < 0.005;
    $number  = $isWhole
        ? number_format(round($value), 0, ',', PRICES_NBSP)
        : number_format($value, 2, ',', PRICES_NBSP);

    return $number . PRICES_NBSP . '₽';
}

/** Число для микроразметки и фида: «15500» или «15500.50», без пробелов. */
function prices_machine(?float $value): string
{
    if ($value === null) {
        return '';
    }

    $isWhole = abs($value - round($value)) < 0.005;
    return $isWhole ? (string)(int)round($value) : number_format($value, 2, '.', '');
}

function prices_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ------------------------------------------------------------- разметка */

/**
 * Цена в карточке каталога: <p class="model-price">15 500 ₽</p>.
 * Если задана старая цена — рядом появляется зачёркнутое значение.
 */
function prices_card_html(?string $key): string
{
    return prices_block_html($key, 'model-price');
}

/** Цена на отдельной странице товара. */
function prices_page_html(?string $key): string
{
    return prices_block_html($key, 'product-dialog-price');
}

function prices_block_html(?string $key, string $className): string
{
    $record = prices_get($key);
    $price  = $record['price'] ?? null;
    $class  = $className . ($price === null ? ' price-unknown' : '');

    $html = '<p class="' . prices_escape($class) . '">' . prices_escape(prices_format($price));
    if ($price !== null && !empty($record['old_price'])) {
        $html .= ' <s class="price-old">' . prices_escape(prices_format($record['old_price'])) . '</s>';
    }

    return $html . '</p>';
}

/** Цена в блоке «Похожие товары». */
function prices_related_html(?string $key): string
{
    $record = prices_get($key);
    $price  = $record['price'] ?? null;
    $class  = 'related-price' . ($price === null ? ' price-unknown' : '');

    $html = '<span class="' . prices_escape($class) . '">' . prices_escape(prices_format($price));
    if ($price !== null && !empty($record['old_price'])) {
        $html .= ' <s class="price-old">' . prices_escape(prices_format($record['old_price'])) . '</s>';
    }

    return $html . '</span>';
}

/**
 * data-атрибуты карточки каталога. Корзина берёт цену отсюда, а не из текста,
 * поэтому цена на странице и цена в корзине не могут разойтись.
 */
function prices_card_attrs(?string $key): string
{
    $record = prices_get($key);
    $price  = $record['price'] ?? null;

    $attrs = ' data-price="' . prices_escape(prices_machine($price)) . '"';
    $attrs .= ' data-unit="' . prices_escape($record['unit'] ?? 'шт.') . '"';
    $attrs .= ' data-stock="' . prices_escape($record['stock'] ?? 'in_stock') . '"';
    if ($price === null || ($record['stock'] ?? '') === 'out_of_stock') {
        $attrs .= ' data-orderable="false"';
    }

    return $attrs;
}

/** Плашка наличия в карточке каталога. */
function prices_stock_badge(?string $key): string
{
    $stock = prices_stock($key);
    return '<span class="model-stock stock-' . prices_escape($stock) . '">'
        . prices_escape(PRICES_STOCK_LABELS[$stock] ?? PRICES_STOCK_LABELS['in_stock'])
        . '</span>';
}

/* ------------------------------------------------- микроразметка и фиды */

/** Массив offers для Schema.org. Без цены offer не выводится вовсе. */
function prices_offer_array(?string $key, string $url): ?array
{
    $record = prices_get($key);
    $price  = $record['price'] ?? null;
    if ($price === null) {
        return null;
    }

    $offer = [
        '@type'         => 'Offer',
        'url'           => $url,
        'price'         => prices_machine($price),
        'priceCurrency' => 'RUB',
        'availability'  => PRICES_SCHEMA_AVAILABILITY[$record['stock']] ?? PRICES_SCHEMA_AVAILABILITY['in_stock'],
        'itemCondition' => 'https://schema.org/NewCondition',
    ];

    if (!empty($record['old_price'])) {
        $offer['priceSpecification'] = [
            '@type'         => 'UnitPriceSpecification',
            'priceType'     => 'https://schema.org/ListPrice',
            'price'         => prices_machine($record['old_price']),
            'priceCurrency' => 'RUB',
        ];
    }

    return $offer;
}

/**
 * Готовый фрагмент JSON-LD: "offers":{...}. Когда цены нет, вместо offers
 * выводится корректный признак «нет предложения», а не пустой объект.
 */
function prices_offer_json(?string $key, string $url): string
{
    $offer = prices_offer_array($key, $url);
    if ($offer === null) {
        return '"offers":' . json_encode([
            '@type'         => 'Offer',
            'url'           => $url,
            'priceCurrency' => 'RUB',
            'availability'  => 'https://schema.org/OutOfStock',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return '"offers":' . json_encode($offer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ---------------------------------------------------------- доставка */

/**
 * Тарифы доставки на случай, когда блок «доставка» удалён из файла цен или
 * файл вовсе не читается: корзина продолжает считать по действующим тарифам,
 * а не показывает «по тарифам службы доставки» вместо всех вариантов.
 * Значение из /data/prices.json всегда важнее этих запасных.
 */
const PRICES_DELIVERY_FALLBACK = [
    'самовывоз'          => 0.0,
    'москва'             => 500.0,
    'московская_область' => 900.0,
    'россия'             => null,
];

/** Тарифы доставки из того же файла цен. Ключ не найден -> запасное значение. */
function prices_delivery(string $key, ?float $fallback = null): ?float
{
    static $rates = null;
    if ($rates === null) {
        $rates = [];
        $raw = @file_get_contents(PRICES_FILE);
        $decoded = is_string($raw) ? json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true) : null;
        if (is_array($decoded) && isset($decoded['доставка']) && is_array($decoded['доставка'])) {
            foreach ($decoded['доставка'] as $name => $value) {
                if (str_starts_with((string)$name, '_')) {
                    continue;
                }
                // 0 — легальная бесплатная доставка, "" — «по тарифам службы».
                $rates[mb_strtolower((string)$name)] = ($value === '' || $value === null)
                    ? null
                    : (prices_parse_amount($value) ?? 0.0);
            }
        }
    }

    $key = mb_strtolower($key);
    if (array_key_exists($key, $rates)) {
        return $rates[$key];
    }

    return PRICES_DELIVERY_FALLBACK[$key] ?? $fallback;
}

/** Значение для data-delivery-price: «500», «0» или пустая строка. */
function prices_delivery_attr(string $key): string
{
    $value = prices_delivery($key);
    if ($value === null) {
        return ''; // пустое значение = «по тарифам службы доставки»
    }

    return $value > 0 ? prices_machine($value) : '0';
}

/** Стоимость доставки для показа человеку. */
function prices_delivery_money(string $key): string
{
    $value = prices_delivery($key);
    if ($value === null) {
        return 'по тарифам службы доставки';
    }
    if ($value === 0.0) {
        return 'бесплатно';
    }

    return prices_format($value);
}

/* --------------------------------------------------------- диагностика */

/**
 * Сверка каталога (yandexmarket/products.php) с файлом цен.
 * Используется страницей /tools/check-prices.php.
 */
function prices_diagnostics(array $catalog): array
{
    $state   = prices_state();
    $records = $state['records'];

    $report = [
        'file'        => $state['file'],
        'mode'        => $state['mode'],
        'notes'       => $state['notes'],
        'catalog'     => count($catalog),
        'priced'      => 0,
        'missing'     => [],
        'problems'    => [],
        'orphans'     => [],
        'duplicates'  => [],
        'rows'        => [],
    ];

    $seenIds  = [];
    $seenSkus = [];
    foreach ($records as $record) {
        $idKey = mb_strtolower($record['id']);
        if ($idKey !== '') {
            $seenIds[$idKey] = ($seenIds[$idKey] ?? 0) + 1;
        }
        $skuKey = mb_strtolower($record['sku']);
        if ($skuKey !== '') {
            $seenSkus[$skuKey] = ($seenSkus[$skuKey] ?? 0) + 1;
        }
        foreach ($record['problems'] as $problem) {
            $report['problems'][] = ($record['id'] ?: $record['sku']) . ' — ' . $problem;
        }
    }
    foreach ($seenIds as $value => $count) {
        if ($count > 1) {
            $report['duplicates'][] = 'идентификатор «' . $value . '» встречается ' . $count . ' раз';
        }
    }
    foreach ($seenSkus as $value => $count) {
        if ($count > 1) {
            $report['duplicates'][] = 'артикул «' . $value . '» встречается ' . $count . ' раз';
        }
    }

    $usedKeys = [];
    foreach ($catalog as $product) {
        $slug   = (string)($product['slug'] ?? '');
        $sku    = (string)($product['id'] ?? '');
        $record = prices_get($slug) ?? prices_get($sku);

        if ($record !== null) {
            $usedKeys[$record['key']] = true;
        }
        if ($record !== null && $record['price'] !== null) {
            $report['priced']++;
        } else {
            $report['missing'][] = $slug . ' (' . $sku . ') — ' . (string)($product['name'] ?? '');
        }

        $report['rows'][] = [
            'slug'      => $slug,
            'sku'       => $sku,
            'name'      => (string)($product['name'] ?? ''),
            'price'     => $record['price'] ?? null,
            'old_price' => $record['old_price'] ?? null,
            'stock'     => $record['stock'] ?? null,
            'unit'      => $record['unit'] ?? null,
            'found'     => $record !== null,
        ];
    }

    foreach ($records as $key => $record) {
        if (!isset($usedKeys[$key])) {
            $report['orphans'][] = ($record['id'] ?: $record['sku']) . ' — ' . ($record['name'] ?: 'без названия');
        }
    }

    return $report;
}
