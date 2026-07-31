<?php
/**
 * Страница категории — ОДИН шаблон на все категории.
 *
 * Товары, тексты и SEO приходят из базы, поэтому новая категория появляется
 * на сайте без единой строки кода: достаточно создать её в CMS.
 *
 * Ожидает $category (строка таблицы categories).
 */

require_once __DIR__ . '/../cms/repo.php';

$products = repo_products(['category' => (int)$category['id']]);

$pageTitle       = $category['seo_title'] ?: ($category['name'] . ' | Comp-Uter');
$pageDescription = $category['seo_desc'] ?: $category['lead'];
$ogTitle         = $category['og_title'] ?: $pageTitle;
$ogDescription   = $category['og_desc'] ?: $pageDescription;
$canonical       = repo_category_url($category);
$noindex         = (int)$category['noindex'] === 1;
$bodyScripts     = [
    '/src/nav.js?v=2',
    '/src/scroll-lock.js?v=3',
    '/src/gallery.js?v=3',
    '/src/main.js?v=6',
];

// На страницах категорий стили подключены после <title>, а не до иконки, —
// сохраняем прежний порядок тегов, чтобы документ не менялся вовсе.
$headAssets = '';
$headTail   = "    <link rel=\"preconnect\" href=\"https://mc.yandex.ru\" />\n"
    . "    <link rel=\"stylesheet\" href=\"/src/styles.css?v=5\" />\n";

$showCart = true;
$footerText = $category['footer_text'] ?: null;
$showOverlays = true;
$footerCategories = true;

// Список товаров категории и хлебные крошки для поисковых систем.
$jsonLd = [[
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => cms_absolute_url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $category['name'], 'item' => cms_absolute_url(repo_category_url($category))],
    ],], [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => (string)($category['list_name'] ?: $category['name']),
    'description'     => (string)($category['list_desc'] ?: $category['lead']),
    'itemListElement' => array_map(static function ($item, $i) {
        return [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            // В списке использовалось короткое название карточки, а не
            // полное имя товара из фида.
            'name'     => (string)($item['card_title'] ?: $item['short_name'] ?: $item['name']),
            'url'      => cms_absolute_url(repo_product_url($item)),
        ];
    }, $products, array_keys($products)),
]];

require __DIR__ . '/header.php';
require __DIR__ . '/partials/cart.php';
?>
    <main class="category-page">
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= e($category['name']) ?></span>
      </nav>

      <section class="section category-intro">
        <p class="section-label"><?= e((string)($category['label'] ?: 'Каталог')) ?></p>
        <h1><?= e((string)($category['h1'] ?: $category['name'])) ?></h1>
        <?php if ($category['lead']): ?><p><?= e((string)$category['lead']) ?></p><?php endif; ?>
        <?php if ($category['selection_text']): ?><p><?= e((string)$category['selection_text']) ?></p><?php endif; ?>
        <div class="category-intro-actions">
          <a class="button primary request-link" href="/#selection" data-goal="<?= e((string)$category['request_goal']) ?>">Помощь в подборе</a>
          <a class="button secondary" href="/#testing">Проверка совместимости</a>
        </div>
      </section>

      <section class="section inventory" id="<?= e((string)($category['grid_anchor'] ?: 'stock')) ?>" aria-labelledby="cat-grid-title">
        <div class="section-heading">
          <h2 id="cat-grid-title"><?= e((string)($category['stock_title'] ?: $category['name'] . ' в наличии')) ?></h2>
        </div>
        <div class="model-grid">
          <?php foreach ($products as $card): ?>
<?php require __DIR__ . '/partials/product-card.php'; ?>
          <?php endforeach; ?>
          <article class="model-card request-card">
            <div class="chip-sketch ghost" aria-hidden="true"><span><?= e((string)$category['request_badge']) ?></span></div>
            <span><?= e((string)$category['request_status']) ?></span>
            <h3><?= e((string)$category['request_title']) ?></h3>
            <p><?= e((string)$category['request_text']) ?></p>
            <ul class="model-tags">
              <?php foreach (array_filter(array_map('trim', explode(',', (string)$category['request_tags']))) as $rtag): ?><li><?= e($rtag) ?></li>
              <?php endforeach; ?>
            </ul>
            <a class="model-detail-button request-link" href="/#order" data-goal="<?= e((string)$category['request_goal']) ?>"><?= e((string)$category['request_button']) ?></a>
          </article>
        
        </div>
      </section>

      <section class="section category-crosslink">
        <div class="category-split">
          <?php foreach (repo_categories() as $other): if ((int)$other['id'] === (int)$category['id']) continue; ?>
          <article class="category-card">
            <p class="section-label">Другая категория</p>
            <h2><?= e($other['name']) ?></h2>
            <p><?= e((string)($other['cross_text'] ?: $other['lead'])) ?></p>
            <a class="button primary" href="<?= e(repo_category_url($other)) ?>"><?= e((string)($other['cross_button'] ?: 'Смотреть ' . mb_strtolower($other['name']))) ?></a>
          </article>
          <?php endforeach; ?>
          <article class="category-card">
            <p class="section-label">Не нашли нужное</p>
            <h2>Подберем под вашу систему</h2>
            <p>
              Напишите модель сервера, материнской платы или NAS и задачу — предложим подходящие позиции
              из наличия и поможем сверить совместимость.
            </p>
            <a class="button primary" href="/#order">Оставить заявку</a>
          </article>
        </div>
      </section>
    </main>
<?php require __DIR__ . '/footer.php'; ?>
