<?php
/**
 * Экран, который видит владелец, пока база не настроена.
 *
 * Он ничего не делает сам: создать файл с паролем базы через веб — значит
 * позволить это же любому, кто откроет адрес раньше. Поэтому здесь только
 * инструкция и проверка того, что уже готово.
 */

declare(strict_types=1);

$configExists = is_file(CMS_ROOT . '/config/database.php');
$connected = false;
$error = null;

if ($configExists) {
    try {
        cms_db()->query('SELECT 1');
        $connected = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="/admin/assets/admin.css?v=1" />
    <title>Установка · Comp-Uter</title>
  </head>
  <body>
    <div class="adm-shell" style="grid-template-columns: minmax(0, 760px); justify-content: center">
      <main class="adm-main">
        <div class="adm-head">
          <div>
            <h1>Сайт ещё не подключён к базе</h1>
            <p>Осталось три шага. Все они делаются один раз.</p>
          </div>
        </div>

        <div class="adm-card">
          <h2>Шаг 1. Файл с доступами к базе</h2>
          <p class="hint">
            Скопируйте <code>config/database.example.php</code> в <code>config/database.php</code>
            и впишите имя базы, пользователя и пароль. Файл закрыт от посторонних:
            по адресу <code>/config/database.php</code> сервер отвечает «доступ запрещён».
          </p>
          <p><?= $configExists
              ? '<span class="adm-tag ok">Файл найден</span>'
              : '<span class="adm-tag bad">Файла нет</span>' ?></p>
        </div>

        <div class="adm-card">
          <h2>Шаг 2. Проверка соединения</h2>
          <?php if (!$configExists): ?>
          <p class="hint">Станет доступен после первого шага.</p>
          <?php elseif ($connected): ?>
          <p><span class="adm-tag ok">База отвечает</span></p>
          <?php else: ?>
          <p><span class="adm-tag bad">База не отвечает</span></p>
          <p class="hint">Ответ сервера базы: <?= e((string)$error) ?></p>
          <?php endif; ?>
        </div>

        <div class="adm-card">
          <h2>Шаг 3. Перенос каталога</h2>
          <p class="hint">
            Выполните в папке сайта одну команду — она создаст таблицы и перенесёт
            товары, цены, тексты и настройки:
          </p>
          <p><code>php cms/install/migrate.php --apply</code></p>
          <p class="hint">
            Затем создайте себе вход:<br />
            <code>php cms/install/create-admin.php</code>
          </p>
          <p class="adm-actions"><a class="adm-btn" href="/admin/">Проверить снова</a></p>
        </div>
      </main>
    </div>
  </body>
</html>
