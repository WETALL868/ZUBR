<?php
/**
 * Обслуживание: проверка сайта и резервные копии.
 *
 * Экран отвечает на вопрос «всё ли настроено правильно» до того, как это
 * выяснится на посетителе. Каждая проверка говорит не только «плохо», но и
 * что именно сделать.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/cms/backup.php';
require_once dirname(__DIR__, 2) . '/cms/orders.php';

// Папка копий создаётся при первом обращении — иначе проверка ниже честно,
// но бесполезно сообщила бы, что её нет.
backup_dir();

/* ------------------------------------------------------------ действия */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)admin_post('action', '');

    if ($action === 'backup') {
        $result = backup_create();
        if ($result['ok']) {
            cms_audit('backup.create', null, null, 'Создана резервная копия ' . $result['file']);
            admin_flash('Копия создана: ' . $result['file'] . ' ('
                . number_format($result['size'] / 1024, 0, ',', ' ') . ' КБ).');
        } else {
            admin_flash($result['message'], 'bad');
        }
    }

    if ($action === 'clear-cache') {
        $removed = 0;
        foreach (glob(CMS_ROOT . '/storage/cache/*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $removed++;
            }
        }
        admin_flash('Кэш очищен, файлов удалено: ' . $removed . '. Фид Яндекс.Маркета соберётся заново при следующем запросе.');
    }

    admin_redirect('maintenance');
}

/* -------------------------------------------------------------- скачать */

if (($_GET['download'] ?? '') !== '') {
    $name = basename((string)$_GET['download']);
    $path = backup_dir() . '/' . $name;

    // Скачать можно только файл копии из папки копий: имя из адреса
    // никогда не склеивается с путём без проверки.
    if (preg_match('~^backup-[\d-]+\.sql$~', $name) && is_file($path)) {
        cms_audit('backup.download', null, null, 'Скачана копия ' . $name);
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    admin_flash('Файл копии не найден.', 'bad');
    admin_redirect('maintenance');
}

/* ------------------------------------------------------------ проверки */

/** @return array{0:string,1:string,2:string} состояние, что проверено, что делать */
function check(bool $ok, string $what, string $good, string $bad, bool $warnOnly = false): array
{
    return [$ok ? 'ok' : ($warnOnly ? 'warn' : 'bad'), $what, $ok ? $good : $bad];
}

$driver = cms_config()['driver'] ?? 'mysql';
$checks = [];

$checks[] = check(
    version_compare(PHP_VERSION, '8.1', '>='),
    'Версия PHP',
    PHP_VERSION,
    PHP_VERSION . ' — нужна 8.1 или новее, переключите версию в панели хостинга'
);

foreach (['pdo_mysql' => 'MySQL', 'pdo_sqlite' => 'SQLite', 'mbstring' => 'русский текст', 'gd' => 'размеры фотографий', 'openssl' => 'отправка почты'] as $ext => $why) {
    $needed = !($ext === 'pdo_mysql' && $driver === 'sqlite') && !($ext === 'pdo_sqlite' && $driver !== 'sqlite');
    if (!$needed) {
        continue;
    }
    $checks[] = check(
        extension_loaded($ext),
        'Расширение ' . $ext,
        'установлено (' . $why . ')',
        'не установлено — без него не работает: ' . $why
    );
}

try {
    cms_db()->query('SELECT 1');
    $checks[] = check(true, 'Связь с базой', 'база отвечает, драйвер ' . $driver, '');
} catch (Throwable $e) {
    $checks[] = check(false, 'Связь с базой', '', 'база не отвечает: ' . $e->getMessage());
}

foreach ([
    '/storage'                 => 'сюда пишутся копии и кэш',
    '/storage/backups'         => 'резервные копии',
    '/public/images/products'  => 'загрузка фотографий',
] as $path => $why) {
    $full = CMS_ROOT . $path;
    $checks[] = check(
        is_dir($full) && is_writable($full),
        'Папка ' . $path,
        'доступна для записи',
        'недоступна для записи — не будет работать: ' . $why . '. Поставьте права 775.'
    );
}

foreach ([
    '/config/.htaccess'                => 'пароль базы',
    '/storage/.htaccess'               => 'база и резервные копии',
    '/cms/.htaccess'                   => 'код панели',
    '/admin/.htaccess'                 => 'закрытие панели от поисковиков',
    '/public/images/products/.htaccess' => 'запрет выполнения кода в папке фотографий',
] as $path => $why) {
    $checks[] = check(
        is_file(CMS_ROOT . $path),
        'Защита ' . dirname($path),
        'на месте (' . $why . ')',
        'файла ' . $path . ' нет — каталог может открываться по HTTP: ' . $why
    );
}

$checks[] = check(
    (int)cms_value('SELECT COUNT(*) FROM admin_users WHERE is_active = 1') > 0,
    'Вход в панель',
    'настроен',
    'ни одного активного пользователя — создайте: php cms/install/create-admin.php'
);

$noPrice = 0;
$noPhoto = 0;
foreach (repo_products() as $product) {
    if (repo_price($product) === null) {
        $noPrice++;
    }
    if (repo_image_is_placeholder($product)) {
        $noPhoto++;
    }
}

$checks[] = check($noPrice === 0, 'Цены товаров', 'у всех опубликованных товаров есть цена',
    "без цены товаров: $noPrice — их нельзя купить и они не уходят в фид", true);
$checks[] = check($noPhoto === 0, 'Фотографии товаров', 'у всех опубликованных товаров есть фотография',
    "без фотографии товаров: $noPhoto — вместо снимка показывается заглушка", true);

/*
 * Директивы, которые нельзя писать в .htaccess.
 *
 * php_admin_value и php_admin_flag PHP разрешает только в основном конфиге
 * сервера. На хостинге с mod_php Apache отвечает на такую строку «not allowed
 * here» — то есть ошибкой 500 на КАЖДЫЙ файл в этой папке. Если строка попала
 * в .htaccess папки с фотографиями, снимки товаров перестают открываться и на
 * сайте остаются одни подписи ALT; если в корневой — падает весь сайт.
 *
 * Проверка читает файлы, а не делает запрос по сети: часть хостингов
 * запрещает обращаться к самому себе, и сетевая проверка там показывала бы
 * вечное предупреждение вместо ответа.
 */
$illegal = [];
foreach (['/.htaccess', '/public/images/products/.htaccess', '/admin/.htaccess',
          '/config/.htaccess', '/storage/.htaccess', '/cms/.htaccess'] as $file) {
    $full = CMS_ROOT . $file;
    if (!is_file($full)) {
        continue;
    }
    foreach (file($full, FILE_IGNORE_NEW_LINES) as $n => $line) {
        $clean = trim($line);
        if ($clean === '' || $clean[0] === '#') {
            continue;   // комментарии Apache не читает
        }
        if (preg_match('~^(php_admin_value|php_admin_flag)\s~i', $clean)) {
            $illegal[] = $file . ', строка ' . ($n + 1) . ': ' . mb_substr($clean, 0, 40);
        }
    }
}

$checks[] = check(
    $illegal === [],
    'Директивы в .htaccess',
    'запрещённых директив нет',
    'найдены директивы, недопустимые в .htaccess — сервер ответит ошибкой 500 на все файлы '
    . 'в этой папке: ' . implode('; ', $illegal) . '. Удалите эти строки.'
);

/*
 * Способы оплаты.
 *
 * Пустой список — не поломка: форма просто не спросит про оплату, и заказ
 * оформится. Но менеджер тогда не узнает, как покупатель собирался платить,
 * поэтому предупредить стоит.
 */
$paymentCount = 0;
try {
    $paymentCount = (int)(cms_value('SELECT COUNT(*) FROM payment_methods WHERE is_active = 1') ?? 0);
} catch (Throwable) {
    $paymentCount = 0;
}
$checks[] = check($paymentCount > 0, 'Способы оплаты',
    'настроено: ' . $paymentCount,
    'ни одного активного способа оплаты — покупатель не сможет выбрать, как платить. '
    . 'Добавьте их в разделе «Оплата».', true);

$mailConfig = CMS_ROOT . '/api/mail-config.php';
$checks[] = check(is_file($mailConfig), 'Настройки почты',
    'файл api/mail-config.php на месте',
    'нет файла api/mail-config.php — заказы сохраняются в базу, но письма не уходят. '
    . 'Скопируйте api/mail-config.example.php и заполните.', true);

$checks[] = check(
    is_file(CMS_ROOT . '/config/database.php'),
    'Настройки базы',
    'файл config/database.php на месте и закрыт от посторонних',
    'нет файла config/database.php'
);

$backups = backup_list();
$freshBackup = $backups && (time() - $backups[0]['time']) < 14 * 86400;
$checks[] = check($freshBackup, 'Резервная копия',
    'последняя от ' . ($backups ? date('d.m.Y H:i', $backups[0]['time']) : ''),
    $backups ? 'последняя от ' . date('d.m.Y', $backups[0]['time']) . ' — старше двух недель'
             : 'копий ещё нет — сделайте первую кнопкой ниже', true);

$bad = count(array_filter($checks, static fn($c) => $c[0] === 'bad'));
$warn = count(array_filter($checks, static fn($c) => $c[0] === 'warn'));

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Обслуживание</h1>
            <p>
              <?php if ($bad === 0 && $warn === 0): ?>Всё в порядке.
              <?php else: ?>Ошибок: <?= $bad ?>, предупреждений: <?= $warn ?>.<?php endif; ?>
            </p>
          </div>
        </div>

        <div class="adm-card">
          <h2>Проверка сайта</h2>
          <div class="adm-scroll">
            <table class="adm-table">
              <thead><tr><th style="width:220px">Что проверено</th><th>Состояние</th></tr></thead>
              <tbody>
                <?php foreach ($checks as [$state, $what, $detail]): ?>
                <tr>
                  <td><?= e($what) ?></td>
                  <td>
                    <span class="adm-tag <?= e($state) ?>"><?= $state === 'ok' ? 'в порядке' : ($state === 'warn' ? 'внимание' : 'ошибка') ?></span>
                    <?= e($detail) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="adm-card">
          <h2>Резервные копии</h2>
          <p class="hint">
            Копия — это обычный файл .sql со всем содержимым базы: товары, цены, тексты, заказы.
            Её можно залить обратно через phpMyAdmin. Паролей базы и почты в копии нет —
            они лежат в файлах, а не в таблицах. Хранятся десять последних.
          </p>
          <form method="post" action="<?= e(admin_url('maintenance')) ?>" class="adm-inline">
            <?= admin_csrf_field() ?>
            <button class="adm-btn" name="action" value="backup" type="submit">Сделать копию сейчас</button>
          </form>

          <?php if ($backups): ?>
          <div class="adm-scroll" style="margin-top:14px">
            <table class="adm-table">
              <thead><tr><th>Файл</th><th>Когда</th><th class="num">Размер</th><th class="num">Действие</th></tr></thead>
              <tbody>
                <?php foreach ($backups as $file): ?>
                <tr>
                  <td><?= e($file['name']) ?></td>
                  <td><?= e(date('d.m.Y H:i', $file['time'])) ?></td>
                  <td class="num"><?= number_format($file['size'] / 1024, 0, ',', ' ') ?> КБ</td>
                  <td class="num"><a class="adm-btn small ghost" href="<?= e(admin_url('maintenance', ['download' => $file['name']])) ?>">Скачать</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <div class="adm-card">
          <h2>Кэш</h2>
          <p class="hint">
            Кэшируется только фид Яндекс.Маркета, и он сбрасывается сам при изменении товара.
            Кнопка нужна, если фид почему-то отдаёт старые данные.
          </p>
          <form method="post" action="<?= e(admin_url('maintenance')) ?>" class="adm-inline">
            <?= admin_csrf_field() ?>
            <button class="adm-btn grey" name="action" value="clear-cache" type="submit">Очистить кэш</button>
          </form>
        </div>

        <div class="adm-card">
          <h2>Что где лежит</h2>
          <div class="adm-scroll">
            <table class="adm-table">
              <tbody>
                <tr><td>Товары, цены, тексты, заказы</td><td>база данных (<?= e($driver) ?>)</td></tr>
                <tr><td>Доступ к базе</td><td><code>config/database.php</code> — закрыт от HTTP</td></tr>
                <tr><td>Почта и Telegram</td><td><code>api/mail-config.php</code> — закрыт от HTTP</td></tr>
                <tr><td>Фотографии товаров</td><td><code>/public/images/products/</code></td></tr>
                <tr><td>Резервные копии и кэш</td><td><code>/storage/</code> — закрыт от HTTP</td></tr>
                <tr><td>Фид Яндекс.Маркета</td><td><a href="/yandexmarket.xml" target="_blank" rel="noopener">/yandexmarket.xml</a></td></tr>
                <tr><td>Карта сайта</td><td><a href="/sitemap.xml" target="_blank" rel="noopener">/sitemap.xml</a></td></tr>
              </tbody>
            </table>
          </div>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
