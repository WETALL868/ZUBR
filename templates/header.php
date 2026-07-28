<?php
/**
 * Общая шапка сайта.
 *
 * Раньше эта разметка была скопирована в каждую из 15 страниц. Теперь она
 * здесь одна: правка меню или телефона применяется ко всему сайту сразу.
 *
 * Ожидает переменные (все необязательные):
 *   $pageTitle, $pageDescription, $pageKeywords, $canonical, $ogTitle,
 *   $ogDescription, $ogImage, $ogType, $noindex, $jsonLd (массив блоков
 *   микроразметки), $bodyScripts (список путей к скриптам страницы)
 *
 * Подключение стилей у главной и у остальных страниц разное: на главной
 * критический CSS встроен в документ, а общий файл подгружается асинхронно.
 * Поэтому блок ссылок на ресурсы вынесен в две точки:
 *   $headAssets — перед иконкой (по умолчанию preconnect + styles.css);
 *   $headTail   — после <title>, до микроразметки.
 */
require_once __DIR__ . '/../cms/repo.php';

$pageTitle       = $pageTitle       ?? cms_setting('seo', 'default_title', 'Comp-Uter');
$pageDescription = $pageDescription ?? cms_setting('seo', 'default_description', '');
$ogType          = $ogType          ?? 'website';
$ogTitle         = $ogTitle         ?? $pageTitle;
$ogDescription   = $ogDescription   ?? $pageDescription;
$ogImage         = $ogImage         ?? cms_absolute_url('/public/assets/xeon-hero.webp');
$jsonLd          = $jsonLd          ?? [];
$headerBrandHref = $headerBrandHref ?? '/';
$headerAnchor    = $headerAnchor    ?? '/';
$headAssets      = $headAssets      ?? "    <link rel=\"preconnect\" href=\"https://mc.yandex.ru\" />\n    <link rel=\"stylesheet\" href=\"/src/styles.css?v=3\" />\n";
$headTail        = $headTail        ?? '';
?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <script src="/src/scroll-restore.js?v=1"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="yandex-verification" content="<?= e((string)cms_setting('seo', 'yandex_verification', '624ceca67ba02e2a')) ?>" />
    <meta name="description" content="<?= e((string)$pageDescription) ?>" />
    <?php if (!empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow" />
    <?php endif; ?>
    <?php if (!empty($pageKeywords)): ?>
    <meta name="keywords" content="<?= e((string)$pageKeywords) ?>" />
    <?php endif; ?>
    <meta property="og:title" content="<?= e((string)$ogTitle) ?>" />
    <meta property="og:description" content="<?= e((string)$ogDescription) ?>" />
    <meta property="og:type" content="<?= e($ogType) ?>" />
    <?php if (!empty($canonical)): ?>
    <meta property="og:url" content="<?= e(cms_absolute_url($canonical)) ?>" />
    <?php endif; ?>
    <meta property="og:site_name" content="<?= e((string)cms_setting('site', 'name', 'Comp-Uter')) ?>" />
    <meta property="og:image" content="<?= e($ogImage) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= e((string)($twitterTitle ?? $ogTitle)) ?>" />
    <meta name="twitter:description" content="<?= e((string)($twitterDescription ?? $ogDescription)) ?>" />
    <meta name="twitter:image" content="<?= e($ogImage) ?>" />
    <?php if (!empty($canonical)): ?>
    <link rel="canonical" href="<?= e(cms_absolute_url($canonical)) ?>" />
    <?php endif; ?>
<?= $headAssets ?>    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title><?= e((string)$pageTitle) ?></title>
<?= $headTail ?>    <?php foreach ($jsonLd as $block): ?>
    <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endforeach; ?>
  </head>
  <body>

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
      (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
      })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=<?= e((string)cms_setting('seo','metrika_id','110948351')) ?>', 'ym');

      ym(<?= e((string)cms_setting('seo','metrika_id','110948351')) ?>, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?= e((string)cms_setting('seo','metrika_id','110948351')) ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->

    <header class="site-header">
      <a class="brand" href="<?= e($headerBrandHref) ?>" aria-label="Comp-Uter">
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
              <?php foreach (repo_categories() as $navCategory): ?>
              <a href="<?= e(repo_category_url($navCategory)) ?>"><?= e($navCategory['name']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
          <a href="<?= e($headerAnchor) ?>#selection">Подбор</a>
          <a href="<?= e($headerAnchor) ?>#testing">Совместимость</a>
          <a href="<?= e($headerAnchor) ?>#delivery">Доставка</a>
          <a href="<?= e($headerAnchor) ?>#contact">Контакты</a>
        </nav>
        <div class="header-contacts" aria-label="Контакты отдела продаж">
          <span>Отдел продаж</span>
          <a class="header-phone" href="tel:<?= e((string)cms_setting('contacts','phone_raw','+74993221311')) ?>"><?= e((string)cms_setting('contacts','phone','+7 (499) 322-13-11')) ?></a>
          <a class="header-mail" href="mailto:<?= e((string)cms_setting('contacts','email','info@comp-uter.ru')) ?>"><?= e((string)cms_setting('contacts','email','info@comp-uter.ru')) ?></a>
        </div>
        <a class="header-action" href="<?= e($headerAnchor) ?>#order">Заказать</a>
      </div>
      <?php if (!empty($showCart)): ?>
      <button class="header-cart" type="button" data-cart-open aria-label="Открыть корзину">
        <span>Корзина</span>
        <strong data-cart-count>0</strong>
        <em data-cart-header-total>0 ₽</em>
      </button>
      <?php endif; ?>
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
