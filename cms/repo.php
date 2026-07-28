<?php

/**
 * Доступ к данным каталога.
 *
 * Единственное место, откуда публичный сайт берёт товары, категории,
 * характеристики, фотографии, FAQ и связи. Раньше эти же сведения лежали
 * в трёх местах сразу (products.php, prices.json и разметка каждой страницы),
 * и любое расхождение между ними было видно посетителю.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/* ------------------------------------------------------------- категории */

function repo_categories(bool $publishedOnly = true): array
{
    static $cache = [];
    $key = $publishedOnly ? 'pub' : 'all';
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $where = $publishedOnly ? 'WHERE is_published = 1' : '';
    return $cache[$key] = cms_all("SELECT * FROM categories $where ORDER BY sort_order, name");
}

function repo_category(string $slug): ?array
{
    foreach (repo_categories(false) as $row) {
        if (mb_strtolower($row['slug']) === mb_strtolower($slug)) {
            return $row;
        }
    }

    return null;
}

function repo_category_by_id(?int $id): ?array
{
    if (!$id) {
        return null;
    }
    foreach (repo_categories(false) as $row) {
        if ((int)$row['id'] === $id) {
            return $row;
        }
    }

    return null;
}

/* ---------------------------------------------------------------- товары */

/**
 * Список товаров.
 *
 * @param array{category?:string|int,published?:bool,featured?:bool,limit?:int,
 *              exclude?:int,search?:string,availability?:string} $filter
 */
function repo_products(array $filter = []): array
{
    $sql = 'SELECT p.*, c.slug AS category_slug, c.name AS category_name
              FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.deleted_at IS NULL';
    $params = [];

    if ($filter['published'] ?? true) {
        $sql .= ' AND p.is_published = 1';
    }
    if (!empty($filter['category'])) {
        if (is_numeric($filter['category'])) {
            $sql .= ' AND p.category_id = ?';
            $params[] = (int)$filter['category'];
        } else {
            $sql .= ' AND c.slug = ?';
            $params[] = (string)$filter['category'];
        }
    }
    if (!empty($filter['featured'])) {
        $sql .= ' AND p.is_featured = 1';
    }
    if (!empty($filter['availability'])) {
        $sql .= ' AND p.availability = ?';
        $params[] = (string)$filter['availability'];
    }
    if (!empty($filter['exclude'])) {
        $sql .= ' AND p.id <> ?';
        $params[] = (int)$filter['exclude'];
    }
    if (!empty($filter['search'])) {
        $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)';
        $like = '%' . $filter['search'] . '%';
        array_push($params, $like, $like, $like);
    }

    $sql .= ' ORDER BY p.sort_order, p.id';

    if (!empty($filter['limit'])) {
        $sql .= ' LIMIT ' . (int)$filter['limit'];
    }

    return cms_all($sql, $params);
}

function repo_product(string $slug): ?array
{
    return cms_one(
        'SELECT p.*, c.slug AS category_slug, c.name AS category_name
           FROM products p
      LEFT JOIN categories c ON c.id = p.category_id
          WHERE p.slug = ? AND p.deleted_at IS NULL',
        [$slug]
    );
}

function repo_product_by_id(int $id): ?array
{
    return cms_one(
        'SELECT p.*, c.slug AS category_slug, c.name AS category_name
           FROM products p
      LEFT JOIN categories c ON c.id = p.category_id
          WHERE p.id = ?',
        [$id]
    );
}

/** Характеристики товара в порядке, заданном в CMS. */
function repo_product_attributes(int $productId): array
{
    return cms_all(
        'SELECT a.name, a.unit, pav.value
           FROM product_attribute_values pav
           JOIN attributes a ON a.id = pav.attribute_id
          WHERE pav.product_id = ? AND a.is_visible = 1
          ORDER BY pav.sort_order, a.sort_order',
        [$productId]
    );
}

/** То же в виде «название => значение» — для микроразметки и фида. */
function repo_product_params(int $productId): array
{
    $out = [];
    foreach (repo_product_attributes($productId) as $row) {
        $out[$row['name']] = (string)$row['value'];
    }

    return $out;
}

/**
 * Характеристики для фида: те же значения, но без тех, что помечены
 * «не выгружать в Яндекс.Маркет».
 */
function repo_product_feed_params(int $productId): array
{
    $out = [];
    foreach (cms_all(
        'SELECT a.name, pav.value
           FROM product_attribute_values pav
           JOIN attributes a ON a.id = pav.attribute_id
          WHERE pav.product_id = ? AND a.in_yml = 1
          ORDER BY pav.sort_order, a.sort_order',
        [$productId]
    ) as $row) {
        $out[$row['name']] = (string)$row['value'];
    }

    return $out;
}

function repo_product_images(int $productId): array
{
    return cms_all(
        'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order, id',
        [$productId]
    );
}

function repo_product_main_image(int $productId): ?array
{
    $images = repo_product_images($productId);
    return $images[0] ?? null;
}

function repo_product_faq(int $productId): array
{
    return cms_all(
        'SELECT question, answer FROM product_faq WHERE product_id = ? ORDER BY sort_order, id',
        [$productId]
    );
}

/**
 * Похожие товары. Если связи заданы в CMS — берём их; если нет, подбираем
 * автоматически из той же категории, как это делала прежняя версия сайта,
 * чтобы у нового товара блок не оказался пустым.
 */
function repo_product_related(int $productId, string $relation = 'similar', int $limit = 3): array
{
    $rows = cms_all(
        'SELECT p.*, c.slug AS category_slug, r.note
           FROM product_relations r
           JOIN products p ON p.id = r.related_id
      LEFT JOIN categories c ON c.id = p.category_id
          WHERE r.product_id = ? AND r.relation = ?
            AND p.is_published = 1 AND p.deleted_at IS NULL
          ORDER BY r.sort_order
          LIMIT ' . (int)$limit,
        [$productId, $relation]
    );

    if ($rows) {
        return $rows;
    }

    $product = repo_product_by_id($productId);
    if (!$product || !$product['category_id']) {
        return [];
    }

    return repo_products([
        'category' => (int)$product['category_id'],
        'exclude'  => $productId,
        'limit'    => $limit,
    ]);
}

/* --------------------------------------------------------------- цены */

function repo_price(?array $product): ?float
{
    if (!$product || $product['price'] === null || $product['price'] === '') {
        return null;
    }

    $price = (float)$product['price'];
    return $price > 0 ? $price : null;
}

function repo_old_price(?array $product): ?float
{
    if (!$product || $product['old_price'] === null || $product['old_price'] === '') {
        return null;
    }

    $old = (float)$product['old_price'];
    $current = repo_price($product);

    // Зачёркнутая цена имеет смысл, только если она выше текущей.
    return ($old > 0 && ($current === null || $old > $current)) ? $old : null;
}

const REPO_STOCK_LABELS = [
    'in_stock'     => 'В наличии',
    'preorder'     => 'Под заказ',
    'out_of_stock' => 'Нет в наличии',
];

const REPO_STOCK_SCHEMA = [
    'in_stock'     => 'https://schema.org/InStock',
    'preorder'     => 'https://schema.org/PreOrder',
    'out_of_stock' => 'https://schema.org/OutOfStock',
];

function repo_stock_label(?array $product): string
{
    return REPO_STOCK_LABELS[$product['availability'] ?? 'in_stock'] ?? 'В наличии';
}

function repo_is_orderable(?array $product): bool
{
    return repo_price($product) !== null && ($product['availability'] ?? '') !== 'out_of_stock';
}

/* ---------------------------------------------------------- изображения */

/** Путь к оригиналу (JPG/PNG) — для og:image, JSON-LD и фида. */
function repo_image_original(?array $product): string
{
    $image = $product ? repo_product_main_image((int)$product['id']) : null;
    $path = $image['path'] ?? '';

    if ($path !== '' && is_file(CMS_ROOT . '/' . ltrim($path, '/'))) {
        return $path;
    }

    return '/public/images/products/placeholder.jpg';
}

/** Путь для <img> на сайте: .webp рядом, если он есть. */
function repo_image_web(?array $product): string
{
    $original = repo_image_original($product);
    $webp = preg_replace('~\.(jpe?g|png)$~i', '.webp', $original);

    if (is_string($webp) && $webp !== $original && is_file(CMS_ROOT . '/' . ltrim($webp, '/'))) {
        return $webp;
    }

    return $original;
}

function repo_image_is_placeholder(?array $product): bool
{
    return str_contains(repo_image_original($product), 'placeholder.');
}

/** srcset из уменьшенных копий -600.webp и -900.webp. */
function repo_image_srcset(string $web): string
{
    $parts = [];
    foreach ([600, 900] as $width) {
        $variant = preg_replace('~\.(webp|jpe?g|png)$~i', '-' . $width . '.webp', $web);
        if (is_string($variant) && is_file(CMS_ROOT . '/' . ltrim($variant, '/'))) {
            $parts[] = $variant . ' ' . $width . 'w';
        }
    }

    if (!$parts) {
        return '';
    }

    $size = @getimagesize(CMS_ROOT . '/' . ltrim($web, '/'));
    if (is_array($size) && !empty($size[0])) {
        $parts[] = $web . ' ' . (int)$size[0] . 'w';
    }

    return implode(', ', $parts);
}

/** Готовый тег <img> с размерами, srcset и ленивой загрузкой. */
function repo_image_tag(?array $product, string $alt, bool $lazy = false, string $sizes = ''): string
{
    $src = repo_image_web($product);
    $html = '<img src="' . e($src) . '" alt="' . e($alt) . '"';

    $srcset = repo_image_srcset($src);
    if ($srcset !== '') {
        $html .= ' srcset="' . e($srcset) . '"';
        $html .= ' sizes="' . e($sizes !== '' ? $sizes : '(max-width: 720px) 92vw, 320px') . '"';
    }

    $html .= ' data-full="' . e($src) . '"';

    $size = @getimagesize(CMS_ROOT . '/' . ltrim($src, '/'));
    if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
        $html .= ' width="' . (int)$size[0] . '" height="' . (int)$size[1] . '"';
    }

    $html .= $lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high" decoding="async"';

    return $html . ' />';
}

/* ------------------------------------------------------------- страницы */

function repo_page(string $slug): ?array
{
    return cms_one('SELECT * FROM pages WHERE slug = ? AND status = ?', [$slug, 'published']);
}

/* -------------------------------------------------------------- главная */

/** Включённые секции главной в заданном порядке. */
function repo_home_blocks(): array
{
    return cms_all('SELECT * FROM home_blocks WHERE is_enabled = 1 ORDER BY sort_order, id');
}

/** Настройки блока (JSON в поле settings) в виде массива. */
function repo_home_block_settings(array $block): array
{
    $decoded = json_decode((string)($block['settings'] ?? ''), true);

    return is_array($decoded) ? $decoded : [];
}

/** Товары витрины: категория задана в настройках блока. */
function repo_home_block_products(array $block): array
{
    $settings = repo_home_block_settings($block);
    $filter = [];

    if (!empty($settings['category'])) {
        $filter['category'] = $settings['category'];
    }
    if (!empty($settings['featured'])) {
        $filter['featured'] = true;
    }
    if (!empty($settings['limit'])) {
        $filter['limit'] = (int)$settings['limit'];
    }

    return repo_products($filter);
}

/**
 * Вопросы и ответы главной — прямо из разметки блока «Частые вопросы».
 *
 * Микроразметка FAQPage строится из того же текста, который видит посетитель,
 * поэтому второй список, способный с ним разойтись, не нужен.
 */
function repo_home_faq(array $blocks): array
{
    $pairs = [];

    foreach ($blocks as $block) {
        if (!str_contains((string)$block['body'], 'faq-item')) {
            continue;
        }
        if (!preg_match_all(
            '~<details class="faq-item">\s*<summary>(.*?)</summary>\s*<p>(.*?)</p>~s',
            (string)$block['body'],
            $matches,
            PREG_SET_ORDER
        )) {
            continue;
        }

        $clean = static fn(string $text): string => trim(
            preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')) ?? ''
        );

        foreach ($matches as $m) {
            $pairs[] = ['question' => $clean($m[1]), 'answer' => $clean($m[2])];
        }
    }

    return $pairs;
}

/* ------------------------------------------------------------- доставка */

function repo_delivery_methods(): array
{
    return cms_all('SELECT * FROM delivery_methods WHERE is_active = 1 ORDER BY sort_order, id');
}

/* ------------------------------------------------------------ адреса */

function repo_product_url(array $product): string
{
    return '/products/' . $product['slug'] . '/';
}

function repo_category_url(array $category): string
{
    return '/' . $category['slug'] . '/';
}
