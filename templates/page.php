<?php
/**
 * Обычная страница сайта (политика, соглашение и любая новая, созданная
 * в CMS). Раньше каждая была отдельным файлом HTML, и правка телефона или
 * подвала требовала открыть их все.
 *
 * Ожидает $page (строка таблицы pages).
 */

require_once __DIR__ . '/../cms/repo.php';

$pageTitle       = $page['seo_title'] ?: $page['title'];
$pageDescription = $page['seo_desc'] ?: '';
$pageKeywords    = $page['keywords'] ?: '';
$ogTitle         = $page['og_title'] ?: $pageTitle;
$ogDescription   = $page['og_desc'] ?: $pageDescription;
$ogImage         = $page['og_image'] ? cms_absolute_url($page['og_image']) : cms_absolute_url('/public/assets/xeon-hero.webp');
$canonical       = $page['canonical'] ?: '/' . $page['slug'] . '/';
$noindex         = (int)$page['noindex'] === 1;

// Правовые страницы носят упрощённую шапку без каталога и корзины, свой
// класс на <body> и свой фон, который браузер начинает грузить заранее.
$isLegal       = $page['template'] === 'legal';
$headerVariant = $isLegal ? 'legal' : 'full';
$bodyClass     = $isLegal ? 'legal-body' : '';
$showVerification = !$isLegal;

$headAssets = '';
$headTail   = ($page['preload_image']
        ? '    <link rel="preload" as="image" href="' . e((string)$page['preload_image']) . "\" />\n"
        : '')
    . "    <link rel=\"preconnect\" href=\"https://mc.yandex.ru\" />\n"
    . "    <link rel=\"stylesheet\" href=\"/src/styles.css?v=4\" />\n";

// В подвале страница не ссылается сама на себя.
$footerSkipPage  = (string)$page['slug'];
$footerBrandHref = '/#top';

require __DIR__ . '/header.php';
?>
    <main class="<?= $isLegal ? 'legal-main' : 'page-main' ?>">
      <?= $page['content'] ?>
    </main>
<?php require __DIR__ . '/footer.php'; ?>
