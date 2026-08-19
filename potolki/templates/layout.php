<?php
/**
 * Общий каркас страницы.
 *
 * Переменная $content приходит из render(): шаблон страницы уже отрисован
 * в буфер, поэтому к этому моменту известны title, description и крошки.
 */
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F5F2EC">

    <?php include APP_ROOT . '/includes/seo.php'; ?>

    <link rel="preload" href="<?= e(asset('fonts/onest-cyrillic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(asset('fonts/manrope-cyrillic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
    <link rel="icon" href="<?= e(asset('icons/favicon.svg')) ?>" type="image/svg+xml">

    <?php include APP_ROOT . '/includes/schema.php'; ?>

    <?php if (config('features.analytics') && filled(config('analytics.yandex_metrika_id'))): ?>
    <script>
        /* Идентификаторы аналитики. Сам скрипт загружается только после
           согласия в cookie-баннере — см. assets/js/app.js */
        window.zubrAnalytics = {
            metrika: <?= (int) config('analytics.yandex_metrika_id') ?>,
            webvisor: <?= config('analytics.webvisor') ? 'true' : 'false' ?>
        };
    </script>
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Перейти к содержанию</a>

<?php if (diagnostics_enabled()): $issues = diagnostics_issues(); ?>
    <?php if ($issues !== []): ?>
    <div class="diag">
        <div class="container">
            <strong>Режим диагностики (виден только вам).</strong> Данные, которые нужно заполнить перед публикацией:
            <ul>
                <?php foreach ($issues as $issue): ?><li><?= e($issue) ?></li><?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php include APP_ROOT . '/includes/header.php'; ?>

<?php include APP_ROOT . '/includes/breadcrumbs.php'; ?>

<main id="main">
    <?= $content ?>
</main>

<?php include APP_ROOT . '/includes/footer.php'; ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if (!empty($needsCalculator)): ?>
<script src="<?= e(asset('js/calculator.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
