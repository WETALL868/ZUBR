<?php
/** Главная страница. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

// Запрос к несуществующему адресу попадает сюда через ErrorDocument/try_files:
// корректно отдаём 404 вместо главной, иначе поисковые системы получат
// сотни «мягких 404».
if (request_path() !== '/') {
    render_404();
}

render('home');
