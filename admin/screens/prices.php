<?php
/**
 * Цены и наличие — экран для ежедневной работы.
 *
 * Здесь весь каталог в одной таблице: цену любого товара можно поправить, не
 * открывая его карточку. Это самая частая задача, и ради неё не должно
 * требоваться двенадцать переходов.
 *
 * Рядом — выгрузка и загрузка таблицы: когда цен много, удобнее править их
 * в Excel и вернуть файлом.
 */

declare(strict_types=1);

/* ------------------------------------------------------------- выгрузка */

if (($_GET['export'] ?? '') === 'csv') {
    $rows = repo_products(['published' => false]);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="prices-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    // Метка BOM: без неё Excel открывает русские названия крякозябрами.
    fwrite($out, "\xEF\xBB\xBF");
    // Точка с запятой — разделитель, который Excel с русской локалью
    // понимает без «мастера импорта».
    // Четвёртый параметр обязателен: в PHP 8.4 без него сыплется
    // предупреждение, а его текст попадает прямо внутрь выгружаемого файла и
    // ломает таблицу. Пустая строка отключает нестандартное экранирование
    // обратным слэшем — получается обычный CSV, который Excel понимает.
    fputcsv($out, ['Артикул', 'Адрес', 'Название', 'Цена', 'Старая цена', 'Наличие', 'Остаток'], ';', '"', '');

    foreach ($rows as $row) {
        fputcsv($out, [
            (string)$row['sku'],
            (string)$row['slug'],
            (string)($row['short_name'] ?: $row['name']),
            cms_money_machine(repo_price($row)),
            cms_money_machine($row['old_price'] === null ? null : (float)$row['old_price']),
            REPO_STOCK_LABELS[$row['availability']] ?? '',
            (string)$row['stock_qty'],
        ], ';', '"', '');
    }
    fclose($out);
    exit;
}

/* --------------------------------------------------------- сохранение */

$imported = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', 'save');

    /* --- быстрая правка прямо в таблице --- */

    if ($action === 'save') {
        $prices = (array)($_POST['price'] ?? []);
        $olds   = (array)($_POST['old_price'] ?? []);
        $stocks = (array)($_POST['availability'] ?? []);
        $qty    = (array)($_POST['stock_qty'] ?? []);
        $changed = 0;

        foreach ($prices as $productId => $raw) {
            $productId = (int)$productId;
            $item = repo_product_by_id($productId);
            if (!$item) {
                continue;
            }

            $was = repo_price($item);
            $now = admin_price_from_text((string)$raw);
            $wasOld = $item['old_price'] === null ? null : (float)$item['old_price'];
            $nowOld = admin_price_from_text((string)($olds[$productId] ?? ''));
            $nowStock = in_array($stocks[$productId] ?? '', ['in_stock', 'preorder', 'out_of_stock'], true)
                ? (string)$stocks[$productId] : $item['availability'];
            $nowQty = trim((string)($qty[$productId] ?? '')) === '' ? null : (int)$qty[$productId];

            if ($was === $now && $wasOld === $nowOld && $nowStock === $item['availability']
                && (string)$nowQty === (string)$item['stock_qty']) {
                continue;
            }

            cms_update('products', [
                'price'        => $now,
                'old_price'    => $nowOld,
                'availability' => $nowStock,
                'stock_qty'    => $nowQty,
                'updated_at'   => cms_now(),
                'updated_by'   => (int)$user['id'],
            ], 'id = :id', ['id' => $productId]);

            if ($was !== $now) {
                cms_insert('price_history', [
                    'product_id' => $productId,
                    'old_price'  => $was,
                    'new_price'  => $now,
                    'admin_id'   => (int)$user['id'],
                    'created_at' => cms_now(),
                ]);
            }
            $changed++;
        }

        cms_audit('prices.save', null, null, 'Изменено позиций: ' . $changed);
        admin_flash($changed > 0 ? "Сохранено. Изменено позиций: $changed." : 'Изменений не было.');
        admin_redirect('prices');
    }

    /* --- загрузка таблицы --- */

    if ($action === 'import') {
        $imported = admin_import_prices($_FILES['file'] ?? [], (int)$user['id'], !empty($_POST['dry']));
    }
}

/**
 * Цена из строки, набранной человеком: «15 500», «15500,50», «15 500 ₽».
 * Пустая строка означает «цена по запросу», а не ноль.
 */
function admin_price_from_text(string $raw): ?float
{
    $raw = str_replace(["\u{00A0}", ' ', '₽', 'р.', 'руб.'], '', $raw);
    $raw = str_replace(',', '.', trim($raw));

    if ($raw === '') {
        return null;
    }

    return is_numeric($raw) ? (float)$raw : null;
}

/**
 * Разбор загруженной таблицы.
 *
 * Товар ищется по артикулу, а если его нет — по адресу. По названию не
 * ищем никогда: одинаковые названия встречаются, и ошибиться ценой из-за
 * похожей строки — слишком дорого.
 *
 * @return array{ok:int,skip:int,errors:string[],rows:array}
 */
function admin_import_prices(array $file, int $adminId, bool $dryRun): array
{
    $result = ['ok' => 0, 'skip' => 0, 'errors' => [], 'rows' => []];

    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        $result['errors'][] = 'Файл не загрузился.';
        return $result;
    }

    $handle = fopen((string)$file['tmp_name'], 'r');
    if (!$handle) {
        $result['errors'][] = 'Не удалось открыть файл.';
        return $result;
    }

    $first = fgets($handle);
    // Excel добавляет метку BOM в начало — без её удаления первый заголовок
    // не совпадает ни с чем.
    $first = ltrim((string)$first, "\xEF\xBB\xBF");
    // Разделитель определяем по первой строке: и «;», и «,» встречаются.
    $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
    rewind($handle);
    fgets($handle);   // пропускаем строку заголовков

    $line = 1;
    while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
        $line++;
        if ($row === [null] || count($row) < 4) {
            continue;
        }

        $sku   = trim((string)($row[0] ?? ''));
        $slug  = trim((string)($row[1] ?? ''));
        $price = admin_price_from_text((string)($row[3] ?? ''));
        $old   = admin_price_from_text((string)($row[4] ?? ''));

        $item = null;
        if ($sku !== '') {
            $item = cms_one('SELECT * FROM products WHERE sku = ? AND deleted_at IS NULL', [$sku]);
        }
        if (!$item && $slug !== '') {
            $item = repo_product($slug);
        }

        if (!$item) {
            $result['skip']++;
            // Фигурные скобки обязательны: PHP считает байты кириллицы и
            // кавычек-ёлочек частью имени переменной, и «$sku»» превратилось
            // бы в неизвестную переменную с пустым значением.
            $result['errors'][] = "Строка {$line}: товар не найден (артикул «{$sku}», адрес «{$slug}»).";
            continue;
        }

        $was = repo_price($item);
        $result['rows'][] = [
            'name' => (string)($item['short_name'] ?: $item['name']),
            'was'  => $was,
            'now'  => $price,
        ];

        if ($dryRun) {
            $result['ok']++;
            continue;
        }

        cms_update('products', [
            'price'      => $price,
            'old_price'  => $old,
            'updated_at' => cms_now(),
            'updated_by' => $adminId,
        ], 'id = :id', ['id' => (int)$item['id']]);

        if ($was !== $price) {
            cms_insert('price_history', [
                'product_id' => (int)$item['id'],
                'old_price'  => $was,
                'new_price'  => $price,
                'admin_id'   => $adminId,
                'created_at' => cms_now(),
            ]);
        }
        $result['ok']++;
    }

    fclose($handle);

    if (!$dryRun) {
        cms_audit('prices.import', null, null, 'Загрузка таблицы цен: обновлено ' . $result['ok']);
    }

    return $result;
}

$items = repo_products(['published' => false]);

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Цены и наличие</h1>
            <p>Правьте прямо в таблице и нажмите «Сохранить» внизу. Пустая цена — «цена по запросу».</p>
          </div>
          <div class="adm-actions">
            <a class="adm-btn grey" href="<?= e(admin_url('prices', ['export' => 'csv'])) ?>">Выгрузить в Excel</a>
          </div>
        </div>

        <?php if ($imported !== null): ?>
        <div class="adm-card">
          <h2><?= !empty($_POST['dry']) ? 'Проверка файла' : 'Файл загружен' ?></h2>
          <p>Обработано строк: <b><?= (int)$imported['ok'] ?></b>. Пропущено: <b><?= (int)$imported['skip'] ?></b>.</p>
          <?php if ($imported['errors']): ?>
          <p class="adm-flash warn" style="white-space:pre-line"><?= e(implode("\n", array_slice($imported['errors'], 0, 20))) ?></p>
          <?php endif; ?>
          <?php if ($imported['rows']): ?>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th>Товар</th><th class="num">Было</th><th class="num">Станет</th></tr></thead>
              <tbody>
                <?php foreach (array_slice($imported['rows'], 0, 50) as $row): ?>
                <tr>
                  <td><?= e($row['name']) ?></td>
                  <td class="num"><?= e(cms_money($row['was'])) ?></td>
                  <td class="num"><?= $row['was'] === $row['now']
                      ? e(cms_money($row['now']))
                      : '<b>' . e(cms_money($row['now'])) . '</b>' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= e(admin_url('prices')) ?>">
          <?= admin_csrf_field() ?>
          <input type="hidden" name="action" value="save" />
          <div class="adm-scroll">
            <table class="adm-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th class="num" style="width:130px">Цена, ₽</th>
                  <th class="num" style="width:130px">Старая, ₽</th>
                  <th style="width:150px">Наличие</th>
                  <th class="num" style="width:100px">Остаток</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <a href="<?= e(admin_url('product', ['id' => $item['id']])) ?>"><?= e((string)($item['short_name'] ?: $item['name'])) ?></a>
                    <div style="color:var(--muted);font-size:12px">арт. <?= e((string)$item['sku']) ?><?= (int)$item['is_published'] === 1 ? '' : ' · скрыт' ?></div>
                  </td>
                  <td class="num"><input name="price[<?= (int)$item['id'] ?>]" type="text" inputmode="decimal" value="<?= e(cms_money_machine(repo_price($item))) ?>" /></td>
                  <td class="num"><input name="old_price[<?= (int)$item['id'] ?>]" type="text" inputmode="decimal" value="<?= e(cms_money_machine($item['old_price'] === null ? null : (float)$item['old_price'])) ?>" /></td>
                  <td>
                    <select name="availability[<?= (int)$item['id'] ?>]">
                      <?php foreach (REPO_STOCK_LABELS as $code => $label): ?>
                      <option value="<?= e($code) ?>"<?= $item['availability'] === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="num"><input name="stock_qty[<?= (int)$item['id'] ?>]" type="text" inputmode="numeric" value="<?= e((string)$item['stock_qty']) ?>" /></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="adm-actions">
            <button class="adm-btn" type="submit">Сохранить цены</button>
          </div>
        </form>

        <div class="adm-card" style="margin-top:18px">
          <h2>Загрузить цены из файла</h2>
          <p class="hint">
            Выгрузите таблицу кнопкой выше, поправьте столбцы «Цена» и «Старая цена» в Excel
            и верните файл сюда. Товар определяется по артикулу, а если его нет — по адресу.
            По названию — никогда: похожие названия встречаются, и ошибка ценой слишком дорога.
          </p>
          <form method="post" action="<?= e(admin_url('prices')) ?>" enctype="multipart/form-data">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="action" value="import" />
            <div class="adm-field"><input name="file" type="file" accept=".csv,text/csv" required /></div>
            <div class="adm-actions">
              <button class="adm-btn grey" type="submit" name="dry" value="1">Сначала проверить</button>
              <button class="adm-btn" type="submit" data-confirm="Записать цены из файла в каталог?">Загрузить и применить</button>
            </div>
          </form>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
