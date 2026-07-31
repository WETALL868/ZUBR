<?php
/**
 * Способы оплаты.
 *
 * Здесь не платёжные шлюзы: деньги на сайте не списываются. Здесь то, о чём
 * покупатель договаривается с магазином — наличные при получении, счёт для
 * организации, что-то ещё. Выбранный способ уходит вместе с заказом, и
 * менеджер видит его в карточке заказа и в письме.
 *
 * Устроено так же, как доставка: список правится здесь, а форма заказа
 * показывает ровно то, что здесь настроено. Придумать способ оплаты в
 * разметке формы нельзя — она берёт список отсюда.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/cms/orders.php';

/** Кому показывать способ оплаты. */
const PAYMENT_AUDIENCES = [
    'both'       => 'Всем',
    'individual' => 'Только физлицам',
    'legal'      => 'Только юрлицам',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', 'save');

    if ($action === 'save') {
        foreach ((array)($_POST['title'] ?? []) as $methodId => $title) {
            $methodId = (int)$methodId;
            $title = trim((string)$title);
            if ($title === '') {
                continue;
            }

            $audience = (string)($_POST['audience'][$methodId] ?? 'both');

            cms_update('payment_methods', [
                'title'       => $title,
                'description' => trim((string)($_POST['description'][$methodId] ?? '')),
                'audience'    => isset(PAYMENT_AUDIENCES[$audience]) ? $audience : 'both',
                'is_default'  => !empty($_POST['is_default'][$methodId]) ? 1 : 0,
                'is_default_legal' => !empty($_POST['is_default_legal'][$methodId]) ? 1 : 0,
                'is_active'   => !empty($_POST['is_active'][$methodId]) ? 1 : 0,
                'sort_order'  => (int)($_POST['sort_order'][$methodId] ?? 0),
            ], 'id = :id', ['id' => $methodId]);
        }

        /*
         * По умолчанию способ может быть только один на каждый тип
         * покупателя. Если владелец отметил несколько, оставляем верхний по
         * порядку: иначе форма выбирала бы случайный, и покупатель видел бы
         * разное при каждом заходе.
         */
        foreach (['is_default', 'is_default_legal'] as $flag) {
            $ids = array_column(
                cms_all("SELECT id FROM payment_methods WHERE $flag = 1 ORDER BY sort_order, id"),
                'id'
            );
            foreach (array_slice($ids, 1) as $extra) {
                cms_update('payment_methods', [$flag => 0], 'id = :id', ['id' => (int)$extra]);
            }
        }

        cms_audit('payment.save', null, null, 'Изменены способы оплаты');
        admin_flash('Сохранено.');
        admin_redirect('payments');
    }

    if ($action === 'add') {
        $title = (string)admin_post('new_title', '');
        if ($title === '') {
            admin_flash('Укажите название способа оплаты.', 'bad');
        } else {
            cms_insert('payment_methods', [
                'code'        => admin_slugify($title) ?: 'payment-' . time(),
                'title'       => $title,
                'description' => (string)admin_post('new_description', ''),
                'audience'    => 'both',
                'is_active'   => 1,
                'sort_order'  => (int)cms_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM payment_methods'),
            ]);
            cms_audit('payment.add', null, null, 'Добавлен способ оплаты: ' . $title);
            admin_flash('Способ оплаты добавлен.');
        }
        admin_redirect('payments');
    }
}

$methods = cms_all('SELECT * FROM payment_methods ORDER BY sort_order, id');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Оплата</h1>
            <p>Эти способы покупатель выбирает при оформлении заказа. Выбранный уходит в заказ и в письмо.</p>
          </div>
        </div>

        <?php if (!$methods): ?>
        <div class="adm-card">
          <p>Способов оплаты пока нет. Пока список пуст, форма заказа не спрашивает про оплату
             и заказ оформляется без неё. Добавьте хотя бы один способ ниже.</p>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= e(admin_url('payments')) ?>">
          <?= admin_csrf_field() ?>
          <input type="hidden" name="action" value="save" />
          <div class="adm-scroll">
            <table class="adm-table">
              <thead>
                <tr>
                  <th style="width:230px">Название</th>
                  <th>Подпись под названием</th>
                  <th style="width:150px">Кому показывать</th>
                  <th style="width:110px">По умолчанию физлицу</th>
                  <th style="width:110px">По умолчанию юрлицу</th>
                  <th class="num" style="width:90px">Порядок</th>
                  <th style="width:80px">Активен</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($methods as $method): $mid = (int)$method['id']; ?>
                <tr>
                  <td>
                    <input name="title[<?= $mid ?>]" type="text" value="<?= e((string)$method['title']) ?>" />
                    <p class="note">код: <code><?= e((string)$method['code']) ?></code></p>
                  </td>
                  <td><input name="description[<?= $mid ?>]" type="text" value="<?= e((string)$method['description']) ?>" /></td>
                  <td>
                    <select name="audience[<?= $mid ?>]">
                      <?php foreach (PAYMENT_AUDIENCES as $code => $label): ?>
                      <option value="<?= e($code) ?>"<?= (string)$method['audience'] === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td><input name="is_default[<?= $mid ?>]" type="checkbox" value="1"<?= (int)$method['is_default'] === 1 ? ' checked' : '' ?> /></td>
                  <td><input name="is_default_legal[<?= $mid ?>]" type="checkbox" value="1"<?= (int)$method['is_default_legal'] === 1 ? ' checked' : '' ?> /></td>
                  <td class="num"><input name="sort_order[<?= $mid ?>]" type="number" value="<?= (int)$method['sort_order'] ?>" /></td>
                  <td><input name="is_active[<?= $mid ?>]" type="checkbox" value="1"<?= (int)$method['is_active'] === 1 ? ' checked' : '' ?> /></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($methods): ?>
          <div class="adm-actions"><button class="adm-btn" type="submit">Сохранить</button></div>
          <?php endif; ?>
        </form>

        <div class="adm-card" style="margin-top:18px">
          <h2>Добавить способ оплаты</h2>
          <form method="post" action="<?= e(admin_url('payments')) ?>">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="add" />
            <div class="adm-cols">
              <div class="adm-field">
                <label for="new_title">Название</label>
                <input id="new_title" name="new_title" type="text" required />
              </div>
              <div class="adm-field">
                <label for="new_description">Подпись под названием</label>
                <input id="new_description" name="new_description" type="text" />
              </div>
            </div>
            <button class="adm-btn" type="submit">Добавить</button>
          </form>
        </div>

        <div class="adm-card" style="margin-top:18px">
          <h2>Что это такое и чего здесь нет</h2>
          <p class="hint">
            Деньги на сайте не списываются: покупатель выбирает, <b>как</b> он будет платить,
            а расчёт происходит уже с менеджером. Приёма карт на сайте нет — если его подключат,
            способ появится в этом же списке.
          </p>
          <p class="hint">
            «По умолчанию юрлицу» удобно поставить у оплаты по счёту: организация чаще всего
            платит именно так, и лишний выбор ей делать не придётся.
          </p>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
