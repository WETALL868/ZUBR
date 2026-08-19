<?php
/** /services/vizualizaciya/ — Визуализация потолка и освещения. ROUTE:auto-generated. Создано tools/build-routes.php, не редактируйте вручную. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render('service', array ( 'set' => 'services', 'slug' => 'vizualizaciya', ));
