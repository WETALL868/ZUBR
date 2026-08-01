<?php
/**
 * Карточка товара: одна форма, разложенная по вкладкам.
 *
 * Всё, что видно на сайте, редактируется здесь — включая тексты, которые
 * раньше существовали только в разметке страницы: подписи карточки каталога,
 * SEO-статью, вопросы и ответы и комментарии в блоке «похожие товары».
 *
 * Сохранение одно на всю форму: разбивать на отдельные кнопки по вкладкам
 * означало бы, что незамеченная вкладка молча теряет правки.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/uploads.php';

$isNew = ($_GET['id'] ?? '') === 'new';
$id = $isNew ? 0 : (int)($_GET['id'] ?? 0);

$product = $isNew ? null : repo_product_by_id($id);

if (!$isNew && !$product) {
    admin_flash('Товар не найден.', 'bad');
    admin_redirect('products');
}

/* ------------------------------------------------------------ сохранение */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', 'save');

    /* --- фотографии обрабатываются отдельно: они не часть общей формы --- */

    if ($action === 'photo-upload' && $id) {
        $result = upload_image($_FILES['photo'] ?? [], (string)$product['slug']);
        if ($result['ok']) {
            $hasMain = (int)cms_value('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$id]) === 0;
            $imageId = cms_insert('product_images', [
                'product_id' => $id,
                'path'       => $result['path'],
                'alt'        => $product['name'],
                'is_main'    => $hasMain ? 1 : 0,
                'width'      => $result['width'],
                'height'     => $result['height'],
                'filesize'   => $result['size'],
                'sort_order' => (int)cms_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM product_images WHERE product_id = ?', [$id]),
                'created_at' => cms_now(),
            ]);
            cms_audit('product.photo', 'product', $id, 'Загружена фотография ' . $result['path']);
            admin_flash('Фотография загружена.' . ($hasMain ? ' Она стала главной.' : ''));
        } else {
            admin_flash($result['message'], 'bad');
        }
        admin_redirect('product', ['id' => $id]);
    }

    if ($action === 'photo-main' && $id) {
        upload_set_main($id, admin_post_int('image_id'));
        admin_flash('Главная фотография изменена.');
        admin_redirect('product', ['id' => $id]);
    }

    if ($action === 'photo-delete' && $id) {
        upload_delete(admin_post_int('image_id'));
        admin_flash('Фотография удалена.', 'warn');
        admin_redirect('product', ['id' => $id]);
    }

    /* ------------------------------- основное сохранение всей карточки --- */

    $name = (string)admin_post('name', '');
    if ($name === '') {
        admin_flash('Название обязательно: без него товар не найдут ни в панели, ни в поиске.', 'bad');
    } else {
        $slug = admin_slugify((string)admin_post('slug', '') ?: $name);
        $slug = admin_unique_slug('products', $slug, $id ?: null);

        $newPrice = admin_post_decimal('price');
        $oldPriceValue = $product ? repo_price($product) : null;

        $data = [
            'category_id'    => admin_post_int('category_id') ?: null,
            'name'           => $name,
            'short_name'     => admin_post('short_name') ?: null,
            'model'          => admin_post('model') ?: null,
            'slug'           => $slug,
            'sku'            => admin_post('sku') ?: null,
            'mpn'            => admin_post('mpn') ?: null,
            'brand'          => admin_post('brand') ?: null,
            'lead'           => admin_post('lead') ?: null,
            'description'    => admin_post('description') ?: null,
            'article_title'  => admin_post('article_title') ?: null,
            'article_html'   => admin_post('article_html') ?: null,
            'price'          => $newPrice,
            'old_price'      => admin_post_decimal('old_price'),
            'cost_price'     => admin_post_decimal('cost_price'),
            'stock_qty'      => admin_post('stock_qty') === '' ? null : admin_post_int('stock_qty'),
            'availability'   => in_array(admin_post('availability'), ['in_stock', 'preorder', 'out_of_stock'], true)
                                    ? (string)admin_post('availability') : 'in_stock',
            'preorder_note'  => (string)admin_post('preorder_note', '') ?: null,
            'unit'           => admin_post('unit') ?: 'шт.',
            'warranty'       => admin_post('warranty') ?: null,
            'condition_note' => admin_post('condition_note') ?: null,
            'country'        => admin_post('country') ?: null,
            'is_published'   => admin_post_bool('is_published'),
            'is_featured'    => admin_post_bool('is_featured'),
            'in_yml'         => admin_post_bool('in_yml'),
            'sort_order'     => admin_post_int('sort_order'),
            'card_title'         => admin_post('card_title') ?: null,
            'card_lead'          => admin_post('card_lead') ?: null,
            'card_alt'           => admin_post('card_alt') ?: null,
            'card_figure_label'  => admin_post('card_figure_label') ?: null,
            'card_tags'          => admin_post('card_tags') ?: null,
            'seo_title'      => admin_post('seo_title') ?: null,
            'seo_desc'       => admin_post('seo_desc') ?: null,
            'seo_h1'         => admin_post('seo_h1') ?: null,
            'canonical'      => admin_post('canonical') ?: null,
            'og_title'       => admin_post('og_title') ?: null,
            'og_desc'        => admin_post('og_desc') ?: null,
            'og_image'       => admin_post('og_image') ?: null,
            'breadcrumb'     => admin_post('breadcrumb') ?: null,
            'noindex'        => admin_post_bool('noindex'),
            'updated_at'     => cms_now(),
            'updated_by'     => (int)$user['id'],
        ];

        if ($id) {
            // Снимок «как было» — чтобы неудачную правку можно было откатить.
            cms_insert('revisions', [
                'entity'     => 'product',
                'entity_id'  => $id,
                'payload'    => json_encode($product, JSON_UNESCAPED_UNICODE),
                'admin_id'   => (int)$user['id'],
                'created_at' => cms_now(),
            ]);
            cms_update('products', $data, 'id = :id', ['id' => $id]);
        } else {
            $data['created_at'] = cms_now();
            $id = cms_insert('products', $data);
        }

        // История цен ведётся отдельно: «почему у нас в марте было дешевле»
        // — вопрос, на который иначе нечем ответить.
        if ($oldPriceValue !== $newPrice) {
            cms_insert('price_history', [
                'product_id' => $id,
                'old_price'  => $oldPriceValue,
                'new_price'  => $newPrice,
                'admin_id'   => (int)$user['id'],
                'created_at' => cms_now(),
            ]);
        }

        /* --- характеристики --- */

        cms_query('DELETE FROM product_attribute_values WHERE product_id = ?', [$id]);
        $attrNames  = (array)($_POST['attr_name'] ?? []);
        $attrValues = (array)($_POST['attr_value'] ?? []);
        $order = 0;
        foreach ($attrNames as $i => $attrName) {
            $attrName = trim((string)$attrName);
            $attrValue = trim((string)($attrValues[$i] ?? ''));
            if ($attrName === '' || $attrValue === '') {
                continue;
            }

            // Словарь характеристик общий на весь каталог: одинаково
            // названные строки у разных товаров — одна характеристика.
            $code = admin_slugify($attrName) ?: 'attr-' . $i;
            $attrId = cms_value('SELECT id FROM attributes WHERE code = ?', [$code]);
            if (!$attrId) {
                $attrId = cms_insert('attributes', [
                    'name' => $attrName, 'code' => $code,
                    'sort_order' => (int)cms_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM attributes'),
                    'created_at' => cms_now(),
                ]);
            }

            cms_insert('product_attribute_values', [
                'product_id'   => $id,
                'attribute_id' => (int)$attrId,
                'value'        => $attrValue,
                'sort_order'   => $order += 10,
            ]);
        }

        /* --- короткие характеристики карточки каталога --- */

        cms_query('DELETE FROM product_card_specs WHERE product_id = ?', [$id]);
        $specNames  = (array)($_POST['spec_name'] ?? []);
        $specValues = (array)($_POST['spec_value'] ?? []);
        $order = 0;
        foreach ($specNames as $i => $specName) {
            $specName = trim((string)$specName);
            if ($specName === '') {
                continue;
            }
            cms_insert('product_card_specs', [
                'product_id' => $id,
                'name'       => $specName,
                'value'      => trim((string)($specValues[$i] ?? '')),
                'sort_order' => $order += 10,
            ]);
        }

        /* --- вопросы и ответы --- */

        cms_query('DELETE FROM product_faq WHERE product_id = ?', [$id]);
        $questions = (array)($_POST['faq_q'] ?? []);
        $answers   = (array)($_POST['faq_a'] ?? []);
        $order = 0;
        foreach ($questions as $i => $question) {
            $question = trim((string)$question);
            $answer = trim((string)($answers[$i] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            cms_insert('product_faq', [
                'product_id' => $id,
                'question'   => $question,
                'answer'     => $answer,
                'sort_order' => $order += 10,
            ]);
        }

        /* --- похожие товары --- */

        cms_query('DELETE FROM product_relations WHERE product_id = ? AND relation = ?', [$id, 'similar']);
        $relatedIds   = (array)($_POST['rel_id'] ?? []);
        $relatedNotes = (array)($_POST['rel_note'] ?? []);
        $order = 0;
        foreach ($relatedIds as $i => $relatedId) {
            $relatedId = (int)$relatedId;
            if ($relatedId <= 0 || $relatedId === $id) {
                continue;
            }
            cms_insert('product_relations', [
                'product_id' => $id,
                'related_id' => $relatedId,
                'relation'   => 'similar',
                'note'       => trim((string)($relatedNotes[$i] ?? '')) ?: null,
                'sort_order' => $order += 10,
            ]);
        }

        cms_audit('product.save', 'product', $id, 'Сохранён товар: ' . $name);
        admin_flash('Сохранено.');
        admin_redirect('product', ['id' => $id]);
    }
}

/* --------------------------------------------------------------- данные */

$product = $id ? repo_product_by_id($id) : null;
$attributes = $id ? repo_product_attributes($id) : [];
$cardSpecs  = $id ? cms_all('SELECT name, value FROM product_card_specs WHERE product_id = ? ORDER BY sort_order, id', [$id]) : [];
$faq        = $id ? repo_product_faq($id) : [];
$images     = $id ? repo_product_images($id) : [];
$relations  = $id ? cms_all(
    'SELECT r.related_id, r.note FROM product_relations r WHERE r.product_id = ? AND r.relation = ? ORDER BY r.sort_order',
    [$id, 'similar']
) : [];
$priceLog = $id ? cms_all('SELECT * FROM price_history WHERE product_id = ? ORDER BY created_at DESC, id DESC LIMIT 10', [$id]) : [];
$allProducts = repo_products(['published' => false]);

/** Значение поля товара или пустая строка у нового. */
$v = static fn(string $field, string $default = ''): string => (string)($product[$field] ?? $default);

$screenTitle = $product ? (string)($product['short_name'] ?: $product['name']) : 'Новый товар';

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1><?= $product ? e($screenTitle) : 'Новый товар' ?></h1>
            <?php if ($product): ?>
            <p>
              <a href="<?= e(repo_product_url($product)) ?>" target="_blank" rel="noopener">Открыть на сайте</a>
              · изменён <?= e($product['updated_at'] ? date('d.m.Y H:i', strtotime((string)$product['updated_at'])) : '—') ?>
            </p>
            <?php else: ?>
            <p>Заполните название и цену — остальное можно дописать позже.</p>
            <?php endif; ?>
          </div>
          <div class="adm-actions">
            <a class="adm-btn grey" href="<?= e(admin_url('products')) ?>">К списку</a>
          </div>
        </div>

        <div class="adm-tabs" data-tabs>
          <button class="adm-tab active" type="button" data-tab="main">Основное</button>
          <button class="adm-tab" type="button" data-tab="price">Цена и наличие</button>
          <button class="adm-tab" type="button" data-tab="specs">Характеристики</button>
          <button class="adm-tab" type="button" data-tab="card">Карточка в каталоге</button>
          <button class="adm-tab" type="button" data-tab="photos">Фотографии</button>
          <button class="adm-tab" type="button" data-tab="text">Описание и вопросы</button>
          <button class="adm-tab" type="button" data-tab="seo">SEO</button>
        </div>

        <form method="post" action="<?= e(admin_url('product', ['id' => $id ?: 'new'])) ?>">
          <?= admin_csrf_field() ?>
          <input type="hidden" name="action" value="save" />

          <!-- ------------------------------------------------- основное -->
          <div data-panel="main">
            <div class="adm-card">
              <div class="adm-field">
                <label for="name">Полное название (уходит в фид Яндекс.Маркета)</label>
                <input id="name" name="name" type="text" value="<?= e($v('name')) ?>" data-slug-source required />
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="short_name">Короткое название (заголовок карточки на сайте)</label>
                  <input id="short_name" name="short_name" type="text" value="<?= e($v('short_name')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="model">Модель (для хлебных крошек)</label>
                  <input id="model" name="model" type="text" value="<?= e($v('model')) ?>" />
                </div>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="slug">Адрес страницы</label>
                  <input id="slug" name="slug" type="text" value="<?= e($v('slug')) ?>" data-slug-target />
                  <p class="note">Страница будет доступна по адресу /products/<b><?= e($v('slug', '…')) ?></b>/<?php if ($product): ?><br />Меняя адрес, добавьте переадресацию со старого — иначе ссылки перестанут работать.<?php endif; ?></p>
                </div>
                <div class="adm-field">
                  <label for="category_id">Категория</label>
                  <select id="category_id" name="category_id">
                    <option value="">Без категории</option>
                    <?php foreach (repo_categories(false) as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= (int)$v('category_id') === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="sku">Артикул</label>
                  <input id="sku" name="sku" type="text" value="<?= e($v('sku')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="mpn">Код производителя (MPN)</label>
                  <input id="mpn" name="mpn" type="text" value="<?= e($v('mpn')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="brand">Производитель</label>
                  <input id="brand" name="brand" type="text" value="<?= e($v('brand')) ?>" />
                </div>
              </div>
              <div class="adm-field">
                <label for="lead">Краткое описание (первый абзац на странице товара)</label>
                <textarea id="lead" name="lead"><?= e($v('lead')) ?></textarea>
              </div>
              <div class="adm-field">
                <label for="description">Описание для фида и микроразметки</label>
                <textarea id="description" name="description"><?= e($v('description')) ?></textarea>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="condition_note">Комплектация</label>
                  <input id="condition_note" name="condition_note" type="text" value="<?= e($v('condition_note')) ?>" placeholder="OEM, без кулера" />
                </div>
                <div class="adm-field">
                  <label for="warranty">Гарантия</label>
                  <input id="warranty" name="warranty" type="text" value="<?= e($v('warranty')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="sort_order">Порядок в списке</label>
                  <input id="sort_order" name="sort_order" type="number" value="<?= e($v('sort_order', '0')) ?>" />
                  <p class="note">Меньше — выше.</p>
                </div>
              </div>
              <label class="adm-check"><input type="checkbox" name="is_published" value="1"<?= $isNew || (int)$v('is_published') === 1 ? ' checked' : '' ?> /> Показывать на сайте</label>
              <label class="adm-check"><input type="checkbox" name="is_featured" value="1"<?= (int)$v('is_featured') === 1 ? ' checked' : '' ?> /> Выделить карточку в каталоге</label>
              <label class="adm-check"><input type="checkbox" name="in_yml" value="1"<?= $isNew || (int)$v('in_yml') === 1 ? ' checked' : '' ?> /> Выгружать в Яндекс.Маркет</label>
            </div>
          </div>

          <!-- --------------------------------------------- цена и наличие -->
          <div data-panel="price" hidden>
            <div class="adm-card">
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="price">Цена, ₽</label>
                  <input id="price" name="price" type="text" inputmode="decimal" value="<?= e(cms_money_machine(repo_price($product))) ?>" />
                  <p class="note">Пустое поле — «цена по запросу». Такой товар нельзя купить на сайте и он не уходит в фид.</p>
                </div>
                <div class="adm-field">
                  <label for="old_price">Старая цена, ₽</label>
                  <input id="old_price" name="old_price" type="text" inputmode="decimal" value="<?= e(cms_money_machine($product ? (float)($product['old_price'] ?: 0) ?: null : null)) ?>" />
                  <p class="note">Показывается зачёркнутой и только если больше текущей.</p>
                </div>
                <div class="adm-field">
                  <label for="cost_price">Закупка, ₽ (только для вас)</label>
                  <input id="cost_price" name="cost_price" type="text" inputmode="decimal" value="<?= e(cms_money_machine($product ? (float)($product['cost_price'] ?: 0) ?: null : null)) ?>" />
                  <p class="note">На сайт не попадает никогда.</p>
                </div>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="availability">Наличие</label>
                  <select id="availability" name="availability">
                    <?php foreach (REPO_STOCK_LABELS as $code => $label): ?>
                    <option value="<?= e($code) ?>"<?= $v('availability', 'in_stock') === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="adm-field">
                  <label for="stock_qty">Остаток, шт.</label>
                  <input id="stock_qty" name="stock_qty" type="number" value="<?= e($v('stock_qty')) ?>" />
                  <p class="note">Необязательно. Пустое поле — учёт не ведётся.</p>
                </div>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="preorder_note">Что написать про срок поставки</label>
                  <input id="preorder_note" name="preorder_note" type="text" value="<?= e($v('preorder_note')) ?>" placeholder="Поставка 2-3 недели после предоплаты" />
                  <p class="note">
                    Показывается покупателю рядом с ярлыком «Под заказ» — в каталоге,
                    в быстром просмотре и на странице товара. У товара в наличии не показывается.
                  </p>
                </div>
                <div class="adm-field">
                  <label for="unit">Единица</label>
                  <input id="unit" name="unit" type="text" value="<?= e($v('unit', 'шт.')) ?>" />
                </div>
              </div>
            </div>

            <?php if ($priceLog): ?>
            <div class="adm-card">
              <h2>История цены</h2>
              <div class="adm-scroll">
                <table class="adm-table">
                  <thead><tr><th>Когда</th><th class="num">Было</th><th class="num">Стало</th></tr></thead>
                  <tbody>
                    <?php foreach ($priceLog as $row): ?>
                    <tr>
                      <td><?= e(date('d.m.Y H:i', strtotime((string)$row['created_at']))) ?></td>
                      <td class="num"><?= e(cms_money($row['old_price'] === null ? null : (float)$row['old_price'])) ?></td>
                      <td class="num"><?= e(cms_money($row['new_price'] === null ? null : (float)$row['new_price'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- --------------------------------------------- характеристики -->
          <div data-panel="specs" hidden>
            <div class="adm-card">
              <h2>Характеристики на странице товара</h2>
              <p class="hint">Показываются в карточке и уходят в фид Яндекс.Маркета. Пустая строка не сохраняется.</p>
              <div class="adm-scroll">
                <table class="adm-table">
                  <thead><tr><th style="width:35%">Название</th><th>Значение</th></tr></thead>
                  <tbody>
                    <?php foreach (array_merge($attributes, array_fill(0, 5, ['name' => '', 'value' => ''])) as $row): ?>
                    <tr>
                      <td><input name="attr_name[]" type="text" value="<?= e((string)$row['name']) ?>" /></td>
                      <td><input name="attr_value[]" type="text" value="<?= e((string)$row['value']) ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <p class="note">Чтобы добавить больше строк, сохраните — снизу появятся новые пустые.</p>
            </div>
          </div>

          <!-- ------------------------------------------ карточка каталога -->
          <div data-panel="card" hidden>
            <div class="adm-card">
              <h2>Как товар выглядит в каталоге</h2>
              <p class="hint">В каталоге текст короче, чем на странице товара, — это отдельные поля, а не сокращение описания.</p>
              <div class="adm-field">
                <label for="card_title">Заголовок карточки</label>
                <input id="card_title" name="card_title" type="text" value="<?= e($v('card_title')) ?>" />
              </div>
              <div class="adm-field">
                <label for="card_lead">Текст под ценой</label>
                <textarea id="card_lead" name="card_lead"><?= e($v('card_lead')) ?></textarea>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="card_alt">Подпись фотографии (alt)</label>
                  <input id="card_alt" name="card_alt" type="text" value="<?= e($v('card_alt')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="card_figure_label">Подсказка кнопки увеличения</label>
                  <input id="card_figure_label" name="card_figure_label" type="text" value="<?= e($v('card_figure_label')) ?>" />
                </div>
              </div>
              <div class="adm-field">
                <label for="card_tags">Метки под карточкой, через запятую</label>
                <input id="card_tags" name="card_tags" type="text" value="<?= e($v('card_tags')) ?>" placeholder="Turbo Boost, ECC, Virtualization Technology" />
              </div>
            </div>

            <div class="adm-card">
              <h2>Короткие характеристики в карточке</h2>
              <p class="hint">Здесь «Ядра / потоки: 22 / 44» одной строкой, а на странице товара это две разные характеристики.</p>
              <div class="adm-scroll">
                <table class="adm-table">
                  <thead><tr><th style="width:35%">Название</th><th>Значение</th></tr></thead>
                  <tbody>
                    <?php foreach (array_merge($cardSpecs, array_fill(0, 4, ['name' => '', 'value' => ''])) as $row): ?>
                    <tr>
                      <td><input name="spec_name[]" type="text" value="<?= e((string)$row['name']) ?>" /></td>
                      <td><input name="spec_value[]" type="text" value="<?= e((string)$row['value']) ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- ------------------------------------------------ фотографии -->
          <div data-panel="photos" hidden>
            <div class="adm-card">
              <h2>Фотографии</h2>
              <?php if (!$id): ?>
              <p class="hint">Сначала сохраните товар — потом появится загрузка фотографий.</p>
              <?php elseif (!$images): ?>
              <p class="hint">Фотографий нет. Пока их нет, в каталоге показывается заглушка, а в фид Яндекс.Маркета товар не уходит.</p>
              <?php else: ?>
              <div class="adm-scroll">
                <table class="adm-table">
                  <thead><tr><th></th><th>Файл</th><th>Размер</th><th class="num">Действия</th></tr></thead>
                  <tbody>
                    <?php foreach ($images as $image): ?>
                    <tr>
                      <td><img class="thumb" src="<?= e((string)$image['path']) ?>" alt="" /></td>
                      <td>
                        <?= e((string)$image['path']) ?>
                        <?php if ((int)$image['is_main'] === 1): ?> <span class="adm-tag ok">главная</span><?php endif; ?>
                      </td>
                      <td><?= (int)$image['width'] ?>×<?= (int)$image['height'] ?></td>
                      <td class="num"><?= (int)$image['id'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- ------------------------------------------ описание и вопросы -->
          <div data-panel="text" hidden>
            <div class="adm-card">
              <h2>Подробный текст</h2>
              <p class="hint">Разворачивается на странице товара по кнопке «Показать полностью». Можно использовать HTML: заголовки, абзацы, списки.</p>
              <div class="adm-field">
                <label for="article_title">Заголовок текста</label>
                <input id="article_title" name="article_title" type="text" value="<?= e($v('article_title')) ?>" placeholder="Подробное описание" />
              </div>
              <div class="adm-field">
                <label for="article_html">Текст</label>
                <textarea id="article_html" name="article_html" class="tall"><?= e($v('article_html')) ?></textarea>
              </div>
            </div>

            <div class="adm-card">
              <h2>Вопросы и ответы</h2>
              <p class="hint">Показываются внизу страницы и отдаются поисковым системам как FAQ.</p>
              <?php foreach (array_merge($faq, array_fill(0, 2, ['question' => '', 'answer' => ''])) as $item): ?>
              <div class="adm-field">
                <input name="faq_q[]" type="text" value="<?= e((string)$item['question']) ?>" placeholder="Вопрос" />
                <textarea name="faq_a[]" placeholder="Ответ" style="margin-top:6px"><?= e((string)$item['answer']) ?></textarea>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="adm-card">
              <h2>Похожие товары</h2>
              <p class="hint">Если ничего не выбрать, сайт сам подставит другие товары этой категории.</p>
              <?php foreach (array_merge($relations, array_fill(0, 1, ['related_id' => 0, 'note' => ''])) as $rel): ?>
              <div class="adm-cols">
                <div class="adm-field">
                  <select name="rel_id[]">
                    <option value="0">— не выбрано —</option>
                    <?php foreach ($allProducts as $other):
                        if ((int)$other['id'] === $id) { continue; } ?>
                    <option value="<?= (int)$other['id'] ?>"<?= (int)$rel['related_id'] === (int)$other['id'] ? ' selected' : '' ?>><?= e((string)($other['short_name'] ?: $other['name'])) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="adm-field">
                  <input name="rel_note[]" type="text" value="<?= e((string)$rel['note']) ?>" placeholder="Чем отличается: «Тот же Broadwell-E, но 18 ядер»" />
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- ------------------------------------------------------- SEO -->
          <div data-panel="seo" hidden>
            <div class="adm-card">
              <h2>Как страница выглядит в поиске</h2>
              <div class="adm-field">
                <label for="seo_title">Заголовок вкладки (title)</label>
                <input id="seo_title" name="seo_title" type="text" value="<?= e($v('seo_title')) ?>" />
                <p class="note">Пусто — соберётся из названия автоматически.</p>
              </div>
              <div class="adm-field">
                <label for="seo_desc">Описание в выдаче (description)</label>
                <textarea id="seo_desc" name="seo_desc"><?= e($v('seo_desc')) ?></textarea>
              </div>
              <div class="adm-cols">
                <div class="adm-field">
                  <label for="seo_h1">Заголовок на странице (H1)</label>
                  <input id="seo_h1" name="seo_h1" type="text" value="<?= e($v('seo_h1')) ?>" />
                </div>
                <div class="adm-field">
                  <label for="breadcrumb">Название в хлебных крошках для поисковика</label>
                  <input id="breadcrumb" name="breadcrumb" type="text" value="<?= e($v('breadcrumb')) ?>" />
                </div>
              </div>
              <div class="adm-field">
                <label for="canonical">Канонический адрес</label>
                <input id="canonical" name="canonical" type="text" value="<?= e($v('canonical')) ?>" />
                <p class="note">Заполняется только если эта же страница доступна ещё по одному адресу.</p>
              </div>
            </div>

            <div class="adm-card">
              <h2>Как страница выглядит при пересылке ссылки</h2>
              <div class="adm-field">
                <label for="og_title">Заголовок</label>
                <input id="og_title" name="og_title" type="text" value="<?= e($v('og_title')) ?>" />
              </div>
              <div class="adm-field">
                <label for="og_desc">Описание</label>
                <textarea id="og_desc" name="og_desc"><?= e($v('og_desc')) ?></textarea>
              </div>
              <div class="adm-field">
                <label for="og_image">Картинка</label>
                <input id="og_image" name="og_image" type="text" value="<?= e($v('og_image')) ?>" placeholder="/public/images/products/…" />
                <p class="note">Пусто — берётся главная фотография товара.</p>
              </div>
              <label class="adm-check"><input type="checkbox" name="noindex" value="1"<?= (int)$v('noindex') === 1 ? ' checked' : '' ?> /> Запретить поисковым системам индексировать страницу</label>
            </div>
          </div>

          <div class="adm-actions">
            <button class="adm-btn" type="submit">Сохранить</button>
            <a class="adm-btn grey" href="<?= e(admin_url('products')) ?>">Отмена</a>
          </div>
        </form>

        <?php if ($id): ?>
        <div class="adm-card" style="margin-top:16px">
          <h2>Загрузить фотографию</h2>
          <p class="hint">JPG, PNG или WebP, до 12 МБ. Имя файла придумает система — присланное не используется.</p>
          <form method="post" action="<?= e(admin_url('product', ['id' => $id])) ?>" enctype="multipart/form-data">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="photo-upload" />
            <div class="adm-field"><input name="photo" type="file" accept="image/jpeg,image/png,image/webp" required /></div>
            <button class="adm-btn" type="submit">Загрузить</button>
          </form>

          <?php if ($images): ?>
          <div class="adm-actions" style="margin-top:14px">
            <?php foreach ($images as $image): ?>
            <form method="post" action="<?= e(admin_url('product', ['id' => $id])) ?>" class="adm-inline">
              <?= admin_csrf_field() ?>
              <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>" />
              <?php if ((int)$image['is_main'] !== 1): ?>
              <button class="adm-btn small ghost" name="action" value="photo-main" type="submit">Сделать главной #<?= (int)$image['id'] ?></button>
              <?php endif; ?>
              <button class="adm-btn small danger" name="action" value="photo-delete" type="submit"
                      data-confirm="Удалить фотографию #<?= (int)$image['id'] ?>?">Удалить #<?= (int)$image['id'] ?></button>
            </form>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
