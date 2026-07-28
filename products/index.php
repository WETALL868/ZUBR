<?php

/**
 * Страница любого товара: /products/<slug>/
 *
 * Раньше на каждый товар лежал отдельный файл-копия. Теперь на все товары
 * один этот контроллер: он находит товар по адресу и отдаёт общий шаблон.
 * Правило в .htaccess направляет сюда любой адрес /products/<что-угодно>/.
 */

declare(strict_types=1);

require_once __DIR__ . '/../cms/repo.php';

/** Slug из адреса или из параметра ?slug= (его подставляет .htaccess). */
function page_product_slug(): string
{
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        return mb_strtolower($slug);
    }

    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    return preg_match('~/products/([^/]+)/?~', $path, $m) ? mb_strtolower($m[1]) : '';
}

$slug = page_product_slug();
$product = $slug !== '' ? repo_product($slug) : null;

// Товар переименовали — уводим на новый адрес 301-м редиректом, чтобы не
// терять позиции в поиске и внешние ссылки.
if (!$product && $slug !== '') {
    $to = cms_value('SELECT to_path FROM redirects WHERE from_path = ? AND is_active = 1', ['/products/' . $slug . '/']);
    if ($to) {
        cms_query('UPDATE redirects SET hits = hits + 1 WHERE from_path = ?', ['/products/' . $slug . '/']);
        header('Location: ' . $to, true, 301);
        exit;
    }
}

// Неопубликованный товар доступен только по ссылке предпросмотра из CMS.
if ($product && (int)$product['is_published'] !== 1) {
    $expected = hash_hmac('sha256', 'product:' . $product['id'], (string)(cms_config()['preview_secret'] ?? ''));
    if (!hash_equals($expected, (string)($_GET['preview'] ?? ''))) {
        $product = null;
    }
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Товар не найден | Comp-Uter';
    $noindex = true;
    require __DIR__ . '/../templates/header.php';
    ?>
    <main class="product-page product-not-found">
      <section class="product-page-card">
        <p class="section-label">Comp-Uter</p>
        <h1>Товар не найден</h1>
        <p>Проверьте ссылку или вернитесь в каталог процессоров Intel Xeon и жестких дисков Seagate.</p>
        <a class="button primary" href="/#stock">В каталог</a>
      </section>
    </main>
    <?php
    require __DIR__ . '/../templates/footer.php';
    exit;
}

require __DIR__ . '/../templates/product.php';
