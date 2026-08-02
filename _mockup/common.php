<?php
/**
 * ПЕСОЧНИЦА ДЛЯ МАКЕТОВ. В поставку не входит, в рабочую корзину не вмешивается.
 *
 * Страница собирается из настоящей шапки, настоящего блока «order» из базы и
 * настоящего partials/order-form.php — чтобы макет показывал реальный сайт,
 * а не нарисованную схему. Отличия варианта живут только в его CSS и JS.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/cms/repo.php';

$variant     = $variant     ?? 'v1';
$variantName = $variantName ?? 'Вариант';
$page        = repo_page('');
$pageTitle   = 'Макет ' . $variantName . ' · Comp-Uter';
$noindex     = true;

$block = null;
foreach (repo_home_blocks() as $row) {
    if ($row['code'] === 'order') { $block = $row; break; }
}

require dirname(__DIR__) . '/templates/header.php';
?>
<link rel="stylesheet" href="/_mockup/base.css?v=<?= time() ?>" />
<link rel="stylesheet" href="/_mockup/<?= htmlspecialchars($variant, ENT_QUOTES) ?>.css?v=<?= time() ?>" />
<main class="mockup-main">
  <div class="mockup-bar">
    <strong>Макет: <?= htmlspecialchars($variantName, ENT_QUOTES) ?></strong>
    <span>Действующая корзина не изменена. Это отдельная страница для просмотра.</span>
    <nav><a href="/_mockup/v1.php">1 · компактная</a> <a href="/_mockup/v2.php">2 · по шагам</a> <a href="/_mockup/v3.php">3 · разделы</a></nav>
  </div>
<?= $block['body'] ?>
<?php require dirname(__DIR__) . '/templates/partials/order-form.php'; ?>
<?= $block['body_after'] ?>
</main>
<?php require dirname(__DIR__) . '/templates/partials/overlays.php'; ?>
<script>
/* Корзина для показа: два товара, чтобы суммы и состав были настоящими. */
(function () {
  var demo = <?php
    $rows = [];
    foreach (repo_products(['limit' => 2]) as $p) {
        $rows[] = [
            'id'    => $p['slug'],
            'title' => $p['short_name'] ?: $p['name'],
            'price' => (float)repo_price($p),
            'qty'   => 1,
            'image' => '',
            'unit'  => 'шт.',
        ];
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
  ?>;
  try {
    localStorage.setItem("xeon-cookie-consent", "accepted");
    localStorage.setItem("comp-uter-cart", JSON.stringify(demo));
  } catch (e) {}
})();
</script>
<?php
$bodyScripts = ['/src/main.js', '/_mockup/base.js', '/_mockup/' . $variant . '.js'];
require dirname(__DIR__) . '/templates/footer.php';

