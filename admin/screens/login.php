<?php
/**
 * Вход в панель.
 *
 * Пароль в исходном коде не хранится и в базе лежит только хешем. Ошибка
 * входа не уточняет, что именно неверно, — иначе форма подсказывала бы,
 * какие логины существуют.
 */

declare(strict_types=1);

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();

    $result = admin_attempt_login((string)admin_post('login', ''), (string)($_POST['password'] ?? ''));

    if ($result['ok']) {
        $back = (string)($_GET['back'] ?? '');
        // Возвращаемся только внутрь панели: чужой адрес в параметре превратил
        // бы форму входа в открытую переадресацию.
        header('Location: ' . (preg_match('~^/admin/~', $back) ? $back : admin_url('dashboard')));
        exit;
    }

    $error = $result['message'];
}

if (admin_user()) {
    admin_redirect('dashboard');
}

$screenTitle = 'Вход';
?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="/admin/assets/admin.css?v=1" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title>Вход · Управление Comp-Uter</title>
  </head>
  <body>
    <form class="adm-login" method="post" action="<?= e(admin_url('login', ['back' => (string)($_GET['back'] ?? '')])) ?>">
      <?= admin_csrf_field() ?>
      <h1>Управление сайтом</h1>
      <p class="lead">Comp-Uter</p>

      <?php foreach (admin_take_flash() as $message): ?>
      <p class="adm-flash <?= e($message['kind']) ?>"><?= e($message['text']) ?></p>
      <?php endforeach; ?>

      <?php if ($error !== null): ?>
      <p class="adm-flash bad"><?= e($error) ?></p>
      <?php endif; ?>

      <div class="adm-field">
        <label for="login">Логин</label>
        <input id="login" name="login" type="text" autocomplete="username" required autofocus />
      </div>

      <div class="adm-field">
        <label for="password">Пароль</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required />
      </div>

      <button class="adm-btn" type="submit" style="width:100%">Войти</button>
    </form>
  </body>
</html>
