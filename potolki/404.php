<?php
/**
 * Обработчик несуществующих адресов.
 * Подключается через ErrorDocument 404 в .htaccess и через try_files в Nginx.
 */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render_404();
