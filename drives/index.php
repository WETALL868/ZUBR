<?php

/**
 * Страница категории «drives».
 *
 * Весь вывод собирает общий шаблон templates/category.php по данным из базы:
 * тексты, SEO и список товаров редактируются в CMS. Файл существует только
 * ради сохранения прежнего адреса /drives/.
 */

declare(strict_types=1);

require_once __DIR__ . '/../cms/repo.php';

$category = repo_category('drives');

if (!$category || (int)$category['is_published'] !== 1) {
    http_response_code(404);
    $pageTitle = 'Раздел не найден | Comp-Uter';
    $noindex = true;
    require __DIR__ . '/../templates/header.php';
    echo '<main class="product-page product-not-found"><section class="product-page-card">'
       . '<h1>Раздел не найден</h1><a class="button primary" href="/">На главную</a></section></main>';
    require __DIR__ . '/../templates/footer.php';
    exit;
}

require __DIR__ . '/../templates/category.php';
