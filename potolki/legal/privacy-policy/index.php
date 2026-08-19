<?php
/** /legal/privacy-policy/ — Политика в отношении обработки персональных данных. ROUTE:auto-generated. Создано tools/build-routes.php, не редактируйте вручную. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render('legal', array ( 'slug' => 'privacy-policy', ));
