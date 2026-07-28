<?php

/**
 * Единственная точка входа административной панели.
 *
 * Все экраны подключаются отсюда, поэтому проверка входа, токена формы и прав
 * написана один раз и её нельзя обойти, открыв внутренний файл напрямую.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// Панель не должна попадать в поисковую выдачу даже там, где .htaccess
// не читается (nginx, отключённый mod_headers).
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

admin_start_session();

/* ------------------------------------------------ база ещё не настроена */

if (!cms_is_installed()) {
    require __DIR__ . '/screens/setup.php';
    exit;
}

/* --------------------------------------------------------------- вход */

$page = (string)($_GET['p'] ?? 'dashboard');

if ($page === 'logout') {
    admin_check_csrf();
    cms_audit('logout', 'admin_user', (int)(admin_user()['id'] ?? 0), 'Выход из панели');
    admin_logout();
    admin_start_session();
    admin_flash('Вы вышли из панели.');
    admin_redirect('login');
}

if ($page === 'login') {
    require __DIR__ . '/screens/login.php';
    exit;
}

// Ни один экран ниже не выполняется без входа.
$user = admin_require_login();
admin_check_csrf();

/* ------------------------------------------------------------- экраны */

$screens = [
    'dashboard'  => ['file' => 'dashboard.php',  'title' => 'Обзор'],
    'orders'     => ['file' => 'orders.php',     'title' => 'Заказы'],
    'products'   => ['file' => 'products.php',   'title' => 'Товары'],
    'product'    => ['file' => 'product.php',    'title' => 'Карточка товара'],
    'prices'     => ['file' => 'prices.php',     'title' => 'Цены и наличие'],
    'categories' => ['file' => 'categories.php', 'title' => 'Категории'],
    'category'   => ['file' => 'category.php',   'title' => 'Категория'],
    'pages'      => ['file' => 'pages.php',      'title' => 'Страницы'],
    'page'       => ['file' => 'page.php',       'title' => 'Страница'],
    'home'       => ['file' => 'home.php',       'title' => 'Главная страница'],
    'media'      => ['file' => 'media.php',      'title' => 'Фотографии'],
    'delivery'   => ['file' => 'delivery.php',   'title' => 'Доставка'],
    'redirects'  => ['file' => 'redirects.php',  'title' => 'Переадресация'],
    'settings'   => ['file' => 'settings.php',   'title' => 'Настройки'],
    'users'      => ['file' => 'users.php',      'title' => 'Пользователи', 'owner' => true],
    'audit'      => ['file' => 'audit.php',      'title' => 'Журнал действий', 'owner' => true],
];

if (!isset($screens[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}

$screen = $screens[$page];

if (!empty($screen['owner'])) {
    admin_require_owner();
}

$screenTitle = $screen['title'];
$currentPage = $page;

require __DIR__ . '/screens/' . $screen['file'];
