<?php
/**
 * Способы доставки.
 *
 * То же, что видит покупатель в корзине. Цена отсюда попадает и в форму, и в
 * пересчёт суммы заказа на сервере — расхождения между «показали» и
 * «посчитали» быть не может.
 */

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', 'save');

    if ($action === 'save') {
        foreach ((array)($_POST['title'] ?? []) as $methodId => $title) {
            $methodId = (int)$methodId;
            $title = trim((string)$title);
            if ($title === '') {
                continue;
            }

            cms_update('delivery_methods', [
                'title'       => $title,
                'description' => trim((string)($_POST['description'][$methodId] ?? '')),
                'price'       => admin_price_from_delivery((string)($_POST['price'][$methodId] ?? '')),
                'free_from'   => admin_price_from_delivery((string)($_POST['free_from'][$methodId] ?? '')),
                'is_active'   => !empty($_POST['is_active'][$methodId]) ? 1 : 0,
                'sort_order'  => (int)($_POST['sort_order'][$methodId] ?? 0),
            ], 'id = :id', ['id' => $methodId]);
        }

        cms_audit('delivery.save', null, null, 'Изменены способы доставки');
        admin_flash('Сохранено.');
        admin_redirect('delivery');
    }

    if ($action === 'add') {
        $title = (string)admin_post('new_title', '');
        if ($title === '') {
            admin_flash('Укажите название способа доставки.', 'bad');
        } else {
            cms_insert('delivery_methods', [
                'code'       => admin_slugify($title) ?: 'delivery-' . time(),
                'title'      => $title,
                'description' => '',
                'price'      => admin_price_from_delivery((string)admin_post('new_price', '')),
                'is_active'  => 1,
                'sort_order' => (int)cms_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM delivery_methods'),
            ]);
            cms_audit('delivery.add', null, null, 'Добавлен способ доставки: ' . $title);
            admin_flash('Способ доставки добавлен.');
        }
        admin_redirect('delivery');
    }
}

/**
 * Цена доставки. Пустое поле означает «по тарифам службы доставки» — это не
 * то же самое, что ноль (бесплатно).
 */
function admin_price_from_delivery(string $raw): ?float
{
    $raw = str_replace(["\u{00A0}", ' ', '₽'], '', trim($raw));
    $raw = str_replace(',', '.', $raw);

    return $raw === '' ? null : (is_numeric($raw) ? (float)$raw : null);
}

$methods = cms_all('SELECT * FROM delivery_methods ORDER BY sort_order, id');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Доставка</h1>
            <p>Эти способы покупатель видит в корзине. По ним же сервер считает итог заказа.</p>
          </div>
        </div>

        <form method="post" action="<?= e(admin_url('delivery')) ?>">
          <?= admin_csrf_field() ?>
          <input type="hidden" name="action" value="save" />
          <div class="adm-scroll">
            <table class="adm-table">
              <thead>
                <tr>
                  <th style="width:210px">Название</th>
                  <th>Подпись под названием</th>
                  <th class="num" style="width:120px">Цена, ₽</th>
                  <th class="num" style="width:130px">Бесплатно от, ₽</th>
                  <th class="num" style="width:90px">Порядок</th>
                  <th style="width:90px">Активен</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($methods as $method): $mid = (int)$method['id']; ?>
                <tr>
                  <td><input name="title[<?= $mid ?>]" type="text" value="<?= e((string)$method['title']) ?>" /></td>
                  <td>
                    <input name="description[<?= $mid ?>]" type="text" value="<?= e((string)$method['description']) ?>" />
                    <p class="note">Вместо <code>{цена}</code> подставится стоимость.</p>
                  </td>
                  <td class="num">
                    <input name="price[<?= $mid ?>]" type="text" inputmode="decimal" value="<?= e(cms_money_machine($method['price'] === null ? null : (float)$method['price'])) ?>" />
                    <p class="note">Пусто — по тарифам службы.</p>
                  </td>
                  <td class="num"><input name="free_from[<?= $mid ?>]" type="text" inputmode="decimal" value="<?= e(cms_money_machine($method['free_from'] === null ? null : (float)$method['free_from'])) ?>" /></td>
                  <td class="num"><input name="sort_order[<?= $mid ?>]" type="number" value="<?= (int)$method['sort_order'] ?>" /></td>
                  <td><input name="is_active[<?= $mid ?>]" type="checkbox" value="1"<?= (int)$method['is_active'] === 1 ? ' checked' : '' ?> /></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="adm-actions"><button class="adm-btn" type="submit">Сохранить</button></div>
        </form>

        <div class="adm-card" style="margin-top:18px">
          <h2>Добавить способ доставки</h2>
          <form method="post" action="<?= e(admin_url('delivery')) ?>">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="add" />
            <div class="adm-cols">
              <div class="adm-field">
                <label for="new_title">Название</label>
                <input id="new_title" name="new_title" type="text" required />
              </div>
              <div class="adm-field">
                <label for="new_price">Цена, ₽</label>
                <input id="new_price" name="new_price" type="text" inputmode="decimal" />
              </div>
            </div>
            <button class="adm-btn" type="submit">Добавить</button>
          </form>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
