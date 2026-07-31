<?php
/**
 * Главная страница.
 *
 * Собирается из строк таблицы home_blocks: порядок, видимость и текст каждой
 * секции меняются в административной панели без правки файлов. Две секции —
 * витрины процессоров и дисков — берут карточки из базы, поэтому новый товар
 * появляется на главной сам.
 *
 * Ожидает $page (строка таблицы pages со slug = '').
 */

require_once __DIR__ . '/../cms/repo.php';

$blocks = repo_home_blocks();

/* --------------------------------------------------------- микроразметка */

// Список товаров для поисковых систем строится из тех же витрин, что видит
// посетитель: разойтись они не могут.
$listed = [];
foreach ($blocks as $block) {
    if ($block['kind'] === 'products') {
        foreach (repo_home_block_products($block) as $item) {
            $listed[] = $item;
        }
    }
}

$jsonLd = [[
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => (string)cms_setting('home', 'list_name', ''),
    'description'     => (string)cms_setting('home', 'list_desc', ''),
    'itemListElement' => array_map(static function ($item, $i) {
        return [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => (string)($item['card_title'] ?: $item['short_name'] ?: $item['name']),
            'url'      => cms_absolute_url(repo_product_url($item)),
        ];
    }, $listed, array_keys($listed)),
]];

$business = array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'LocalBusiness',
    'name'        => (string)cms_setting('site', 'name', ''),
    'legalName'   => (string)cms_setting('business', 'legal_name', ''),
    'url'         => cms_absolute_url('/'),
    'description' => (string)cms_setting('business', 'description', ''),
    'email'       => (string)cms_setting('contacts', 'email', ''),
    'telephone'   => (string)cms_setting('contacts', 'phone_raw', ''),
    'taxID'       => (string)cms_setting('business', 'tax_id', ''),
], static fn($v) => $v !== '');

$business['address'] = array_filter([
    '@type'           => 'PostalAddress',
    'streetAddress'   => (string)cms_setting('business', 'street', ''),
    'addressLocality' => (string)cms_setting('business', 'locality', ''),
    'postalCode'      => (string)cms_setting('business', 'postal_code', ''),
    'addressCountry'  => (string)cms_setting('business', 'country', 'RU'),
], static fn($v) => $v !== '');

if ($hours = (string)cms_setting('business', 'opening_hours', '')) {
    $business['openingHours'] = $hours;
}
$jsonLd[] = $business;

// Вопросы и ответы для микроразметки берутся из того же блока, который
// показан на странице: отдельного списка, способного разойтись с видимым
// текстом, здесь нет.
$faqPairs = repo_home_faq($blocks);
if ($faqPairs) {
    $jsonLd[] = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(static fn($pair) => [
            '@type'          => 'Question',
            'name'           => $pair['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $pair['answer']],
        ], $faqPairs),
    ];
}

/* ------------------------------------------------------------ метаданные */

$pageTitle       = $page['seo_title'] ?: (string)cms_setting('seo', 'default_title', 'Comp-Uter');
$pageDescription = $page['seo_desc'] ?: '';
$pageKeywords    = $page['keywords'] ?: '';
$ogTitle         = $page['og_title'] ?: $pageTitle;
$ogDescription   = $page['og_desc'] ?: $pageDescription;
$ogImage         = $page['og_image'] ?: cms_absolute_url('/public/assets/xeon-hero.webp');
$twitterTitle       = $page['tw_title'] ?: null;
$twitterDescription = $page['tw_desc'] ?: null;
$canonical       = '/';
$noindex         = (int)$page['noindex'] === 1;
$showCart        = true;

// Главная — единственная страница, где критический CSS встроен в документ,
// а общий файл стилей подгружается асинхронно: это заметно ускоряет первую
// отрисовку первого экрана.
$criticalCss = (string)@file_get_contents(CMS_ROOT . '/src/critical-home.css');

$headAssets = "    <link rel=\"sitemap\" type=\"application/xml\" href=\"/sitemap.xml\" />\n";
$headTail   = "    <link rel=\"preconnect\" href=\"https://mc.yandex.ru\" />\n"
    . "    <link rel=\"dns-prefetch\" href=\"https://mc.yandex.ru\" />\n"
    . "    <link rel=\"preload\" as=\"image\" href=\"/public/assets/xeon-hero.webp\" fetchpriority=\"high\" />\n"
    . "    <style>\n" . $criticalCss . "    </style>\n"
    . "    <script src=\"/src/anchor-after-css.js?v=1\"></script>\n"
    . "    <link rel=\"preload\" href=\"/src/styles.css?v=4\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet';window.compUterAnchorAfterCss&amp;&amp;window.compUterAnchorAfterCss()\" />\n"
    . "    <noscript><link rel=\"stylesheet\" href=\"/src/styles.css?v=4\" /></noscript>\n";

$bodyScripts = [
    '/src/nav.js?v=2',
    '/src/scroll-lock.js?v=3',
    '/src/gallery.js?v=3',
    '/src/main.js?v=5',
];

// На главной логотипы ведут наверх, а якоря указываются без слэша: ссылка
// «/#order» с самой главной перезагружала бы страницу.
$headerBrandHref = '#top';
$headerAnchor    = '';
$footerBrandHref = '#top';
$footerAnchor    = '';
$footerText      = cms_setting('home', 'footer_text', null);
$footerCategories = true;

require __DIR__ . '/header.php';
require __DIR__ . '/partials/cart.php';
?>
    <main id="top">
<?php foreach ($blocks as $block): ?>
<?php if ($block['kind'] !== 'products') { echo $block['body']; continue; } ?>
<?= $block['body'] ?>
<?php foreach (repo_home_block_products($block) as $card): ?>
<?php require __DIR__ . '/partials/product-card.php'; ?>
<?php endforeach; ?>
<?= $block['body_after'] ?>
<?php endforeach; ?>
    </main>

<?php require __DIR__ . '/partials/overlays.php'; ?>
<?php require __DIR__ . '/footer.php'; ?>
