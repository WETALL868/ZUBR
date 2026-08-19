<?php
/** /moskva/rajony/ — Районы Москвы. ROUTE:auto-generated. Создано tools/build-routes.php, не редактируйте вручную. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render('geo-hub', array ( 'group' => 'rajon', 'parent' => 'moskva', ));
