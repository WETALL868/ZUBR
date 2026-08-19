<?php
/** /systems/skrytyj-karniz/ — Скрытый карниз (ниша под шторы). ROUTE:auto-generated. Создано tools/build-routes.php, не редактируйте вручную. */

$d = __DIR__;
while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') {
    $d = dirname($d);
}
require $d . '/includes/bootstrap.php';

render('catalog-item', array ( 'set' => 'systems', 'slug' => 'skrytyj-karniz', ));
