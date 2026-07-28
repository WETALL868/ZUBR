<?php
/**
 * Список категорий.
 */

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = admin_post_int('id');
    $item = $id ? repo_category_by_id($id) : null;
    $action = (string)admin_post('action', '');

    if ($item && $action === 'publish') {
        $now = (int)$item['is_published'] === 1 ? 0 : 1;
        cms_update('categories', ['is_published' => $now, 'updated_at' => cms_now()], 'id = :id', ['id' => $id]);
        cms_audit('category.publish', 'category', $id, ($now ? 'Опубликована: ' : 'Скрыта: ') . $item['name']);
        admin_flash($now ? 'Категория показана на сайте.' : 'Категория скрыта.');
    }

    if ($item && $action === 'delete') {
        $count = (int)cms_value('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL', [$id]);
        if ($count > 0) {
            // Удалить категорию с товарами — значит потерять их привязку
            // молча. Лучше отказать и объяснить.
            admin_flash("Сначала перенесите товары в другую категорию: сейчас их $count.", 'bad');
        } else {
            cms_query('DELETE FROM categories WHERE id = ?', [$id]);
            cms_audit('category.delete', 'category', $id, 'Удалена категория: ' . $item['name']);
            admin_flash('Категория удалена.', 'warn');
        }
    }

    admin_redirect('categories');
}

$items = repo_categories(false);

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Категории</h1>
            <p>Новая категория сразу получает адрес и страницу — файлы создавать не нужно.</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn" href="<?= e(admin_url('category', ['id' => 'new'])) ?>">Добавить категорию</a>
          </div>
        </div>

        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr><th>Название</th><th>Адрес</th><th class="num">Товаров</th><th>Состояние</th><th class="num">Действия</th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><a href="<?= e(admin_url('category', ['id' => $item['id']])) ?>"><?= e($item['name']) ?></a></td>
                <td><?= e(repo_category_url($item)) ?></td>
                <td class="num"><?= (int)cms_value('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL', [(int)$item['id']]) ?></td>
                <td><?= (int)$item['is_published'] === 1
                    ? '<span class="adm-tag ok">на сайте</span>'
                    : '<span class="adm-tag">скрыта</span>' ?></td>
                <td class="num">
                  <form method="post" action="<?= e(admin_url('categories')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
                    <button class="adm-btn small grey" name="action" value="publish" type="submit"><?= (int)$item['is_published'] === 1 ? 'Скрыть' : 'Показать' ?></button>
                    <button class="adm-btn small danger" name="action" value="delete" type="submit"
                            data-confirm="Удалить категорию «<?= e($item['name']) ?>»?">Удалить</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
