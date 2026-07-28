<?php
/**
 * Страница товара — ОДИН шаблон на все товары.
 *
 * Раньше на каждый товар существовал отдельный файл на ~310 строк, и любая
 * правка шапки, подвала или разметки требовала повторить её двенадцать раз.
 * Теперь товар описан строкой в базе, а разметка — здесь.
 *
 * Ожидает $product (строка таблицы products).
 */

require_once __DIR__ . '/../cms/repo.php';

$productId  = (int)$product['id'];
$params     = repo_product_params($productId);
$faq        = repo_product_faq($productId);
$related    = repo_product_related($productId);
$category   = repo_category_by_id($product['category_id'] ? (int)$product['category_id'] : null);
$price      = repo_price($product);
$oldPrice   = repo_old_price($product);
$canonical  = repo_product_url($product);
$imageAbs   = cms_absolute_url(repo_image_original($product));
$displayName = $product['short_name'] ?: $product['name'];

/* ------------------------------------------------------------ микроразметка */

$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $product['name'],
    'description' => (string)$product['description'],
    'image'       => $imageAbs,
    'sku'         => (string)$product['sku'],
    'mpn'         => (string)($product['mpn'] ?: $product['sku']),
    'url'         => cms_absolute_url($canonical),
    'brand'       => ['@type' => 'Brand', 'name' => (string)($product['brand'] ?: 'Intel')],
    'category'    => (string)($category['name'] ?? ''),
];

// Предложение выводится только с реальной ценой: Offer без price поисковики
// считают ошибкой разметки.
if ($price !== null) {
    $offer = [
        '@type'         => 'Offer',
        'url'           => cms_absolute_url($canonical),
        'price'         => cms_money_machine($price),
        'priceCurrency' => 'RUB',
        'availability'  => REPO_STOCK_SCHEMA[$product['availability']] ?? REPO_STOCK_SCHEMA['in_stock'],
        'itemCondition' => 'https://schema.org/NewCondition',
    ];
    if ($oldPrice !== null) {
        $offer['priceSpecification'] = [
            '@type'         => 'UnitPriceSpecification',
            'priceType'     => 'https://schema.org/ListPrice',
            'price'         => cms_money_machine($oldPrice),
            'priceCurrency' => 'RUB',
        ];
    }
    $schema['offers'] = $offer;
}

$schema['additionalProperty'] = [];
foreach ($params as $name => $value) {
    $schema['additionalProperty'][] = ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value];
}

$jsonLd = [$schema, [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => cms_absolute_url('/')],
        $category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category['name'], 'item' => cms_absolute_url(repo_category_url($category))] : null,
        // Имя в микроразметке хранится отдельным полем: на прежних страницах
        // оно не всегда совпадало с видимой крошкой, и менять его — значит
        // менять уже проиндексированные данные.
        ['@type' => 'ListItem', 'position' => 3, 'name' => (string)($product['breadcrumb'] ?: $displayName), 'item' => cms_absolute_url($canonical)],
    ])),
]];

if ($faq) {
    $jsonLd[] = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(static fn($item) => [
            '@type'          => 'Question',
            'name'           => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ], $faq),
    ];
}

/* ------------------------------------------------------------- метаданные */

$pageTitle       = $product['seo_title'] ?: ($displayName . ' купить | Comp-Uter');
$pageDescription = $product['seo_desc'] ?: $product['description'];
$ogTitle         = $product['og_title'] ?: $product['name'];
$ogDescription   = $product['og_desc'] ?: $product['description'];
$ogImage         = $product['og_image'] ? cms_absolute_url($product['og_image']) : $imageAbs;
$ogType          = 'product';
$noindex         = (int)$product['noindex'] === 1;
$bodyScripts     = [
    '/src/nav.js?v=2',
    '/src/cpu-article.js?v=2',
    '/src/scroll-lock.js?v=3',
    '/src/gallery.js?v=3',
    '/src/product-page.js?v=3',
];

require __DIR__ . '/header.php';
?>
    <main class="product-page">
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a>
        <?php if ($category): ?>
        <span aria-hidden="true">/</span>
        <a href="<?= e(repo_category_url($category)) ?>"><?= e($category['name']) ?></a>
        <?php endif; ?>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= e((string)$displayName) ?></span>
      </nav>
      <article class="product-page-card product-dialog">
        <div class="product-dialog-media">
          <?= repo_image_tag($product, $product['name'], false, '(max-width: 900px) 92vw, 520px') ?>

        </div>
        <div class="product-dialog-copy">
          <p class="section-label">Карточка товара</p>
          <h1><?= e((string)($product['seo_h1'] ?: $displayName)) ?></h1>
          <p class="product-dialog-price<?= $price === null ? ' price-unknown' : '' ?>"><?= e(cms_money($price)) ?><?php
            if ($oldPrice !== null): ?> <s class="price-old"><?= e(cms_money($oldPrice)) ?></s><?php endif; ?></p>
          <p class="product-dialog-lead"><?= e((string)($product['lead'] ?: $product['description'])) ?></p>
          <?php if ($params): ?>
          <dl class="product-dialog-specs">
            <?php foreach ($params as $name => $value): ?>
              <div>
                <dt><?= e((string)$name) ?></dt>
                <dd><?= e((string)$value) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          <?php endif; ?>
          <?php
            $features = array_values(array_filter(array_map('trim', explode(',', (string)($params['Особенности'] ?? '')))));
            if (!$features) {
                $features = ['Turbo Boost', 'ECC', 'Virtualization Technology'];
            }
          ?>
          <ul class="product-dialog-tags">
            <?php foreach ($features as $feature): ?>
              <li><?= e($feature) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="product-page-actions">
            <a class="button primary product-dialog-order" href="/#order">Оформить заказ</a>
            <a class="button secondary" href="<?= e($category ? repo_category_url($category) : '/#stock') ?>" data-back-to-catalog>Вернуться в каталог</a>
          </div>
        </div>
      </article>

      <?php if (!empty($product['article_html'])): ?>
      <section class="cpu-article" aria-labelledby="cpu-article-title">
        <h2 id="cpu-article-title"><?= e((string)($product['article_title'] ?: 'Подробное описание')) ?></h2>
        <div class="cpu-article-body" data-article-body>
          <?= $product['article_html'] ?>
        </div>
        <button type="button" class="cpu-article-toggle" data-article-toggle aria-expanded="false">
          <span data-toggle-label>Показать полностью</span>
        </button>
      </section>
      <?php endif; ?>

      <?php if ($related): ?>
      <section class="related-products" aria-labelledby="related-products-title">
        <h2 id="related-products-title"><?= e((string)(($category['slug'] ?? '') === 'drives' ? 'Похожие жесткие диски' : 'Похожие процессоры')) ?></h2>
        <div class="related-products-grid">
          <?php foreach ($related as $item):
              $itemPrice = repo_price($item);
              $itemOld   = repo_old_price($item);
              // Подпись объясняет отличие от текущего товара и написана
              // вручную; если её нет — показываем описание товара.
              $itemNote  = $item['note'] ?? null; ?>
            <a class="related-product-card" href="<?= e(repo_product_url($item)) ?>">
              <h3><?= e((string)($item['short_name'] ?: $item['name'])) ?></h3>
              <span class="related-price<?= $itemPrice === null ? ' price-unknown' : '' ?>"><?= e(cms_money($itemPrice)) ?><?php
                if ($itemOld !== null): ?> <s class="price-old"><?= e(cms_money($itemOld)) ?></s><?php endif; ?></span>
              <p><?= e((string)($itemNote ?: $item['description'])) ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($faq): ?>
      <section class="faq" aria-labelledby="faq-title">
        <h2 id="faq-title">Частые вопросы</h2>
        <div class="faq-list">
          <?php foreach ($faq as $item): ?>
            <details class="faq-item">
              <summary><?= e($item['question']) ?></summary>
              <p><?= e($item['answer']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </main>
<?php require __DIR__ . '/footer.php'; ?>
