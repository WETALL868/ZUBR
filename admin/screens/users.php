<?php
/**
 * Пользователи панели. Доступно только владельцу.
 *
 * Пароль задаётся здесь и сразу превращается в хеш — в базе, в журнале и в
 * резервной копии он никогда не встречается в открытом виде.
 */

declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');
    $id = admin_post_int('id');
    $target = $id ? cms_one('SELECT * FROM admin_users WHERE id = ?', [$id]) : null;

    if ($action === 'add') {
        $login = (string)admin_post('login', '');
        $password = (string)($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            admin_flash('Нужны логин и пароль.', 'bad');
        } elseif (mb_strlen($password) < 10) {
            admin_flash('Пароль короче десяти символов подбирается за минуты. Задайте длиннее.', 'bad');
        } elseif (cms_value('SELECT id FROM admin_users WHERE login = ?', [$login])) {
            admin_flash('Такой логин уже есть.', 'bad');
        } else {
            $newId = cms_insert('admin_users', [
                'name'          => admin_post('name') ?: $login,
                'login'         => $login,
                'email'         => admin_post('email') ?: null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => admin_post('role') === 'owner' ? 'owner' : 'manager',
                'is_active'     => 1,
                'session_epoch' => 1,
                'created_at'    => cms_now(),
            ]);
            cms_audit('user.add', 'admin_user', $newId, 'Создан пользователь: ' . $login);
            admin_flash('Пользователь создан.');
        }
    }

    if ($target && $action === 'password') {
        $password = (string)($_POST['password'] ?? '');
        if (mb_strlen($password) < 10) {
            admin_flash('Пароль должен быть не короче десяти символов.', 'bad');
        } else {
            cms_update('admin_users', [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                // Смена пароля завершает все открытые сессии этого человека —
                // ради этого смену пароля обычно и делают.
                'session_epoch' => (int)$target['session_epoch'] + 1,
                'updated_at'    => cms_now(),
            ], 'id = :id', ['id' => $id]);
            cms_audit('user.password', 'admin_user', $id, 'Изменён пароль: ' . $target['login']);
            admin_flash('Пароль изменён, все открытые сессии этого пользователя завершены.');
        }
    }

    if ($target && $action === 'toggle') {
        if ((int)$target['id'] === (int)$user['id']) {
            admin_flash('Отключить самого себя нельзя — иначе панель останется без входа.', 'bad');
        } else {
            $now = (int)$target['is_active'] === 1 ? 0 : 1;
            cms_update('admin_users', [
                'is_active'     => $now,
                'session_epoch' => (int)$target['session_epoch'] + 1,
                'updated_at'    => cms_now(),
            ], 'id = :id', ['id' => $id]);
            cms_audit('user.toggle', 'admin_user', $id, ($now ? 'Включён: ' : 'Отключён: ') . $target['login']);
            admin_flash($now ? 'Доступ включён.' : 'Доступ отключён, открытые сессии завершены.');
        }
    }

    if ($target && $action === 'logout-all') {
        cms_update('admin_users', ['session_epoch' => (int)$target['session_epoch'] + 1], 'id = :id', ['id' => $id]);
        cms_audit('user.logout-all', 'admin_user', $id, 'Завершены сессии: ' . $target['login']);
        admin_flash('Все сессии этого пользователя завершены.');
    }

    admin_redirect('users');
}

$users = cms_all('SELECT * FROM admin_users ORDER BY id');

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Пользователи</h1>
            <p>Владелец видит журнал действий и управляет доступом. Менеджер работает с каталогом и заказами.</p>
          </div>
        </div>

        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr><th>Имя</th><th>Логин</th><th>Права</th><th>Последний вход</th><th>Состояние</th><th class="num">Действия</th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $row): ?>
              <tr>
                <td><?= e((string)$row['name']) ?><?= (int)$row['id'] === (int)$user['id'] ? ' <span class="adm-tag">это вы</span>' : '' ?></td>
                <td><?= e((string)$row['login']) ?></td>
                <td><?= $row['role'] === 'owner' ? 'владелец' : 'менеджер' ?></td>
                <td><?= $row['last_login_at'] ? e(date('d.m.Y H:i', strtotime((string)$row['last_login_at']))) : '—' ?></td>
                <td><?= (int)$row['is_active'] === 1
                    ? '<span class="adm-tag ok">активен</span>'
                    : '<span class="adm-tag bad">отключён</span>' ?></td>
                <td class="num">
                  <form method="post" action="<?= e(admin_url('users')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                    <input name="password" type="password" placeholder="новый пароль" style="width:150px;display:inline-block" autocomplete="new-password" />
                    <button class="adm-btn small ghost" name="action" value="password" type="submit">Сменить</button>
                    <button class="adm-btn small grey" name="action" value="logout-all" type="submit">Завершить сессии</button>
                    <?php if ((int)$row['id'] !== (int)$user['id']): ?>
                    <button class="adm-btn small grey" name="action" value="toggle" type="submit"><?= (int)$row['is_active'] === 1 ? 'Отключить' : 'Включить' ?></button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="adm-card" style="margin-top:18px">
          <h2>Добавить пользователя</h2>
          <form method="post" action="<?= e(admin_url('users')) ?>" autocomplete="off">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="add" />
            <div class="adm-cols">
              <div class="adm-field">
                <label for="name">Имя</label>
                <input id="name" name="name" type="text" />
              </div>
              <div class="adm-field">
                <label for="login">Логин</label>
                <input id="login" name="login" type="text" required />
              </div>
              <div class="adm-field">
                <label for="email">Почта</label>
                <input id="email" name="email" type="email" />
              </div>
              <div class="adm-field">
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required />
                <p class="note">Не короче десяти символов.</p>
              </div>
              <div class="adm-field">
                <label for="role">Права</label>
                <select id="role" name="role">
                  <option value="manager">Менеджер</option>
                  <option value="owner">Владелец</option>
                </select>
              </div>
            </div>
            <button class="adm-btn" type="submit">Создать</button>
          </form>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
