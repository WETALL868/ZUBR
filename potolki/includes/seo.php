<?php
/**
 * Мета-теги страницы.
 *
 * Данные берутся из состояния seo(), которое задаёт шаблон страницы.
 * Здесь же собирается canonical: всегда на собственный URL страницы,
 * без параметров запроса — фильтры и метки не должны плодить дубли.
 */

$state = seo();

$brand = (string) config('brand.name');
$title = filled($state['title']) ? (string) $state['title'] : $brand;
$description = (string) ($state['description'] ?? '');
$canonical = filled($state['canonical']) ? (string) $state['canonical'] : absolute_url(request_path());
$robots = (string) ($state['robots'] ?? 'index, follow');

// Черновой домен и режим разработки индексировать нельзя.
if (config('runtime.env') === 'development' || str_contains((string) config('site.domain'), 'example')) {
    $robots = 'noindex, nofollow';
}
?>
    <title><?= e($title) ?></title>
<?php if ($description !== ''): ?>
    <meta name="description" content="<?= e($description) ?>">
<?php endif; ?>
    <meta name="robots" content="<?= e($robots) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($brand) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
<?php if ($description !== ''): ?>
    <meta property="og:description" content="<?= e($description) ?>">
<?php endif; ?>
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="ru_RU">
<?php if (filled($state['og_image'])): ?>
    <meta property="og:image" content="<?= e((string) $state['og_image']) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php else: ?>
    <meta name="twitter:card" content="summary">
<?php endif; ?>
    <meta name="twitter:title" content="<?= e($title) ?>">
<?php if ($description !== ''): ?>
    <meta name="twitter:description" content="<?= e($description) ?>">
<?php endif; ?>
