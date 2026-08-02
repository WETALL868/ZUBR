<?php
declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !is_admin()) {
    $login = (string)($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (hash_equals((string)$config['admin_login'], $login) && hash_equals((string)$config['admin_password'], $password)) {
        session_regenerate_id(true);
        $_SESSION['novaya_iskan_admin'] = true;
        header('Location: /admin/');
        exit;
    }
    $error = 'Неверный логин или пароль.';
}

if (!is_admin()):
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Вход администратора</title><link rel="stylesheet" href="/assets/admin.css"></head><body><main class="login"><h1>Вход администратора</h1><p>Обращения посетителей сайта.</p><?php if ($error): ?><p class="error"><?=h($error)?></p><?php endif; ?><form method="post"><label>Логин<input name="login" required autocomplete="username"></label><label>Пароль<input name="password" type="password" required autocomplete="current-password"></label><button class="button" type="submit">Войти</button></form><p><a href="/">← Вернуться на сайт</a></p></main></body></html><?php
exit;
endif;

$records = [];
if (is_dir(storage_dir())) {
    foreach (glob(storage_dir() . DIRECTORY_SEPARATOR . '*.record.php') ?: [] as $path) {
        $record = load_record($path);
        if ($record) $records[] = $record;
    }
}
usort($records, static function (array $a, array $b): int {
    return strcmp((string)$b['created_at'], (string)$a['created_at']);
});
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Обращения — Новая Искань</title><link rel="stylesheet" href="/assets/admin.css"></head><body><header class="top"><div class="top-inner"><div class="brand">СНП «Новая Искань»</div><nav><a href="/">На сайт</a> · <a href="?logout=1">Выйти</a></nav></div></header><main class="content"><div class="topline"><div><h1>Обращения</h1><p>Всего: <?=count($records)?></p></div></div><?php if (!$records): ?><div class="empty">Обращений пока нет.</div><?php endif; ?><?php foreach ($records as $record): ?><article class="card"><h2><?=h((string)$record['topic'])?></h2><div class="meta"><strong><?=h((string)$record['public_id'])?></strong><span><?=h(date('d.m.Y H:i', strtotime((string)$record['created_at'])))?></span><span><?=h((string)$record['full_name'])?></span><?php if (!empty($record['plot_number'])): ?><span>Участок: <?=h((string)$record['plot_number'])?></span><?php endif; ?></div><div class="meta"><?php if (!empty($record['phone'])): ?><span>Телефон: <?=h((string)$record['phone'])?></span><?php endif; ?><?php if (!empty($record['email'])): ?><span>Email: <?=h((string)$record['email'])?></span><?php endif; ?></div><div class="message"><?=h((string)$record['message'])?></div><?php if (!empty($record['attachment'])): ?><a class="file" href="/admin/download.php?id=<?=rawurlencode((string)$record['public_id'])?>">Скачать: <?=h((string)$record['attachment']['original_name'])?></a><?php endif; ?></article><?php endforeach; ?></main></body></html>
