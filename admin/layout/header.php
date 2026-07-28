<?php
/**
 * Шапка и меню панели. Ожидает $screenTitle и $currentPage.
 */
$adminUser = admin_user();
$menu = [
    'dashboard'  => 'Обзор',
    'orders'     => 'Заказы',
    'products'   => 'Товары',
    'prices'     => 'Цены и наличие',
    'categories' => 'Категории',
    'home'       => 'Главная',
    'pages'      => 'Страницы',
    'media'      => 'Фотографии',
    'delivery'   => 'Доставка',
    'redirects'  => 'Переадресация',
    'settings'   => 'Настройки',
];
if (admin_is_owner()) {
    $menu['users'] = 'Пользователи';
    $menu['audit'] = 'Журнал';
}
// Экран правки подсвечивает пункт своего списка.
$activeGroup = ['product' => 'products', 'category' => 'categories', 'page' => 'pages'][$currentPage] ?? $currentPage;
?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="/admin/assets/admin.css?v=1" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title><?= e($screenTitle) ?> · Управление Comp-Uter</title>
  </head>
  <body>
    <header class="adm-top">
      <a class="adm-brand" href="<?= e(admin_url('dashboard')) ?>">Comp-Uter</a>
      <div class="adm-top-right">
        <a class="adm-link" href="/" target="_blank" rel="noopener">Открыть сайт</a>
        <span class="adm-who"><?= e((string)($adminUser['name'] ?? '')) ?></span>
        <form method="post" action="<?= e(admin_url('logout')) ?>" class="adm-inline">
          <?= admin_csrf_field() ?>
          <button class="adm-btn ghost" type="submit">Выйти</button>
        </form>
      </div>
    </header>

    <div class="adm-shell">
      <nav class="adm-side" aria-label="Разделы панели">
        <?php foreach ($menu as $code => $label): ?>
        <a class="adm-nav<?= $activeGroup === $code ? ' active' : '' ?>" href="<?= e(admin_url($code)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>

      <main class="adm-main">
        <?php foreach (admin_take_flash() as $message): ?>
        <p class="adm-flash <?= e($message['kind']) ?>"><?= e($message['text']) ?></p>
        <?php endforeach; ?>
