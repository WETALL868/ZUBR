<?php
/**
 * Заказы: список и работа с одним заказом.
 *
 * До перехода на базу единственным следом заказа было письмо и файл
 * orders-YYYY-MM.jsonl, который никто не открывал: непришедшее письмо
 * означало потерянный заказ. Теперь заказ виден здесь сразу — даже если
 * почтовый сервер не ответил.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/cms/orders.php';

$openId = (int)($_GET['id'] ?? 0);
$filter = (string)($_GET['status'] ?? '');
$search = (string)($_GET['q'] ?? '');

/* ------------------------------------------------------------- выгрузка */

if (($_GET['export'] ?? '') === 'csv') {
    $rows = cms_all('SELECT * FROM orders ORDER BY created_at DESC, id DESC');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    // Четвёртый параметр обязателен: в PHP 8.4 без него сыплется
    // предупреждение, а его текст попадает прямо внутрь выгружаемого файла и
    // ломает таблицу. Пустая строка отключает нестандартное экранирование
    // обратным слэшем — получается обычный CSV, который Excel понимает.
    fputcsv($out, ['Номер', 'Дата', 'Статус', 'Тип покупателя', 'Покупатель', 'Телефон', 'Почта',
                   'Компания', 'ИНН', 'КПП', 'ОГРН', 'Юр. адрес', 'Банк', 'БИК',
                   'Расчётный счёт', 'Корр. счёт',
                   'Доставка', 'Стоимость доставки', 'Регион', 'Город', 'Адрес',
                   'Оплата', 'Товары', 'Скидка', 'Итого', 'Состав'], ';', '"', '');

    foreach ($rows as $row) {
        $items = [];
        foreach (cms_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [(int)$row['id']]) as $item) {
            $items[] = $item['title'] . ' × ' . (int)$item['qty'];
        }

        $type = (string)($row['customer_type'] ?? 'individual') === 'legal' ? 'legal' : 'individual';

        fputcsv($out, [
            $row['number'],
            date('d.m.Y H:i', strtotime((string)$row['created_at'])),
            ORDER_STATUSES[$row['status']] ?? $row['status'],
            ORDER_CUSTOMER_TYPES[$type],
            $row['customer_name'], $row['phone'], $row['email'],
            $row['company'], $row['inn'], $row['kpp'], $row['ogrn'], $row['legal_address'],
            $row['bank_name'], $row['bik'], $row['bank_account'], $row['corr_account'],
            $row['delivery_title'],
            $row['delivery_price'] === null ? 'по тарифам' : cms_money_machine((float)$row['delivery_price']),
            $row['region'], $row['city'], $row['address'],
            $row['payment'],
            cms_money_machine((float)$row['items_total']),
            $row['discount'] === null ? '' : cms_money_machine((float)$row['discount']),
            cms_money_machine((float)$row['total']),
            implode('; ', $items),
        ], ';', '"', '');
    }
    fclose($out);
    exit;
}

/* -------------------------------------------------------------- печать */

if (($_GET['print'] ?? '') !== '' && $openId) {
    $order = cms_one('SELECT * FROM orders WHERE id = ?', [$openId]);
    if ($order) {
        $items = cms_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$openId]);
        require __DIR__ . '/order-print.php';
        exit;
    }
}

/* ------------------------------------------------------------ действия */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = admin_post_int('id');
    $order = $id ? cms_one('SELECT * FROM orders WHERE id = ?', [$id]) : null;
    $action = (string)admin_post('action', '');

    if ($order && $action === 'status') {
        $status = (string)admin_post('status', '');
        if (isset(ORDER_STATUSES[$status]) && $status !== $order['status']) {
            cms_update('orders', [
                'status'     => $status,
                'is_paid'    => in_array($status, ['paid', 'shipped', 'done'], true) ? 1 : (int)$order['is_paid'],
                'updated_at' => cms_now(),
            ], 'id = :id', ['id' => $id]);

            // История статусов ведётся отдельно: «когда мы это отправили» —
            // вопрос, который задают чаще, чем кажется.
            cms_insert('order_status_log', [
                'order_id'   => $id,
                'status'     => $status,
                'note'       => (string)admin_post('note', '') ?: null,
                'admin_id'   => (int)$user['id'],
                'created_at' => cms_now(),
            ]);

            cms_audit('order.status', 'order', $id, 'Заказ ' . $order['number'] . ': ' . (ORDER_STATUSES[$status] ?? $status));
            admin_flash('Статус изменён на «' . ORDER_STATUSES[$status] . '».');
        }
    }

    if ($order && $action === 'note') {
        cms_update('orders', [
            'manager_note' => (string)admin_post('manager_note', '') ?: null,
            'updated_at'   => cms_now(),
        ], 'id = :id', ['id' => $id]);
        cms_audit('order.note', 'order', $id, 'Заметка к заказу ' . $order['number']);
        admin_flash('Заметка сохранена.');
    }

    admin_redirect('orders', array_filter(['id' => $id, 'status' => $filter, 'q' => $search]));
}

/* -------------------------------------------------------------- выборка */

$sql = 'SELECT * FROM orders WHERE 1 = 1';
$params = [];

if ($filter !== '' && isset(ORDER_STATUSES[$filter])) {
    $sql .= ' AND status = ?';
    $params[] = $filter;
}
if ($search !== '') {
    $sql .= ' AND (number LIKE ? OR customer_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 200';
$orders = cms_all($sql, $params);

$counts = [];
foreach (cms_all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
    $counts[$row['status']] = (int)$row['n'];
}

$order = $openId ? cms_one('SELECT * FROM orders WHERE id = ?', [$openId]) : null;
$items = $order ? cms_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$openId]) : [];
$log   = $order ? cms_all('SELECT * FROM order_status_log WHERE order_id = ? ORDER BY created_at, id', [$openId]) : [];

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Заказы</h1>
            <p>Всего: <?= array_sum($counts) ?>. Новых: <?= $counts['new'] ?? 0 ?>.</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn grey" href="<?= e(admin_url('orders', ['export' => 'csv'])) ?>">Выгрузить в Excel</a>
          </div>
        </div>

        <div class="adm-tabs">
          <a class="adm-tab<?= $filter === '' ? ' active' : '' ?>" href="<?= e(admin_url('orders')) ?>">Все</a>
          <?php foreach (ORDER_STATUSES as $code => $label): ?>
          <a class="adm-tab<?= $filter === $code ? ' active' : '' ?>" href="<?= e(admin_url('orders', ['status' => $code])) ?>">
            <?= e($label) ?><?= isset($counts[$code]) ? ' (' . $counts[$code] . ')' : '' ?>
          </a>
          <?php endforeach; ?>
        </div>

        <form class="adm-card" method="get" action="/admin/">
          <input type="hidden" name="p" value="orders" />
          <?php if ($filter !== ''): ?><input type="hidden" name="status" value="<?= e($filter) ?>" /><?php endif; ?>
          <div class="adm-field">
            <label for="q">Поиск по номеру, имени, телефону или почте</label>
            <input id="q" name="q" type="text" value="<?= e($search) ?>" />
          </div>
          <div class="adm-actions">
            <button class="adm-btn" type="submit">Найти</button>
            <?php if ($search !== ''): ?><a class="adm-btn grey" href="<?= e(admin_url('orders', ['status' => $filter])) ?>">Сбросить</a><?php endif; ?>
          </div>
        </form>

        <?php if (!$orders): ?>
        <div class="adm-card"><p class="hint">Заказов нет.</p></div>
        <?php else: ?>
        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr><th>Номер</th><th>Дата</th><th>Покупатель</th><th>Состав</th><th class="num">Итого</th><th>Статус</th></tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $row):
                  $rowItems = cms_all('SELECT title, qty FROM order_items WHERE order_id = ? ORDER BY id', [(int)$row['id']]); ?>
              <tr<?= (int)$row['id'] === $openId ? ' style="background:var(--accent-soft)"' : '' ?>>
                <td><a href="<?= e(admin_url('orders', ['id' => $row['id']])) ?>"><?= e((string)$row['number']) ?></a></td>
                <td><?= e(date('d.m.Y H:i', strtotime((string)$row['created_at']))) ?></td>
                <td>
                  <?= e((string)$row['customer_name']) ?>
                  <?php if ((string)($row['customer_type'] ?? '') === 'legal'): ?>
                  <span class="adm-tag">юр. лицо</span>
                  <?php endif; ?>
                  <div style="color:var(--muted);font-size:12px">
                    <?= e((string)$row['phone']) ?>
                    <?php if (!empty($row['inn'])): ?> · ИНН <?= e((string)$row['inn']) ?><?php endif; ?>
                  </div>
                </td>
                <td style="font-size:13px">
                  <?php if (!$rowItems): ?>
                  <span class="adm-tag">заявка без корзины</span>
                  <?php else: foreach ($rowItems as $item): ?>
                  <div><?= e((string)$item['title']) ?> × <?= (int)$item['qty'] ?></div>
                  <?php endforeach; endif; ?>
                </td>
                <td class="num"><?= e(cms_money((float)$row['total'])) ?></td>
                <td>
                  <span class="adm-tag <?= $row['status'] === 'new' ? 'warn' : ($row['status'] === 'cancelled' ? 'bad' : 'ok') ?>">
                    <?= e(ORDER_STATUSES[$row['status']] ?? $row['status']) ?>
                  </span>
                  <?php if ($row['mail_status'] === 'mail_not_configured'): ?>
                  <span class="adm-tag warn">почта не настроена</span>
                  <?php elseif ($row['mail_status'] && (str_contains((string)$row['mail_status'], 'failed')
                      || str_contains((string)$row['mail_status'], 'invalid'))): ?>
                  <span class="adm-tag bad" title="<?= e((string)$row['mail_status']) ?>">письмо не ушло</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if ($order): ?>
        <div class="adm-card" style="margin-top:18px">
          <div class="adm-head">
            <div>
              <h2 style="font-size:18px">Заказ <?= e((string)$order['number']) ?></h2>
              <p><?= e(date('d.m.Y H:i', strtotime((string)$order['created_at']))) ?>
                 · <?= e(ORDER_STATUSES[$order['status']] ?? (string)$order['status']) ?></p>
            </div>
            <div class="adm-actions">
              <a class="adm-btn grey" href="<?= e(admin_url('orders', ['id' => $order['id'], 'print' => 1])) ?>" target="_blank" rel="noopener">Печать</a>
            </div>
          </div>

          <?php
            $customerType = (string)($order['customer_type'] ?? 'individual') === 'legal' ? 'legal' : 'individual';
            $legalDetails = $customerType === 'legal' ? orders_legal_details($order) : [];
          ?>
          <div class="adm-cols">
            <div>
              <h3 style="font-size:14px">Покупатель</h3>
              <p>
                <b>Тип покупателя: <?= e(ORDER_CUSTOMER_TYPES[$customerType]) ?></b><br />
                <?= e((string)$order['customer_name']) ?><br />
                <a href="tel:<?= e((string)$order['phone']) ?>"><?= e((string)$order['phone']) ?></a>
                <?php if ($order['email']): ?><br /><a href="mailto:<?= e((string)$order['email']) ?>"><?= e((string)$order['email']) ?></a><?php endif; ?>
              </p>
            </div>
            <div>
              <h3 style="font-size:14px">Доставка и оплата</h3>
              <p>
                <?= e((string)($order['delivery_title'] ?: '—')) ?>
                — <?= $order['delivery_price'] === null ? 'по тарифам службы' : e(cms_money((float)$order['delivery_price'])) ?><br />
                <?php if (!empty($order['delivery_term'])): ?>Срок: <?= e((string)$order['delivery_term']) ?><br /><?php endif; ?>
                <?php if (!empty($order['region'])): ?><?= e((string)$order['region']) ?><br /><?php endif; ?>
                <?php if ($order['city']): ?><?= e((string)$order['city']) ?><br /><?php endif; ?>
                <?php if ($order['address']): ?><?= nl2br(e((string)$order['address'])) ?><br /><?php endif; ?>
                Оплата: <?= e((string)($order['payment'] ?: '—')) ?>
              </p>
            </div>
          </div>

          <?php if ($legalDetails): ?>
          <?php /* Реквизиты — отдельным блоком: менеджер выставляет по ним счёт,
                   и искать их вперемешку с адресом доставки неудобно. */ ?>
          <div class="adm-card" style="margin:0 0 14px">
            <h3 style="font-size:14px;margin-top:0">Реквизиты организации</h3>
            <div class="adm-scroll">
              <table class="adm-table">
                <tbody>
                  <?php foreach ($legalDetails as $label => $value): ?>
                  <tr>
                    <th style="text-align:left;width:220px"><?= e($label) ?></th>
                    <td><?= nl2br(e($value)) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($order['comment']): ?>
          <p><b>Комментарий покупателя:</b> <?= nl2br(e((string)$order['comment'])) ?></p>
          <?php endif; ?>

          <?php if ($items): ?>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th>Товар</th><th class="num">Цена</th><th class="num">Кол-во</th><th class="num">Сумма</th></tr></thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <?php if ($item['product_id']): ?>
                    <a href="<?= e(admin_url('product', ['id' => (int)$item['product_id']])) ?>"><?= e((string)$item['title']) ?></a>
                    <?php else: ?><?= e((string)$item['title']) ?><?php endif; ?>
                    <?php if ($item['sku']): ?><div style="color:var(--muted);font-size:12px">арт. <?= e((string)$item['sku']) ?></div><?php endif; ?>
                  </td>
                  <td class="num"><?= e(cms_money((float)$item['price'])) ?></td>
                  <td class="num"><?= (int)$item['qty'] ?></td>
                  <td class="num"><?= e(cms_money((float)$item['total'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                  <td colspan="3" class="num"><b>Товары</b></td>
                  <td class="num"><b><?= e(cms_money((float)$order['items_total'])) ?></b></td>
                </tr>
                <tr>
                  <td colspan="3" class="num"><b>Итого с доставкой</b></td>
                  <td class="num"><b><?= e(cms_money((float)$order['total'])) ?></b></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="hint">Цены записаны на момент заказа: если товар потом подорожал, здесь останется прежняя.</p>
          <?php endif; ?>

          <div class="adm-cols" style="margin-top:14px">
            <form method="post" action="<?= e(admin_url('orders', ['id' => $order['id']])) ?>">
              <?= admin_csrf_field() ?>
              <input type="hidden" name="action" value="status" />
              <input type="hidden" name="id" value="<?= (int)$order['id'] ?>" />
              <div class="adm-field">
                <label for="status">Изменить статус</label>
                <select id="status" name="status">
                  <?php foreach (ORDER_STATUSES as $code => $label): ?>
                  <option value="<?= e($code) ?>"<?= $order['status'] === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="adm-field">
                <label for="note">Пояснение (попадёт в историю)</label>
                <input id="note" name="note" type="text" />
              </div>
              <button class="adm-btn" type="submit">Сохранить статус</button>
            </form>

            <form method="post" action="<?= e(admin_url('orders', ['id' => $order['id']])) ?>">
              <?= admin_csrf_field() ?>
              <input type="hidden" name="action" value="note" />
              <input type="hidden" name="id" value="<?= (int)$order['id'] ?>" />
              <div class="adm-field">
                <label for="manager_note">Заметка менеджера (покупатель её не видит)</label>
                <textarea id="manager_note" name="manager_note"><?= e((string)$order['manager_note']) ?></textarea>
              </div>
              <button class="adm-btn grey" type="submit">Сохранить заметку</button>
            </form>
          </div>

          <?php if ($log): ?>
          <h3 style="font-size:14px;margin-top:16px">История</h3>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th>Когда</th><th>Статус</th><th>Пояснение</th></tr></thead>
              <tbody>
                <?php foreach ($log as $row): ?>
                <tr>
                  <td><?= e(date('d.m.Y H:i', strtotime((string)$row['created_at']))) ?></td>
                  <td><?= e(ORDER_STATUSES[$row['status']] ?? (string)$row['status']) ?></td>
                  <td><?= e((string)$row['note']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
