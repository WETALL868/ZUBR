<?php

/**
 * Скачивает оригиналы фотографий товаров на наш сервер.
 *
 * Зачем: часть фотографий исторически была загружена с чужого CDN (Ozon).
 * Чужой CDN может закрыть доступ по реферу, переименовать файл или просто
 * исчезнуть — и фотографии пропали бы и с сайта, и из фида Яндекс.Маркета.
 * Скрипт забирает такие файлы к нам в /public/images/products/ и сразу
 * делает WebP-версию для быстрой загрузки страниц.
 *
 * Откуда берётся адрес: поле 'picture_source' в yandexmarket/products.php.
 *
 * Запуск в консоли хостинга (из корня сайта):
 *     php tools/import-product-images.php            # показать, что будет скачано
 *     php tools/import-product-images.php --apply    # скачать
 *
 * Запуск через браузер (если консоли нет):
 *     https://comp-uter.ru/tools/import-product-images.php?token=ВАШ_ТОКЕН
 *     https://comp-uter.ru/tools/import-product-images.php?token=ВАШ_ТОКЕН&apply=1
 *
 * ВАШ_ТОКЕН — значение 'mail_test_token' из файла api/mail-config.php.
 *
 * Скрипт безопасно запускать повторно: уже скачанные файлы пропускаются,
 * если не добавить --force (или &force=1).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/images.php';

$root   = dirname(__DIR__);
$dir    = $root . PRODUCT_IMAGES_DIR;
$isCli  = PHP_SAPI === 'cli';

if ($isCli) {
    $apply = in_array('--apply', $argv ?? [], true);
    $force = in_array('--force', $argv ?? [], true);
} else {
    // В браузере скрипт пишет файлы, поэтому закрыт токеном.
    $config = @include $root . '/api/mail-config.php';
    $expected = is_array($config) ? (string)($config['mail_test_token'] ?? '') : '';
    $given = (string)($_GET['token'] ?? '');

    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Доступ закрыт. Добавьте ?token=... — значение mail_test_token из api/mail-config.php.\n");
    }

    $apply = ($_GET['apply'] ?? '') === '1';
    $force = ($_GET['force'] ?? '') === '1';
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
}

function say(string $line): void
{
    echo $line . "\n";
    if (PHP_SAPI !== 'cli') {
        @ob_flush();
        @flush();
    }
}

/* ------------------------------------------------------------ подготовка */

if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
    say('ОШИБКА: не удалось создать папку ' . PRODUCT_IMAGES_DIR);
    exit(1);
}
if (!is_writable($dir)) {
    say('ОШИБКА: папка ' . PRODUCT_IMAGES_DIR . ' недоступна для записи.');
    say('Поставьте на неё права 755 в файловом менеджере хостинга и повторите.');
    exit(1);
}

$jobs = [];
foreach (product_catalog() as $entry) {
    $slug   = (string)($entry['slug'] ?? '');
    $source = (string)($entry['picture_source'] ?? '');
    if ($slug === '' || $source === '' || !preg_match('~^https?://~i', $source)) {
        continue;
    }
    $jobs[] = ['slug' => $slug, 'source' => $source];
}

if (!$jobs) {
    say('Скачивать нечего: ни у одного товара не указан picture_source.');
    say('Значит, все оригиналы уже лежат локально в ' . PRODUCT_IMAGES_DIR);
    exit(0);
}

say(sprintf('Товаров с внешним источником фото: %d%s', count($jobs), $apply ? '' : '  (пробный запуск, ничего не скачивается)'));
say('Папка назначения: ' . PRODUCT_IMAGES_DIR);
say('');

/* -------------------------------------------------------------- загрузка */

$downloaded = 0;
$skipped    = 0;
$failed     = 0;

foreach ($jobs as $job) {
    $slug   = $job['slug'];
    $target = $dir . '/' . $slug . '.jpg';
    say($slug);
    say('    источник: ' . $job['source']);
    say('    файл:     ' . PRODUCT_IMAGES_DIR . '/' . $slug . '.jpg');

    if (is_file($target) && !$force) {
        say('    -> уже есть, пропускаю (добавьте --force, чтобы перекачать)');
        $skipped++;
        say('');
        continue;
    }

    if (!$apply) {
        say('    -> будет скачан');
        say('');
        continue;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'follow_location' => 1,
            'header' => "User-Agent: Mozilla/5.0 (compatible; comp-uter.ru image import)\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $bytes = @file_get_contents($job['source'], false, $context);
    if ($bytes === false || strlen($bytes) < 1024) {
        say('    !! не удалось скачать — файл на сервере не тронут');
        $failed++;
        say('');
        continue;
    }

    // Проверяем, что скачали действительно картинку, а не страницу с ошибкой.
    $info = @getimagesizefromstring($bytes);
    if (!is_array($info) || empty($info[0])) {
        say('    !! по адресу вернулась не картинка — пропускаю');
        $failed++;
        say('');
        continue;
    }

    if (@file_put_contents($target, $bytes) === false) {
        say('    !! не удалось записать файл — проверьте права на папку');
        $failed++;
        say('');
        continue;
    }

    say(sprintf('    -> скачано: %d×%d, %.1f КБ', $info[0], $info[1], strlen($bytes) / 1024));
    $downloaded++;

    // WebP для сайта — необязателен, поэтому любая осечка не считается ошибкой.
    $webp = $dir . '/' . $slug . '.webp';
    if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $image = @imagecreatefromstring($bytes);
        if ($image !== false) {
            if (@imagewebp($image, $webp, 85)) {
                say(sprintf('    -> WebP: %.1f КБ', filesize($webp) / 1024));
            } else {
                say('    (WebP создать не удалось — сайт будет показывать JPG, это нормально)');
            }
            imagedestroy($image);
        }
    } else {
        say('    (на хостинге нет поддержки WebP — сайт будет показывать JPG, это нормально)');
    }

    say('');
}

/* ----------------------------------------------------------------- итог */

say('---------------------------------------------');
if (!$apply) {
    say('Это был пробный запуск. Чтобы действительно скачать:');
    say($isCli ? '    php tools/import-product-images.php --apply'
               : '    добавьте к адресу &apply=1');
    exit(0);
}

say(sprintf('Скачано: %d   пропущено: %d   с ошибкой: %d', $downloaded, $skipped, $failed));
say('');
say('Дальше проверьте результат:  /tools/check-images.php');
if ($failed > 0) {
    say('Для позиций с ошибкой загрузите фото вручную в ' . PRODUCT_IMAGES_DIR);
    say('под именем <идентификатор товара>.jpg — например st10000nm0016.jpg');
}

exit($failed > 0 ? 1 : 0);
