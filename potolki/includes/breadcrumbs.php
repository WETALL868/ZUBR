<?php
/**
 * Хлебные крошки. Разметка BreadcrumbList формируется отдельно
 * в includes/schema.php из того же массива — расхождение исключено.
 */

$crumbs = seo()['breadcrumbs'] ?? [];

if ($crumbs === []) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Вы находитесь здесь">
    <div class="container">
        <ol>
            <?php foreach ($crumbs as $index => $crumb): ?>
                <li>
                    <?php if ($index === array_key_last($crumbs)): ?>
                        <span aria-current="page"><?= e($crumb['title']) ?></span>
                    <?php else: ?>
                        <a href="<?= e(site_url($crumb['url'])) ?>"><?= e($crumb['title']) ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
