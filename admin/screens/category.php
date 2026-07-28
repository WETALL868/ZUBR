<?php
/**
 * Правка категории.
 *
 * Полей больше, чем ожидаешь от «названия и описания», потому что на прежних
 * страницах почти каждая подпись была написана вручную под конкретную
 * категорию: и заголовок над сеткой, и текст карточки «нужен другой
 * процессор», и абзац в подвале. Вывести их из названия нельзя — их можно
 * только сохранить и дать редактировать.
 */

declare(strict_types=1);

$isNew = ($_GET['id'] ?? '') === 'new';
$id = $isNew ? 0 : (int)($_GET['id'] ?? 0);
$category = $isNew ? null : repo_category_by_id($id);

if (!$isNew && !$category) {
    admin_flash('Категория не найдена.', 'bad');
    admin_redirect('categories');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = (string)admin_post('name', '');

    if ($name === '') {
        admin_flash('Название обязательно.', 'bad');
    } else {
        $slug = admin_unique_slug('categories', admin_slugify((string)admin_post('slug', '') ?: $name), $id ?: null);

        $data = [
            'name'           => $name,
            'slug'           => $slug,
            'h1'             => admin_post('h1') ?: null,
            'lead'           => admin_post('lead') ?: null,
            'label'          => admin_post('label') ?: null,
            'stock_title'    => admin_post('stock_title') ?: null,
            'grid_anchor'    => admin_slugify((string)admin_post('grid_anchor', '')) ?: 'stock',
            'selection_text' => admin_post('selection_text') ?: null,
            'request_goal'   => admin_post('request_goal') ?: null,
            'request_badge'  => admin_post('request_badge') ?: null,
            'request_status' => admin_post('request_status') ?: null,
            'request_title'  => admin_post('request_title') ?: null,
            'request_text'   => admin_post('request_text') ?: null,
            'request_tags'   => admin_post('request_tags') ?: null,
            'request_button' => admin_post('request_button') ?: null,
            'cross_text'     => admin_post('cross_text') ?: null,
            'cross_button'   => admin_post('cross_button') ?: null,
            'footer_text'    => admin_post('footer_text') ?: null,
            'list_name'      => admin_post('list_name') ?: null,
            'list_desc'      => admin_post('list_desc') ?: null,
            'seo_title'      => admin_post('seo_title') ?: null,
            'seo_desc'       => admin_post('seo_desc') ?: null,
            'og_title'       => admin_post('og_title') ?: null,
            'og_desc'        => admin_post('og_desc') ?: null,
            'yml_category_id' => admin_post('yml_category_id') === '' ? null : admin_post_int('yml_category_id'),
            'yml_name'       => admin_post('yml_name') ?: null,
            'noindex'        => admin_post_bool('noindex'),
            'is_published'   => admin_post_bool('is_published'),
            'sort_order'     => admin_post_int('sort_order'),
            'updated_at'     => cms_now(),
        ];

        if ($id) {
            cms_update('categories', $data, 'id = :id', ['id' => $id]);
        } else {
            $data['created_at'] = cms_now();
            $id = cms_insert('categories', $data);
        }

        cms_audit('category.save', 'category', $id, 'Сохранена категория: ' . $name);
        admin_flash('Сохранено.');
        admin_redirect('category', ['id' => $id]);
    }
}

$category = $id ? repo_category_by_id($id) : null;
$v = static fn(string $field, string $default = ''): string => (string)($category[$field] ?? $default);

$screenTitle = $category ? (string)$category['name'] : 'Новая категория';

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1><?= $category ? e($screenTitle) : 'Новая категория' ?></h1>
            <?php if ($category): ?>
            <p><a href="<?= e(repo_category_url($category)) ?>" target="_blank" rel="noopener">Открыть на сайте</a></p>
            <?php endif; ?>
          </div>
          <div class="adm-actions"><a class="adm-btn grey" href="<?= e(admin_url('categories')) ?>">К списку</a></div>
        </div>

        <div class="adm-tabs" data-tabs>
          <button class="adm-tab active" type="button" data-tab="main">Основное</button>
          <button class="adm-tab" type="button" data-tab="texts">Тексты страницы</button>
          <button class="adm-tab" type="button" data-tab="seo">SEO и фид</button>
        </div>

        <form method="post" action="<?= e(admin_url('category', ['id' => $id ?: 'new'])) ?>">
          <?= admin_csrf_field() ?>

          <div data-panel="main">
            <div class="adm-card">
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="name">Название</label>
                  <input id="name" name="name" type="text" value="<?= e($v('name')) ?>" data-slug-source required />
                </div>
                <div class="adm-field">
                  <label for="slug">Адрес</label>
                  <input id="slug" name="slug" type="text" value="<?= e($v('slug')) ?>" data-slug-target />
                  <p class="note">Страница будет по адресу /<b><?= e($v('slug', '…')) ?></b>/</p>
                </div>
                <div class="adm-field">
                  <label for="sort_order">Порядок</label>
                  <input id="sort_order" name="sort_order" type="number" value="<?= e($v('sort_order', '0')) ?>" />
                </div>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="h1">Заголовок на странице (H1)</label>
                  <input id="h1" name="h1" type="text" value="<?= e($v('h1')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="label">Подпись над заголовком</label>
                  <input id="label" name="label" type="text" value="<?= e($v('label')) ?>" placeholder="Каталог · категория 1 из 2" />
                </div>
              </div>
              <div class="adm-field">
                <label for="lead">Вступительный текст</label>
                <textarea id="lead" name="lead"><?= e($v('lead')) ?></textarea>
              </div>
              <div class="adm-field">
                <label for="selection_text">Второй абзац вступления</label>
                <textarea id="selection_text" name="selection_text"><?= e($v('selection_text')) ?></textarea>
              </div>
              <label class="adm-check"><input type="checkbox" name="is_published" value="1"<?= $isNew || (int)$v('is_published') === 1 ? ' checked' : '' ?> /> Показывать на сайте</label>
            </div>
          </div>

          <div data-panel="texts" hidden>
            <div class="adm-card">
              <h2>Витрина товаров</h2>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="stock_title">Заголовок над сеткой товаров</label>
                  <input id="stock_title" name="stock_title" type="text" value="<?= e($v('stock_title')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="grid_anchor">Якорь секции</label>
                  <input id="grid_anchor" name="grid_anchor" type="text" value="<?= e($v('grid_anchor', 'stock')) ?>" />
                  <p class="note">Часть адреса после решётки: /<?= e($v('slug', 'категория')) ?>/#<?= e($v('grid_anchor', 'stock')) ?></p>
                </div>
              </div>
            </div>

            <div class="adm-card">
              <h2>Карточка «нужен другой товар»</h2>
              <p class="hint">Последняя плитка в сетке — предложение подобрать то, чего нет в наличии.</p>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="request_badge">Значок</label>
                  <input id="request_badge" name="request_badge" type="text" value="<?= e($v('request_badge')) ?>" placeholder="Xeon" />
                </div>
                <div class="adm-field">
                  <label for="request_status">Подпись сверху</label>
                  <input id="request_status" name="request_status" type="text" value="<?= e($v('request_status')) ?>" placeholder="Под заказ" />
                </div>
                <div class="adm-field">
                  <label for="request_title">Заголовок</label>
                  <input id="request_title" name="request_title" type="text" value="<?= e($v('request_title')) ?>" />
                </div>
              </div>
              <div class="adm-field">
                <label for="request_text">Текст</label>
                <textarea id="request_text" name="request_text"><?= e($v('request_text')) ?></textarea>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="request_tags">Метки, через запятую</label>
                  <input id="request_tags" name="request_tags" type="text" value="<?= e($v('request_tags')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="request_button">Надпись на кнопке</label>
                  <input id="request_button" name="request_button" type="text" value="<?= e($v('request_button')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="request_goal">Метка цели для статистики</label>
                  <input id="request_goal" name="request_goal" type="text" value="<?= e($v('request_goal')) ?>" />
                </div>
              </div>
            </div>

            <div class="adm-card">
              <h2>Как категорию описывают на других страницах</h2>
              <p class="hint">Этот текст показывается в блоке «другая категория» — то есть на странице соседней категории, а не на своей.</p>
              <div class="adm-field">
                <label for="cross_text">Описание</label>
                <textarea id="cross_text" name="cross_text"><?= e($v('cross_text')) ?></textarea>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="cross_button">Надпись на кнопке</label>
                  <input id="cross_button" name="cross_button" type="text" value="<?= e($v('cross_button')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="footer_text">Абзац о компании в подвале этой страницы</label>
                  <input id="footer_text" name="footer_text" type="text" value="<?= e($v('footer_text')) ?>" />
                </div>
              </div>
            </div>
          </div>

          <div data-panel="seo" hidden>
            <div class="adm-card">
              <h2>Поиск</h2>
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
                  <label for="list_name">Название списка товаров для поисковика</label>
                  <input id="list_name" name="list_name" type="text" value="<?= e($v('list_name')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="list_desc">Описание списка</label>
                  <input id="list_desc" name="list_desc" type="text" value="<?= e($v('list_desc')) ?>" />
                </div>
              </div>
              <label class="adm-check"><input type="checkbox" name="noindex" value="1"<?= (int)$v('noindex') === 1 ? ' checked' : '' ?> /> Запретить индексацию</label>
            </div>

            <div class="adm-card">
              <h2>При пересылке ссылки</h2>
              <div class="adm-field">
                <label for="og_title">Заголовок</label>
                <input id="og_title" name="og_title" type="text" value="<?= e($v('og_title')) ?>" />
              </div>
              <div class="adm-field">
                <label for="og_desc">Описание</label>
                <textarea id="og_desc" name="og_desc"><?= e($v('og_desc')) ?></textarea>
              </div>
            </div>

            <div class="adm-card">
              <h2>Яндекс.Маркет</h2>
              <p class="hint">Номер и название категории в фиде менять не стоит: маркетплейс привязывает к ним товары.</p>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="yml_category_id">Номер категории в фиде</label>
                  <input id="yml_category_id" name="yml_category_id" type="number" value="<?= e($v('yml_category_id')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="yml_name">Название в фиде</label>
                  <input id="yml_name" name="yml_name" type="text" value="<?= e($v('yml_name')) ?>" />
                </div>
              </div>
            </div>
          </div>

          <div class="adm-actions">
            <button class="adm-btn" type="submit">Сохранить</button>
            <a class="adm-btn grey" href="<?= e(admin_url('categories')) ?>">Отмена</a>
          </div>
        </form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
