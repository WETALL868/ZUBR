<?php
/** /legal/user-agreement/ — Пользовательское соглашение. ROUTE:auto-generated. Создано tools/build-routes.php, не редактируйте вручную. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render('legal', array ( 'slug' => 'user-agreement', ));
