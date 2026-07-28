<?php
/**
 * Главная страница: порядок, видимость и содержимое секций.
 *
 * Главная — не набор товаров, а посадочная страница из семнадцати непохожих
 * друг на друга секций: первый экран с фотографией, полоса преимуществ, две
 * витрины, форма заказа, карта, реквизиты. Втискивать их в общий вид
 * «заголовок плюс текст» значило бы потерять вёрстку, поэтому каждая секция
 * правится своей разметкой, а витрины товаров собираются из каталога.
 */

declare(strict_types=1);

$editing = (int)($_GET['block'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');
    $id = admin_post_int('id');
    $block = $id ? cms_one('SELECT * FROM home_blocks WHERE id = ?', [$id]) : null;

    if ($block && $action === 'toggle') {
        $now = (int)$block['is_enabled'] === 1 ? 0 : 1;
        cms_update('home_blocks', ['is_enabled' => $now, 'updated_at' => cms_now()], 'id = :id', ['id' => $id]);
        cms_audit('home.toggle', 'home_block', $id, ($now ? 'Показан блок: ' : 'Скрыт блок: ') . $block['title']);
        admin_flash($now ? 'Секция снова на главной.' : 'Секция убрана с главной.');
        admin_redirect('home');
    }

    if ($block && $action === 'move') {
        // Перестановка меняется обменом позициями с соседом: так порядок
        // остаётся понятным числом, а не «перетащите мышью».
        $direction = admin_post('dir') === 'up' ? 'up' : 'down';
        $neighbour = cms_one(
            $direction === 'up'
                ? 'SELECT * FROM home_blocks WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1'
                : 'SELECT * FROM home_blocks WHERE sort_order > ? ORDER BY sort_order LIMIT 1',
            [(int)$block['sort_order']]
        );
        if ($neighbour) {
            cms_update('home_blocks', ['sort_order' => (int)$neighbour['sort_order']], 'id = :id', ['id' => $id]);
            cms_update('home_blocks', ['sort_order' => (int)$block['sort_order']], 'id = :id', ['id' => (int)$neighbour['id']]);
            cms_audit('home.move', 'home_block', $id, 'Переставлен блок: ' . $block['title']);
        }
        admin_redirect('home');
    }

    if ($block && $action === 'save') {
        $data = [
            'title'      => admin_post('title') ?: $block['title'],
            'body'       => (string)($_POST['body'] ?? ''),
            'updated_at' => cms_now(),
        ];

        if ($block['kind'] === 'products') {
            $data['body_after'] = (string)($_POST['body_after'] ?? '');
            $data['settings'] = json_encode(
                array_filter([
                    'category' => admin_post('category') ?: null,
                    'limit'    => admin_post_int('limit') ?: null,
                ]),
                JSON_UNESCAPED_UNICODE
            );
        }

        cms_update('home_blocks', $data, 'id = :id', ['id' => $id]);
        cms_audit('home.save', 'home_block', $id, 'Сохранён блок главной: ' . $data['title']);
        admin_flash('Сохранено.');
        admin_redirect('home', ['block' => $id]);
    }
}

$blocks = cms_all('SELECT * FROM home_blocks ORDER BY sort_order, id');
$current = $editing ? cms_one('SELECT * FROM home_blocks WHERE id = ?', [$editing]) : null;

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Главная страница</h1>
            <p>Секции идут сверху вниз в том же порядке, что и здесь.</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn grey" href="/" target="_blank" rel="noopener">Открыть главную</a>
          </div>
        </div>

        <div class="adm-scroll">
          <table class="adm-table">
            <thead>
              <tr><th>Секция</th><th>Что внутри</th><th>Состояние</th><th class="num">Порядок</th><th class="num">Действия</th></tr>
            </thead>
            <tbody>
              <?php foreach ($blocks as $block):
                  $settings = repo_home_block_settings($block); ?>
              <tr<?= (int)$block['id'] === $editing ? ' style="background:var(--accent-soft)"' : '' ?>>
                <td>
                  <a href="<?= e(admin_url('home', ['block' => $block['id']])) ?>"><?= e((string)$block['title']) ?></a>
                  <div style="color:var(--muted);font-size:12px"><?= e((string)$block['code']) ?></div>
                </td>
                <td>
                  <?php if ($block['kind'] === 'products'): ?>
                  <span class="adm-tag ok">витрина товаров</span>
                  <?= e((string)($settings['category'] ?? '')) ?>
                  <?php else: ?>
                  <span class="adm-tag">разметка</span> <?= number_format(mb_strlen((string)$block['body']), 0, ',', ' ') ?> симв.
                  <?php endif; ?>
                </td>
                <td><?= (int)$block['is_enabled'] === 1
                    ? '<span class="adm-tag ok">видна</span>'
                    : '<span class="adm-tag">скрыта</span>' ?></td>
                <td class="num"><?= (int)$block['sort_order'] ?></td>
                <td class="num">
                  <form method="post" action="<?= e(admin_url('home')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$block['id'] ?>" />
                    <input type="hidden" name="action" value="move" />
                    <button class="adm-btn small grey" name="dir" value="up" type="submit" aria-label="Выше">↑</button>
                    <button class="adm-btn small grey" name="dir" value="down" type="submit" aria-label="Ниже">↓</button>
                  </form>
                  <form method="post" action="<?= e(admin_url('home')) ?>" class="adm-inline">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$block['id'] ?>" />
                    <button class="adm-btn small grey" name="action" value="toggle" type="submit"><?= (int)$block['is_enabled'] === 1 ? 'Скрыть' : 'Показать' ?></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($current): ?>
        <form method="post" action="<?= e(admin_url('home')) ?>" style="margin-top:18px">
          <?= admin_csrf_field() ?>
          <input type="hidden" name="action" value="save" />
          <input type="hidden" name="id" value="<?= (int)$current['id'] ?>" />

          <div class="adm-card">
            <h2>Секция «<?= e((string)$current['title']) ?>»</h2>
            <div class="adm-field">
              <label for="title">Как называть эту секцию в панели</label>
              <input id="title" name="title" type="text" value="<?= e((string)$current['title']) ?>" />
            </div>

            <?php if ($current['kind'] === 'products'):
                $settings = repo_home_block_settings($current); ?>
            <div class="adm-cols">
              <div class="adm-field">
                <label for="category">Какие товары показывать</label>
                <select id="category" name="category">
                  <?php foreach (repo_categories(false) as $c): ?>
                  <option value="<?= e($c['slug']) ?>"<?= ($settings['category'] ?? '') === $c['slug'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="adm-field">
                <label for="limit">Сколько товаров показывать</label>
                <input id="limit" name="limit" type="number" value="<?= e((string)($settings['limit'] ?? '')) ?>" />
                <p class="note">Пусто — все товары категории.</p>
              </div>
            </div>
            <div class="adm-field">
              <label for="body">Разметка до сетки товаров</label>
              <textarea id="body" name="body" class="tall"><?= e((string)$current['body']) ?></textarea>
            </div>
            <div class="adm-field">
              <label for="body_after">Разметка после сетки товаров</label>
              <textarea id="body_after" name="body_after" class="tall"><?= e((string)$current['body_after']) ?></textarea>
              <p class="note">Здесь лежит карточка «нужен другой товар» и закрывающие теги секции.</p>
            </div>
            <?php else: ?>
            <div class="adm-field">
              <label for="body">Разметка секции</label>
              <textarea id="body" name="body" class="tall" style="min-height:420px"><?= e((string)$current['body']) ?></textarea>
              <p class="note">
                Это готовый кусок страницы вместе с тегами <code>&lt;section&gt;</code>. Меняйте текст внутри тегов;
                если удалить сами теги, вёрстка страницы поедет.
              </p>
            </div>
            <?php endif; ?>

            <div class="adm-actions">
              <button class="adm-btn" type="submit">Сохранить секцию</button>
              <a class="adm-btn grey" href="<?= e(admin_url('home')) ?>">Закрыть</a>
            </div>
          </div>
        </form>
        <?php else: ?>
        <div class="adm-card" style="margin-top:18px">
          <p class="hint">Выберите секцию в списке, чтобы поправить её содержимое.</p>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
