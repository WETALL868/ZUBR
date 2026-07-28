<?php
/**
 * Правка обычной страницы.
 */

declare(strict_types=1);

$isNew = ($_GET['id'] ?? '') === 'new';
$id = $isNew ? 0 : (int)($_GET['id'] ?? 0);
$page = $isNew ? null : cms_one('SELECT * FROM pages WHERE id = ?', [$id]);

if (!$isNew && !$page) {
    admin_flash('Страница не найдена.', 'bad');
    admin_redirect('pages');
}

// Главная собирается из блоков — у неё свой экран.
if ($page && (string)$page['slug'] === '') {
    admin_redirect('home');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $title = (string)admin_post('title', '');

    if ($title === '') {
        admin_flash('Название обязательно.', 'bad');
    } else {
        $slug = admin_unique_slug('pages', admin_slugify((string)admin_post('slug', '') ?: $title), $id ?: null);

        $data = [
            'title'      => $title,
            'menu_title' => admin_post('menu_title') ?: null,
            'slug'       => $slug,
            'h1'         => admin_post('h1') ?: null,
            'content'    => admin_post('content') ?: null,
            'template'   => in_array(admin_post('template'), ['default', 'legal'], true) ? (string)admin_post('template') : 'default',
            'seo_title'  => admin_post('seo_title') ?: null,
            'seo_desc'   => admin_post('seo_desc') ?: null,
            'keywords'   => admin_post('keywords') ?: null,
            'canonical'  => admin_post('canonical') ?: null,
            'og_title'   => admin_post('og_title') ?: null,
            'og_desc'    => admin_post('og_desc') ?: null,
            'og_image'   => admin_post('og_image') ?: null,
            'preload_image' => admin_post('preload_image') ?: null,
            'noindex'    => admin_post_bool('noindex'),
            'status'     => admin_post_bool('published') ? 'published' : 'draft',
            'in_footer'  => admin_post_bool('in_footer'),
            'sort_order' => admin_post_int('sort_order'),
            'updated_at' => cms_now(),
            'updated_by' => (int)$user['id'],
        ];

        if ($id) {
            cms_update('pages', $data, 'id = :id', ['id' => $id]);
        } else {
            $data['created_at'] = cms_now();
            $id = cms_insert('pages', $data);
        }

        cms_audit('page.save', 'page', $id, 'Сохранена страница: ' . $title);
        admin_flash('Сохранено.');
        admin_redirect('page', ['id' => $id]);
    }
}

$page = $id ? cms_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
$v = static fn(string $field, string $default = ''): string => (string)($page[$field] ?? $default);

$screenTitle = $page ? (string)$page['title'] : 'Новая страница';

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1><?= $page ? e($screenTitle) : 'Новая страница' ?></h1>
            <?php if ($page): ?>
            <p><a href="/<?= e((string)$page['slug']) ?>/" target="_blank" rel="noopener">Открыть на сайте</a></p>
            <?php endif; ?>
          </div>
          <div class="adm-actions"><a class="adm-btn grey" href="<?= e(admin_url('pages')) ?>">К списку</a></div>
        </div>

        <form method="post" action="<?= e(admin_url('page', ['id' => $id ?: 'new'])) ?>">
          <?= admin_csrf_field() ?>

          <div class="adm-card">
            <div class="adm-cols">
              <div class="adm-field">
                <label for="title">Название</label>
                <input id="title" name="title" type="text" value="<?= e($v('title')) ?>" data-slug-source required />
              </div>
              <div class="adm-field">
                <label for="slug">Адрес</label>
                <input id="slug" name="slug" type="text" value="<?= e($v('slug')) ?>" data-slug-target />
                <p class="note">Страница будет по адресу /<b><?= e($v('slug', '…')) ?></b>/</p>
              </div>
            </div>
            <div class="adm-cols">
              <div class="adm-field">
                <label for="menu_title">Короткая подпись для меню и подвала</label>
                <input id="menu_title" name="menu_title" type="text" value="<?= e($v('menu_title')) ?>" />
              </div>
              <div class="adm-field">
                <label for="h1">Заголовок на странице (H1)</label>
                <input id="h1" name="h1" type="text" value="<?= e($v('h1')) ?>" />
              </div>
              <div class="adm-field">
                <label for="template">Оформление</label>
                <select id="template" name="template">
                  <option value="default"<?= $v('template') === 'default' ? ' selected' : '' ?>>Обычное — полная шапка сайта</option>
                  <option value="legal"<?= $v('template') === 'legal' ? ' selected' : '' ?>>Правовая страница — упрощённая шапка</option>
                </select>
              </div>
            </div>
            <div class="adm-field">
              <label for="content">Содержимое страницы (HTML)</label>
              <textarea id="content" name="content" class="tall"><?= e($v('content')) ?></textarea>
              <p class="note">Вставляется внутрь основной части страницы. Шапку и подвал добавит сайт.</p>
            </div>
            <div class="adm-cols">
              <div class="adm-field">
                <label for="sort_order">Порядок в подвале</label>
                <input id="sort_order" name="sort_order" type="number" value="<?= e($v('sort_order', '0')) ?>" />
              </div>
              <div class="adm-field">
                <label for="preload_image">Фон, который грузится заранее</label>
                <input id="preload_image" name="preload_image" type="text" value="<?= e($v('preload_image')) ?>" placeholder="/public/assets/…" />
              </div>
            </div>
            <label class="adm-check"><input type="checkbox" name="published" value="1"<?= $isNew || $v('status') === 'published' ? ' checked' : '' ?> /> Показывать на сайте</label>
            <label class="adm-check"><input type="checkbox" name="in_footer" value="1"<?= (int)$v('in_footer') === 1 ? ' checked' : '' ?> /> Ссылка в подвале сайта</label>
          </div>

          <div class="adm-card">
            <h2>SEO</h2>
            <div class="adm-field">
              <label for="seo_title">Заголовок вкладки (title)</label>
              <input id="seo_title" name="seo_title" type="text" value="<?= e($v('seo_title')) ?>" />
            </div>
            <div class="adm-field">
              <label for="seo_desc">Описание в выдаче</label>
              <textarea id="seo_desc" name="seo_desc"><?= e($v('seo_desc')) ?></textarea>
            </div>
            <div class="adm-cols">
              <div class="adm-field">
                <label for="keywords">Ключевые слова</label>
                <input id="keywords" name="keywords" type="text" value="<?= e($v('keywords')) ?>" />
              </div>
              <div class="adm-field">
                <label for="canonical">Канонический адрес</label>
                <input id="canonical" name="canonical" type="text" value="<?= e($v('canonical')) ?>" />
              </div>
            </div>
            <div class="adm-cols">
              <div class="adm-field">
                <label for="og_title">Заголовок при пересылке</label>
                <input id="og_title" name="og_title" type="text" value="<?= e($v('og_title')) ?>" />
              </div>
              <div class="adm-field">
                <label for="og_image">Картинка при пересылке</label>
                <input id="og_image" name="og_image" type="text" value="<?= e($v('og_image')) ?>" />
              </div>
            </div>
            <div class="adm-field">
              <label for="og_desc">Описание при пересылке</label>
              <textarea id="og_desc" name="og_desc"><?= e($v('og_desc')) ?></textarea>
            </div>
            <label class="adm-check"><input type="checkbox" name="noindex" value="1"<?= (int)$v('noindex') === 1 ? ' checked' : '' ?> /> Запретить индексацию</label>
          </div>

          <div class="adm-actions">
            <button class="adm-btn" type="submit">Сохранить</button>
            <a class="adm-btn grey" href="<?= e(admin_url('pages')) ?>">Отмена</a>
          </div>
        </form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
