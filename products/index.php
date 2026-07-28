<?php

declare(strict_types=1);

$products = require __DIR__ . '/../yandexmarket/products.php';
require_once __DIR__ . '/../src/prices.php';
require_once __DIR__ . '/../src/images.php';

function page_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page_absolute_url(string $path): string
{
    // Product images may already be absolute (imported from an external CDN);
    // prefixing those again yields https://comp-uter.ru/https://cdn... which
    // breaks og:image and the Schema.org `image` field.
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }

    return 'https://comp-uter.ru/' . ltrim($path, '/');
}

function page_product_slug(): string
{
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        return strtolower($slug);
    }

    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    if (preg_match('~/products/([^/]+)/?~', $path, $matches)) {
        return strtolower((string)$matches[1]);
    }

    return '';
}

$slug = page_product_slug();
$product = null;

foreach ($products as $entry) {
    if (strtolower((string)$entry['slug']) === $slug) {
        $product = $entry;
        break;
    }
}

if (!$product) {
    http_response_code(404);
    $title = 'Товар не найден | Comp-Uter';
    ?>
<!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <script src="/src/scroll-restore.js?v=1"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />
    <link rel="stylesheet" href="/src/styles.css?v=2" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title><?= page_text($title) ?></title>
  </head>
  <body>
    <main class="product-page product-not-found">
      <section class="product-page-card">
        <p class="section-label">Comp-Uter</p>
        <h1>Товар не найден</h1>
        <p>Проверьте ссылку или вернитесь в каталог процессоров Intel Xeon и жестких дисков Seagate.</p>
        <a class="button primary" href="/#stock">В каталог</a>
      </section>
    </main>
  </body>
</html>
    <?php
    exit;
}

$canonical = page_absolute_url('/products/' . $product['slug'] . '/');
$image = product_image_absolute((string)$product['slug']);
$displayName = trim(($product['display_prefix'] ?? '') . ' ' . $product['model']);
$categoryName = (string)($product['category'] ?? 'Процессоры Intel Xeon');
$categoryUrl = (string)($product['category_url'] ?? '/#stock');
$categoryId = (int)($product['category_id'] ?? 1);
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['description'],
    'image' => $image,
    'sku' => (string)$product['id'],
    'mpn' => (string)($product['mpn'] ?? $product['model']),
    'url' => $canonical,
    'brand' => [
        '@type' => 'Brand',
        'name' => (string)($product['brand'] ?? 'Intel'),
    ],
    'category' => $categoryName,
    'additionalProperty' => [],
];

$offer = prices_offer_array((string)$product['slug'], $canonical);
if ($offer !== null) {
    $schema['offers'] = $offer;
}

foreach (($product['params'] ?? []) as $name => $value) {
    $schema['additionalProperty'][] = [
        '@type' => 'PropertyValue',
        'name' => (string)$name,
        'value' => (string)$value,
    ];
}

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => page_absolute_url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryName, 'item' => page_absolute_url($categoryUrl)],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $displayName, 'item' => $canonical],
    ],
];

$params = $product['params'] ?? [];
$featureText = (string)($params['Особенности'] ?? '');
$features = array_filter(array_map('trim', explode(',', $featureText)));
if (!$features) {
    $features = ['Turbo Boost', 'ECC', 'Virtualization Technology'];
}

/**
 * Related products: for a fallback-generated page (any product added to
 * yandexmarket/products.php without its own static folder under
 * products/<slug>/), pick the 3 closest models within the SAME category so
 * the cross-linking block stays meaningful without hand-authored curation.
 * CPUs are ranked by core count, HDDs by capacity — the two categories use
 * unrelated spec fields, so the ranking metric is chosen per category_id.
 */
$rankParam = $categoryId === 2 ? 'Объем' : 'Количество ядер';
$currentRank = (float)preg_replace('/[^\d.]+/', '', str_replace(',', '.', (string)($params[$rankParam] ?? '0')));
$others = array_values(array_filter(
    $products,
    static fn($entry) => $entry['slug'] !== $product['slug'] && (int)($entry['category_id'] ?? 1) === $categoryId
));
usort($others, static function ($a, $b) use ($currentRank, $rankParam) {
    $rankA = (float)preg_replace('/[^\d.]+/', '', str_replace(',', '.', (string)($a['params'][$rankParam] ?? '0')));
    $rankB = (float)preg_replace('/[^\d.]+/', '', str_replace(',', '.', (string)($b['params'][$rankParam] ?? '0')));
    return abs($rankA - $currentRank) <=> abs($rankB - $currentRank);
});
$related = array_slice($others, 0, 3);
$relatedHeading = $categoryId === 2 ? 'Похожие жесткие диски' : 'Похожие процессоры';

/**
 * Generic FAQ derived only from the same $params data used above — no
 * invented specifics. When a real static page is authored for this product
 * (see products/e5-2667-v3/index.html or products/st10000nm0016/index.html
 * for the pattern), replace this with a hand-written FAQ and a full SEO
 * article the same way it was done for the existing models.
 */
$faq = [];
if ($categoryId === 2) {
    $volumeText = (string)($params['Объем'] ?? '');
    $interfaceText = (string)($params['Интерфейс'] ?? 'SATA III');
    $raidText = (string)($params['Поддержка RAID'] ?? '');
    if ($volumeText !== '') {
        $faq[] = [
            'q' => 'Какой объём у ' . $product['model'] . '?',
            'a' => $volumeText . '.',
        ];
    }
    $faq[] = [
        'q' => 'Какой интерфейс подключения у этой модели?',
        'a' => $interfaceText . '. Совместим с большинством серверных плат, NAS-корпусов и системных блоков с портами SATA.',
    ];
    if ($raidText !== '') {
        $faq[] = [
            'q' => 'Подходит ли этот диск для RAID-массива?',
            'a' => 'Да, диск поддерживает конфигурации ' . $raidText . '.',
        ];
    }
} else {
    $coresText = (string)($params['Количество ядер'] ?? '');
    $threadsText = (string)($params['Количество потоков'] ?? '');
    $socketText = (string)($params['Сокет'] ?? 'LGA 2011-3');
    $hasIntegratedGraphics = stripos($featureText, 'графика') !== false;
    if ($coresText !== '' && $threadsText !== '') {
        $faq[] = [
            'q' => 'Сколько ядер и потоков у ' . $product['model'] . '?',
            'a' => $coresText . ' ядер и ' . $threadsText . ' потоков.',
        ];
    }
    $faq[] = [
        'q' => 'Какой сокет и платформа нужны для ' . $product['model'] . '?',
        'a' => 'Сокет ' . $socketText . '. Перед покупкой сверьтесь со списком поддерживаемых процессоров у конкретной модели материнской платы и обновите BIOS.',
    ];
    $faq[] = [
        'q' => 'Нужна ли отдельная видеокарта?',
        'a' => $hasIntegratedGraphics
            ? 'У этой модели есть встроенная графика, но для требовательных задач дискретная видеокарта всё равно рекомендуется.'
            : 'Да, встроенной графики нет — для вывода изображения нужна дискретная видеокарта.',
    ];
}

$faqSchema = null;
if ($faq) {
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static function ($item) {
            return [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ];
        }, $faq),
    ];
}

?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <script src="/src/scroll-restore.js?v=1"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="yandex-verification" content="624ceca67ba02e2a" />
    <meta name="description" content="<?= page_text((string)$product['description']) ?>" />
    <meta property="og:title" content="<?= page_text((string)$product['name']) ?>" />
    <meta property="og:description" content="<?= page_text((string)$product['description']) ?>" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="<?= page_text($canonical) ?>" />
    <meta property="og:site_name" content="Comp-Uter" />
    <meta property="og:image" content="<?= page_text($image) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= page_text((string)$product['name']) ?>" />
    <meta name="twitter:description" content="<?= page_text((string)$product['description']) ?>" />
    <meta name="twitter:image" content="<?= page_text($image) ?>" />
    <link rel="canonical" href="<?= page_text($canonical) ?>" />
    <link rel="preconnect" href="https://mc.yandex.ru" />
    <link rel="stylesheet" href="/src/styles.css?v=2" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title><?= page_text($displayName) ?> купить | Comp-Uter</title>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php if ($faqSchema): ?>
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
  </head>
  <body>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
      (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
      })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=110948351', 'ym');

      ym(110948351, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/110948351" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->

    <header class="site-header">
      <a class="brand" href="/" aria-label="Comp-Uter">
        <span class="brand-image brand-image-wordmark">
          <img src="/public/assets/comp-uter-logo-wordmark.webp" alt="" />
        </span>
      </a>
      <div class="nav-panel" id="site-nav-panel" data-nav-panel>
        <nav aria-label="Основная навигация">
          <div class="nav-group" data-nav-group>
            <button
              class="nav-group-trigger"
              type="button"
              data-nav-group-trigger
              aria-expanded="false"
              aria-controls="catalog-menu"
            >
              Каталог
              <span class="nav-group-caret" aria-hidden="true"></span>
            </button>
            <div class="nav-group-menu" id="catalog-menu" data-nav-group-menu>
              <a href="/processors/">Серверные процессоры</a>
              <a href="/drives/">Диски и накопители</a>
            </div>
          </div>
          <a href="/#selection">Подбор</a>
          <a href="/#testing">Совместимость</a>
          <a href="/#delivery">Доставка</a>
          <a href="/#contact">Контакты</a>
        </nav>
        <div class="header-contacts" aria-label="Контакты отдела продаж">
          <span>Отдел продаж</span>
          <a class="header-phone" href="tel:+74993221311">+7 (499) 322-13-11</a>
          <a class="header-mail" href="mailto:info@comp-uter.ru">info@comp-uter.ru</a>
        </div>
        <a class="header-action" href="/#order">Заказать</a>
      </div>
      <button
        class="nav-toggle"
        type="button"
        data-nav-toggle
        aria-expanded="false"
        aria-controls="site-nav-panel"
        aria-label="Открыть меню"
      >
        <span></span>
      </button>
    </header>

    <main class="product-page">
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a>
        <span aria-hidden="true">/</span>
        <a href="<?= page_text($categoryUrl) ?>"><?= page_text($categoryName) ?></a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= page_text($displayName) ?></span>
      </nav>
      <article class="product-page-card product-dialog">
        <div class="product-dialog-media">
          <?= product_image_tag((string)$product['slug'], (string)$product['name']) ?>
        </div>
        <div class="product-dialog-copy">
          <p class="section-label">Карточка товара</p>
          <h1><?= page_text($displayName) ?></h1>
          <?= prices_page_html((string)$product['slug']) ?>

          <p class="product-dialog-lead"><?= page_text((string)$product['description']) ?></p>
          <dl class="product-dialog-specs">
            <?php foreach ($params as $name => $value): ?>
              <div>
                <dt><?= page_text((string)$name) ?></dt>
                <dd><?= page_text((string)$value) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          <ul class="product-dialog-tags">
            <?php foreach ($features as $feature): ?>
              <li><?= page_text($feature) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="product-page-actions">
            <a class="button primary product-dialog-order" href="/#order">Оформить заказ</a>
            <a class="button secondary" href="<?= page_text($categoryUrl) ?>" data-back-to-catalog>Вернуться в каталог</a>
          </div>
        </div>
      </article>

      <?php if ($related): ?>
      <section class="related-products" aria-labelledby="related-products-title">
        <h2 id="related-products-title"><?= page_text($relatedHeading) ?></h2>
        <div class="related-products-grid">
          <?php foreach ($related as $item): ?>
            <a class="related-product-card" href="<?= page_text('/products/' . $item['slug'] . '/') ?>">
              <h3><?= page_text(trim(($item['display_prefix'] ?? '') . ' ' . $item['model'])) ?></h3>
              <?= prices_related_html((string)$item['slug']) ?>

              <p><?= page_text((string)$item['description']) ?></p>
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
              <summary><?= page_text($item['q']) ?></summary>
              <p><?= page_text($item['a']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </main>

    <footer class="site-footer">
      <div class="footer-brand">
        <a class="brand footer-logo" href="/" aria-label="Comp-Uter">
          <span class="brand-image brand-image-full">
            <img src="/public/assets/comp-uter-logo-full.webp" alt="" />
          </span>
        </a>
        <p>Процессоры Intel Xeon и жесткие диски Seagate для серверных платформ, X99, рабочих станций, домашних сборок и корпоративных закупок.</p>
      </div>
      <div class="footer-links">
        <a href="/privacy_policy/">Политика конфиденциальности</a>
        <a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a>
        <a href="/#requisites">Реквизиты для счета</a>
        <a href="/#order">Оформить заказ</a>
      </div>
      <div class="footer-bottom">
        <span>ИП Михайловский В.Г. · +7 (499) 322-13-11 · info@comp-uter.ru</span>
        <span>© 2026 Comp-Uter. Все права защищены. Копирование материалов сайта запрещено.</span>
      </div>
    </footer>
    <script src="/src/nav.js?v=2"></script>
    <script src="/src/product-page.js?v=2"></script>
  </body>
</html>
