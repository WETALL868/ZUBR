<?php

/**
 * Главная страница.
 *
 * Вся её начинка — семнадцать секций в таблице home_blocks и витрины товаров
 * из каталога. Здесь остаётся только выбор шаблона: сам файл ничего не знает
 * ни о ценах, ни о текстах.
 */

declare(strict_types=1);

require_once __DIR__ . '/cms/repo.php';

$page = repo_page('');

if (!$page) {
    // База ещё не заполнена — показываем страницу-заглушку, а не белый экран.
    http_response_code(503);
    $pageTitle = 'Сайт готовится к работе | Comp-Uter';
    $noindex = true;
    require __DIR__ . '/templates/header.php';
    echo '<main class="product-page product-not-found"><section class="product-page-card">'
       . '<h1>Сайт готовится к работе</h1>'
       . '<p>Данные каталога ещё не перенесены в базу. Выполните установку: <code>php cms/install/migrate.php --apply</code>.</p>'
       . '</section></main>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

require __DIR__ . '/templates/home.php';
