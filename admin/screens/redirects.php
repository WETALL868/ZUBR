<?php
/**
 * Переадресация со старых адресов.
 *
 * Нужна ровно тогда, когда у страницы меняется адрес: без неё ссылки из
 * писем, закладок и поисковой выдачи ведут в никуда, а накопленные позиции
 * в поиске теряются.
 */

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');

    if ($action === 'add') {
        $from = admin_normalize_path((string)admin_post('from_path', ''));
        $to   = trim((string)admin_post('to_path', ''));

        if ($from === '' || $to === '') {
            admin_flash('Нужны оба адреса.', 'bad');
        } elseif ($from === admin_normalize_path($to)) {
            // Адрес, ведущий сам на себя, — это бесконечный цикл в браузере.
            admin_flash('Адреса совпадают: получилась бы переадресация на саму себя.', 'bad');
        } elseif (cms_value('SELECT id FROM redirects WHERE from_path = ?', [$from])) {
            admin_flash('Такая переадресация уже есть.', 'warn');
        } else {
            cms_insert('redirects', [
                'from_path'  => $from,
                'to_path'    => $to,
                'code'       => admin_post_int('code', 301) === 302 ? 302 : 301,
                'is_active'  => 1,
                'created_at' => cms_now(),
            ]);
            cms_audit('redirect.add', null, null, 'Переадресация ' . $from . ' -> ' . $to);
            admin_flash('Переадресация добавлена.');
        }
    }

    if ($action === 'delete') {
        $id = admin_post_int('id');
        cms_query('DELETE FROM redirects WHERE id = ?', [$id]);
        cms_audit('redirect.delete', null, $id, 'Удалена переадресация');
        admin_flash('Удалено.', 'warn');
    }

    admin_redirect('redirects');
}

/** Приводит адрес к виду /что-то/ — так их сравнивает маршрутизатор сайта. */
function admin_normalize_path(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('~^https?://~i', $path)) {
        return $path;
    }

    return '/' . trim($path, '/') . '/';
}

$items = cms_all('SELECT * FROM redirects ORDER BY id DESC');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Переадресация</h1>
            <p>Если у товара или страницы изменился адрес — добавьте сюда старый, чтобы ссылки продолжали работать.</p>
          </div>
        </div>

        <div class="adm-card">
          <h2>Добавить</h2>
          <form method="post" action="<?= e(admin_url('redirects')) ?>">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="add" />
            <div class="adm-cols">
              <div class="adm-field">
                <label for="from_path">Старый адрес</label>
                <input id="from_path" name="from_path" type="text" placeholder="/products/staryy-adres/" required />
              </div>
              <div class="adm-field">
                <label for="to_path">Новый адрес</label>
                <input id="to_path" name="to_path" type="text" placeholder="/products/novyy-adres/" required />
              </div>
              <div class="adm-field">
                <label for="code">Тип</label>
                <select id="code" name="code">
                  <option value="301">301 — навсегда (обычный случай)</option>
                  <option value="302">302 — временно</option>
                </select>
              </div>
            </div>
            <button class="adm-btn" type="submit">Добавить</button>
          </form>
        </div>

        <?php if (!$items): ?>
        <div class="adm-card"><p class="hint">Пока ни одной переадресации.</p></div>
        <?php else: ?>
        <div class="adm-scroll">
          <table class="adm-table">
            <thead><tr><th>Старый адрес</th><th>Новый адрес</th><th>Тип</th><th class="num">Переходов</th><th class="num">Действия</th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e((string)$item['from_path']) ?></td>
                <td><?= e((string)$item['to_path']) ?></td>
                <td><?= (int)$item['code'] ?></td>
                <td class="num"><?= (int)$item['hits'] ?></td>
                <td class="num">
                  <form method="post" action="<?= e(admin_url('redirects')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
                    <button class="adm-btn small danger" name="action" value="delete" type="submit"
                            data-confirm="Удалить переадресацию?">Удалить</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
