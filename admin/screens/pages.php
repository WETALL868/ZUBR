<?php
/**
 * Список страниц. Главная в этом списке тоже есть, но правится отдельным
 * экраном: она собирается из блоков, а не из одного поля с текстом.
 */

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = admin_post_int('id');
    $item = $id ? cms_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
    $action = (string)admin_post('action', '');

    if ($item && $action === 'delete') {
        if ((string)$item['slug'] === '') {
            admin_flash('Главную страницу удалить нельзя.', 'bad');
        } else {
            cms_query('DELETE FROM pages WHERE id = ?', [$id]);
            cms_audit('page.delete', 'page', $id, 'Удалена страница: ' . $item['title']);
            admin_flash('Страница удалена.', 'warn');
        }
    }

    admin_redirect('pages');
}

$items = cms_all('SELECT * FROM pages ORDER BY sort_order, id');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Страницы</h1>
            <p>Политика, соглашение, доставка, оплата — всё, что не товар и не категория.</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn" href="<?= e(admin_url('page', ['id' => 'new'])) ?>">Добавить страницу</a>
          </div>
        </div>

        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr><th>Название</th><th>Адрес</th><th>В подвале</th><th>Состояние</th><th class="num">Действия</th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): $isHome = (string)$item['slug'] === ''; ?>
              <tr>
                <td>
                  <?php if ($isHome): ?>
                  <a href="<?= e(admin_url('home')) ?>"><?= e((string)$item['title']) ?></a>
                  <span class="adm-tag">собирается из блоков</span>
                  <?php else: ?>
                  <a href="<?= e(admin_url('page', ['id' => $item['id']])) ?>"><?= e((string)$item['title']) ?></a>
                  <?php endif; ?>
                </td>
                <td>/<?= e((string)$item['slug']) ?><?= $isHome ? '' : '/' ?></td>
                <td><?= (int)$item['in_footer'] === 1 ? 'да' : '—' ?></td>
                <td><?= $item['status'] === 'published'
                    ? '<span class="adm-tag ok">на сайте</span>'
                    : '<span class="adm-tag">черновик</span>' ?></td>
                <td class="num">
                  <?php if (!$isHome): ?>
                  <form method="post" action="<?= e(admin_url('pages')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
                    <button class="adm-btn small danger" name="action" value="delete" type="submit"
                            data-confirm="Удалить страницу «<?= e((string)$item['title']) ?>»?">Удалить</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
