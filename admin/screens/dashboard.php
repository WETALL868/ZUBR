<?php
/**
 * Обзор: что в каталоге и что требует внимания.
 *
 * Экран отвечает на один вопрос — «всё ли в порядке прямо сейчас». Поэтому
 * здесь не общая статистика, а именно проблемы: товар без цены не купят, а
 * товар без фотографии выпадет из фида Яндекс.Маркета.
 */

declare(strict_types=1);

$products   = repo_products(['published' => false]);
$published  = array_filter($products, static fn($p) => (int)$p['is_published'] === 1);
$noPrice    = array_filter($published, static fn($p) => repo_price($p) === null);
$noPhoto    = array_filter($published, static fn($p) => repo_image_is_placeholder($p));
$outOfStock = array_filter($published, static fn($p) => $p['availability'] === 'out_of_stock');

$newOrders = (int)cms_value("SELECT COUNT(*) FROM orders WHERE status = 'new'");
$todayOrders = (int)cms_value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [date('Y-m-d') . ' 00:00:00']);
$failedMail = (int)cms_value(
    "SELECT COUNT(*) FROM orders WHERE mail_status LIKE '%failed%' OR mail_status LIKE '%invalid%'"
);
$unconfiguredMail = (int)cms_value("SELECT COUNT(*) FROM orders WHERE mail_status = 'mail_not_configured'");

$recent = cms_all('SELECT * FROM audit_log ORDER BY created_at DESC, id DESC LIMIT 8');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Обзор</h1>
            <p>Каталог, цены и фотографии — коротко.</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn" href="<?= e(admin_url('orders')) ?>">Заказы</a>
            <a class="adm-btn ghost" href="<?= e(admin_url('prices')) ?>">Изменить цены</a>
            <a class="adm-btn ghost" href="<?= e(admin_url('product', ['id' => 'new'])) ?>">Добавить товар</a>
          </div>
        </div>

        <div class="adm-grid">
          <div class="adm-tile">
            <b><?= $newOrders ?></b>
            <span>новых заказов<?= $todayOrders > 0 ? ', сегодня ' . $todayOrders : '' ?></span>
          </div>
          <div class="adm-tile">
            <b><?= count($published) ?></b>
            <span>товаров опубликовано<?= count($products) > count($published)
                ? ' (ещё ' . (count($products) - count($published)) . ' скрыто)' : '' ?></span>
          </div>
          <div class="adm-tile">
            <b><?= count(repo_categories(false)) ?></b>
            <span>категорий</span>
          </div>
          <div class="adm-tile">
            <b><?= count($noPrice) ?></b>
            <span>без цены — не показываются как товар</span>
          </div>
          <div class="adm-tile">
            <b><?= count($noPhoto) ?></b>
            <span>без фотографии — не попадают в фид</span>
          </div>
        </div>

        <?php if ($unconfiguredMail > 0): ?>
        <p class="adm-flash warn">
          Заказов принято без отправки письма: <?= $unconfiguredMail ?> — почта ещё не настроена.
          Сами заказы сохранены и видны в разделе <a href="<?= e(admin_url('orders')) ?>">«Заказы»</a>.
          Чтобы письма приходили, заполните <code>api/mail-config.php</code>
          по образцу <code>api/mail-config.example.php</code>.
        </p>
        <?php endif; ?>

        <?php if ($failedMail > 0): ?>
        <p class="adm-flash bad">
          Заказов, о которых не удалось отправить письмо: <?= $failedMail ?>.
          Сами заказы сохранены — откройте <a href="<?= e(admin_url('orders')) ?>">список заказов</a>,
          а причину смотрите в настройках почты <code>api/mail-config.php</code>.
        </p>
        <?php endif; ?>

        <?php if ($noPrice || $noPhoto || $outOfStock): ?>
        <div class="adm-card">
          <h2>Требует внимания</h2>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead>
                <tr><th>Товар</th><th>Что не так</th><th class="num">Действие</th></tr>
              </thead>
              <tbody>
                <?php
                $issues = [];
                foreach ($noPrice as $p)    { $issues[$p['id']]['p'] = $p; $issues[$p['id']]['w'][] = 'нет цены'; }
                foreach ($noPhoto as $p)    { $issues[$p['id']]['p'] = $p; $issues[$p['id']]['w'][] = 'нет фотографии'; }
                foreach ($outOfStock as $p) { $issues[$p['id']]['p'] = $p; $issues[$p['id']]['w'][] = 'нет в наличии'; }
                foreach ($issues as $issue): $p = $issue['p']; ?>
                <tr>
                  <td><a href="<?= e(admin_url('product', ['id' => $p['id']])) ?>"><?= e((string)($p['short_name'] ?: $p['name'])) ?></a></td>
                  <td><?php foreach ($issue['w'] as $what): ?><span class="adm-tag warn"><?= e($what) ?></span> <?php endforeach; ?></td>
                  <td class="num"><a class="adm-btn small ghost" href="<?= e(admin_url('product', ['id' => $p['id']])) ?>">Открыть</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php else: ?>
        <div class="adm-card">
          <h2>Требует внимания</h2>
          <p class="hint">Ничего: у всех опубликованных товаров есть цена и фотография.</p>
        </div>
        <?php endif; ?>

        <div class="adm-card">
          <h2>Последние действия</h2>
          <?php if (!$recent): ?>
          <p class="hint">Пока пусто.</p>
          <?php else: ?>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th>Когда</th><th>Кто</th><th>Что</th></tr></thead>
              <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                  <td><?= e(date('d.m.Y H:i', strtotime((string)$row['created_at']))) ?></td>
                  <td><?= e((string)$row['admin_login']) ?></td>
                  <td><?= e((string)($row['summary'] ?: $row['action'])) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
