<?php
/**
 * Фотографии товаров: что лежит в папке и чему принадлежит.
 *
 * Экран отвечает на два вопроса, которые иначе требуют доступа по FTP:
 * у каких товаров нет фотографии и какие файлы лежат в папке зря.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/uploads.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');

    if ($action === 'delete-orphan') {
        $name = basename((string)admin_post('file', ''));
        $path = upload_dir() . '/' . $name;

        // Удаляем только то, что действительно ничьё: проверка по базе, а не
        // по имени файла, иначе можно снести используемый снимок.
        $used = cms_value('SELECT id FROM product_images WHERE path = ?', [UPLOAD_DIR . '/' . $name]);
        if ($used) {
            admin_flash('Файл используется товаром — удаление отменено.', 'bad');
        } elseif ($name !== '' && is_file($path) && !str_contains($name, 'placeholder')) {
            @unlink($path);
            cms_audit('media.delete', null, null, 'Удалён неиспользуемый файл ' . $name);
            admin_flash('Файл удалён.', 'warn');
        }
    }

    admin_redirect('media');
}

$dir = upload_dir();
$files = [];
foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $path) {
    $files[basename($path)] = ['size' => (int)filesize($path), 'time' => (int)filemtime($path)];
}

// Что из этого кому принадлежит.
$used = [];
foreach (cms_all(
    'SELECT i.path, i.is_main, p.id AS product_id, p.short_name, p.name
       FROM product_images i JOIN products p ON p.id = i.product_id'
) as $row) {
    $used[basename((string)$row['path'])][] = $row;
}

$withoutPhoto = array_filter(repo_products(['published' => false]), static fn($p) => repo_image_is_placeholder($p));

// Уменьшенные копии и WebP-версии — не «лишние файлы», а часть основного
// снимка: показывать их отдельной строкой было бы ложной тревогой.
$isVariant = static function (string $name) use ($used): bool {
    $base = preg_replace('~(-\d+)?\.(webp|jpe?g|png)$~i', '', $name);
    foreach (array_keys($used) as $usedName) {
        if ($base !== '' && str_starts_with($usedName, (string)$base)) {
            return true;
        }
    }
    return false;
};

$orphans = [];
foreach ($files as $name => $info) {
    if (!isset($used[$name]) && !str_contains($name, 'placeholder') && !$isVariant($name)) {
        $orphans[$name] = $info;
    }
}

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Фотографии</h1>
            <p>Папка <code><?= e(UPLOAD_DIR) ?></code> — <?= count($files) ?> файлов. Загружаются фотографии в карточке товара.</p>
          </div>
        </div>

        <?php if ($withoutPhoto): ?>
        <div class="adm-card">
          <h2>Товары без фотографии</h2>
          <p class="hint">Пока фотографии нет, на сайте показывается заглушка, а в фид Яндекс.Маркета товар не уходит.</p>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th>Товар</th><th class="num">Действие</th></tr></thead>
              <tbody>
                <?php foreach ($withoutPhoto as $item): ?>
                <tr>
                  <td><?= e((string)($item['short_name'] ?: $item['name'])) ?></td>
                  <td class="num"><a class="adm-btn small ghost" href="<?= e(admin_url('product', ['id' => $item['id']])) ?>#photos">Загрузить</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <div class="adm-card">
          <h2>Фотографии товаров</h2>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th></th><th>Файл</th><th>Товар</th><th class="num">Размер</th></tr></thead>
              <tbody>
                <?php foreach ($used as $name => $rows): ?>
                <tr>
                  <td><img class="thumb" src="<?= e(UPLOAD_DIR . '/' . $name) ?>" alt="" /></td>
                  <td><?= e($name) ?></td>
                  <td>
                    <?php foreach ($rows as $row): ?>
                    <a href="<?= e(admin_url('product', ['id' => $row['product_id']])) ?>"><?= e((string)($row['short_name'] ?: $row['name'])) ?></a>
                    <?php if ((int)$row['is_main'] === 1): ?><span class="adm-tag ok">главная</span><?php endif; ?>
                    <?php endforeach; ?>
                  </td>
                  <td class="num"><?= isset($files[$name]) ? number_format($files[$name]['size'] / 1024, 0, ',', ' ') . ' КБ' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="adm-card">
          <h2>Файлы, которые ничему не принадлежат</h2>
          <?php if (!$orphans): ?>
          <p class="hint">Таких нет — в папке только используемые снимки и их уменьшенные копии.</p>
          <?php else: ?>
          <p class="hint">Эти файлы лежат в папке, но ни к одному товару не привязаны. Уменьшенные копии и WebP-версии сюда не попадают.</p>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th></th><th>Файл</th><th class="num">Размер</th><th class="num">Действие</th></tr></thead>
              <tbody>
                <?php foreach ($orphans as $name => $info): ?>
                <tr>
                  <td><img class="thumb" src="<?= e(UPLOAD_DIR . '/' . $name) ?>" alt="" /></td>
                  <td><?= e($name) ?></td>
                  <td class="num"><?= number_format($info['size'] / 1024, 0, ',', ' ') ?> КБ</td>
                  <td class="num">
                    <form method="post" action="<?= e(admin_url('media')) ?>" class="adm-inline">
                      <?= admin_csrf_field() ?>
                      <input type="hidden" name="file" value="<?= e($name) ?>" />
                      <button class="adm-btn small danger" name="action" value="delete-orphan" type="submit"
                              data-confirm="Удалить файл <?= e($name) ?> навсегда?">Удалить</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
