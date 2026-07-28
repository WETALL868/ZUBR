<?php
/**
 * Список товаров: поиск, фильтр по категории, быстрая правка порядка,
 * публикация и удаление в корзину.
 */

declare(strict_types=1);

$search   = (string)($_GET['q'] ?? '');
$category = (string)($_GET['cat'] ?? '');
$trash    = !empty($_GET['trash']);

/* ------------------------------------------------------------ действия */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');
    $id     = admin_post_int('id');
    $item   = $id ? repo_product_by_id($id) : null;

    if ($item && $action === 'publish') {
        $now = (int)$item['is_published'] === 1 ? 0 : 1;
        cms_update('products', ['is_published' => $now, 'updated_at' => cms_now()], 'id = :id', ['id' => $id]);
        cms_audit('product.publish', 'product', $id, ($now ? 'Опубликован: ' : 'Скрыт: ') . $item['name']);
        admin_flash($now ? 'Товар показан на сайте.' : 'Товар убран с сайта.');
    }

    // Удаление помечает дату, а не стирает строку: заказы ссылаются на товар,
    // и потерять его название в старом заказе нельзя.
    if ($item && $action === 'delete') {
        cms_update('products', ['deleted_at' => cms_now(), 'is_published' => 0], 'id = :id', ['id' => $id]);
        cms_audit('product.delete', 'product', $id, 'В корзину: ' . $item['name']);
        admin_flash('Товар убран в корзину. Его можно вернуть.', 'warn');
    }

    if ($item && $action === 'restore') {
        cms_update('products', ['deleted_at' => null], 'id = :id', ['id' => $id]);
        cms_audit('product.restore', 'product', $id, 'Возвращён: ' . $item['name']);
        admin_flash('Товар возвращён. Он пока скрыт — опубликуйте, когда будете готовы.');
    }

    admin_redirect('products', array_filter(['q' => $search, 'cat' => $category, 'trash' => $trash ? 1 : null]));
}

/* -------------------------------------------------------------- выборка */

if ($trash) {
    $items = cms_all(
        'SELECT p.*, c.name AS category_name FROM products p
      LEFT JOIN categories c ON c.id = p.category_id
          WHERE p.deleted_at IS NOT NULL ORDER BY p.deleted_at DESC'
    );
} else {
    $items = repo_products(array_filter([
        'published' => false,
        'search'    => $search !== '' ? $search : null,
        'category'  => $category !== '' ? $category : null,
    ], static fn($v) => $v !== null));
}

$trashCount = (int)cms_value('SELECT COUNT(*) FROM products WHERE deleted_at IS NOT NULL');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1><?= $trash ? 'Корзина' : 'Товары' ?></h1>
            <p><?= count($items) ?> <?= $trash ? 'в корзине' : 'в каталоге' ?></p>
          </div>
          <div class="adm-actions">
            <?php if (!$trash): ?>
            <a class="adm-btn" href="<?= e(admin_url('product', ['id' => 'new'])) ?>">Добавить товар</a>
            <?php endif; ?>
            <?php if ($trashCount > 0 || $trash): ?>
            <a class="adm-btn grey" href="<?= e($trash ? admin_url('products') : admin_url('products', ['trash' => 1])) ?>">
              <?= $trash ? 'К списку товаров' : 'Корзина (' . $trashCount . ')' ?>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!$trash): ?>
        <form class="adm-card" method="get" action="/admin/">
          <input type="hidden" name="p" value="products" />
          <div class="adm-cols">
            <div class="adm-field">
              <label for="q">Поиск по названию, адресу или артикулу</label>
              <input id="q" name="q" type="text" value="<?= e($search) ?>" />
            </div>
            <div class="adm-field">
              <label for="cat">Категория</label>
              <select id="cat" name="cat">
                <option value="">Все</option>
                <?php foreach (repo_categories(false) as $c): ?>
                <option value="<?= e($c['slug']) ?>"<?= $category === $c['slug'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="adm-actions">
            <button class="adm-btn" type="submit">Показать</button>
            <?php if ($search !== '' || $category !== ''): ?>
            <a class="adm-btn grey" href="<?= e(admin_url('products')) ?>">Сбросить</a>
            <?php endif; ?>
          </div>
        </form>
        <?php endif; ?>

        <?php if (!$items): ?>
        <div class="adm-card"><p class="hint">Ничего не найдено.</p></div>
        <?php else: ?>
        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr>
                <th></th>
                <th>Название</th>
                <th>Категория</th>
                <th class="num">Цена</th>
                <th>Наличие</th>
                <th>Состояние</th>
                <th class="num">Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item):
                  $price = repo_price($item);
                  $old   = repo_old_price($item); ?>
              <tr>
                <td><img class="thumb" src="<?= e(repo_image_web($item)) ?>" alt="" /></td>
                <td>
                  <a href="<?= e(admin_url('product', ['id' => $item['id']])) ?>"><?= e((string)($item['short_name'] ?: $item['name'])) ?></a>
                  <div style="color:var(--muted);font-size:12px">/<?= e((string)$item['slug']) ?>/ · арт. <?= e((string)$item['sku']) ?></div>
                </td>
                <td><?= e((string)($item['category_name'] ?? '—')) ?></td>
                <td class="num">
                  <?= e(cms_money($price)) ?>
                  <?php if ($old !== null): ?><div style="color:var(--muted);font-size:12px;text-decoration:line-through"><?= e(cms_money($old)) ?></div><?php endif; ?>
                </td>
                <td><span class="adm-tag <?= $item['availability'] === 'in_stock' ? 'ok' : ($item['availability'] === 'out_of_stock' ? 'bad' : 'warn') ?>"><?= e(repo_stock_label($item)) ?></span></td>
                <td>
                  <?php if ($trash): ?>
                  <span class="adm-tag bad">в корзине</span>
                  <?php elseif ((int)$item['is_published'] === 1): ?>
                  <span class="adm-tag ok">на сайте</span>
                  <?php else: ?>
                  <span class="adm-tag">скрыт</span>
                  <?php endif; ?>
                  <?php if (repo_image_is_placeholder($item)): ?><span class="adm-tag warn">нет фото</span><?php endif; ?>
                  <?php if ($price === null): ?><span class="adm-tag warn">нет цены</span><?php endif; ?>
                </td>
                <td class="num">
                  <form method="post" action="<?= e(admin_url('products', array_filter(['q' => $search, 'cat' => $category, 'trash' => $trash ? 1 : null]))) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
                    <?php if ($trash): ?>
                    <button class="adm-btn small ghost" name="action" value="restore" type="submit">Вернуть</button>
                    <?php else: ?>
                    <button class="adm-btn small grey" name="action" value="publish" type="submit"><?= (int)$item['is_published'] === 1 ? 'Скрыть' : 'Показать' ?></button>
                    <button class="adm-btn small danger" name="action" value="delete" type="submit"
                            data-confirm="Убрать «<?= e((string)($item['short_name'] ?: $item['name'])) ?>» в корзину?">Удалить</button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
