<?php
/**
 * Журнал действий: кто, когда и что менял.
 *
 * Нужен ровно в тот момент, когда на сайте появилась неверная цена и никто
 * не помнит, откуда она взялась.
 */

declare(strict_types=1);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 60;
$total = (int)cms_value('SELECT COUNT(*) FROM audit_log');
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$rows = cms_all(
    'SELECT * FROM audit_log ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
);

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Журнал действий</h1>
            <p>Записей: <?= number_format($total, 0, ',', ' ') ?>.</p>
          </div>
        </div>

        <?php if (!$rows): ?>
        <div class="adm-card"><p class="hint">Пока пусто.</p></div>
        <?php else: ?>
        <div class="adm-scroll">
          <table class="adm-table">
            <thead><tr><th>Когда</th><th>Кто</th><th>Что</th><th>Адрес</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= e(date('d.m.Y H:i:s', strtotime((string)$row['created_at']))) ?></td>
                <td><?= e((string)$row['admin_login']) ?></td>
                <td>
                  <?= e((string)($row['summary'] ?: $row['action'])) ?>
                  <?php if ($row['entity'] === 'product' && $row['entity_id']): ?>
                  <a class="adm-btn small ghost" href="<?= e(admin_url('product', ['id' => (int)$row['entity_id']])) ?>">открыть</a>
                  <?php endif; ?>
                </td>
                <td style="color:var(--muted)"><?= e((string)$row['ip']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="adm-actions" style="margin-top:14px">
          <?php if ($page > 1): ?>
          <a class="adm-btn grey" href="<?= e(admin_url('audit', ['page' => $page - 1])) ?>">Назад</a>
          <?php endif; ?>
          <span class="adm-who">Страница <?= $page ?> из <?= $pages ?></span>
          <?php if ($page < $pages): ?>
          <a class="adm-btn grey" href="<?= e(admin_url('audit', ['page' => $page + 1])) ?>">Дальше</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
