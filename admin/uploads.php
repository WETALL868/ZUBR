<?php

/**
 * Приём фотографий.
 *
 * Загрузка файлов — самое опасное место любой панели: если посетитель сумеет
 * положить в папку сайта .php и открыть его, он получит сервер. Поэтому здесь
 * три независимых заслона:
 *
 *   1. имя файла придумываем мы сами — присланное не используется вовсе;
 *   2. расширение только из белого списка, и оно назначается по типу
 *      настоящего изображения, а не по тому, что написал браузер;
 *   3. в самой папке лежит .htaccess, запрещающий выполнять там что-либо.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/cms/repo.php';

const UPLOAD_DIR = '/public/images/products';
const UPLOAD_MAX_BYTES = 12 * 1024 * 1024;

/** Типы, которые принимаем, и расширение для каждого. */
const UPLOAD_TYPES = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
];

/** Полный путь к папке загрузок; создаёт её и защиту при первом обращении. */
function upload_dir(): string
{
    $dir = CMS_ROOT . UPLOAD_DIR;

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $guard = $dir . '/.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, <<<'HTACCESS'
# В этой папке лежат только картинки. Выполнять здесь ничего нельзя —
# даже если файл с кодом каким-то образом сюда попадёт.
#
# ЗДЕСЬ НЕ ДОЛЖНО БЫТЬ ДИРЕКТИВ php_admin_value / php_admin_flag.
# PHP разрешает их только в основном конфиге сервера, и на хостинге с
# mod_php Apache отвечает «not allowed here» — то есть ошибкой 500 на
# КАЖДЫЙ файл в папке. Внешне это выглядит так, будто фотографии товаров
# исчезли: браузер не может их загрузить и показывает только подпись ALT.
#
# Запрет ниже работает на любом сервере и делает ровно то, что нужно:
# файл с кодом в этой папке просто нельзя открыть, а значит и выполнить.
<FilesMatch "\.(php|phtml|phar|php[0-9]|cgi|pl|py|sh|htaccess)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Deny from all
  </IfModule>
</FilesMatch>
HTACCESS);
    }

    return $dir;
}

/**
 * Объясняет, почему в папку не удалось записать, и что с этим делать.
 *
 * «Проверьте права на папку» — совет, после которого владелец сайта ставит
 * 777 и идёт дальше. А причина почти всегда другая: архив распаковали от
 * root по SSH, и вся папка сайта принадлежит root, тогда как PHP работает
 * от пользователя хостинга. Права тут ни при чём, менять нужно владельца.
 * Поэтому сообщение называет и папку целиком, и её владельца, и от кого
 * работает PHP, и готовую команду — чтобы не гадать.
 */
function upload_dir_problem(string $dir): string
{
    $lines = ['Не удалось сохранить файл: папка недоступна для записи.'];

    $lines[] = 'Папка: ' . $dir;

    if (is_dir($dir)) {
        $mode  = substr(sprintf('%o', fileperms($dir)), -3);
        $owner = function_exists('posix_getpwuid')
            ? (posix_getpwuid(fileowner($dir))['name'] ?? (string)fileowner($dir))
            : (string)fileowner($dir);
        $lines[] = 'Сейчас: права ' . $mode . ', владелец ' . $owner . '.';
    } else {
        $lines[] = 'Сейчас: папки нет вовсе.';
    }

    $php = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
        ? (posix_getpwuid(posix_geteuid())['name'] ?? '')
        : '';
    if ($php !== '') {
        $lines[] = 'PHP работает от пользователя ' . $php . '.';
        $lines[] = 'Выполните на сервере: chown -R ' . $php . ':' . $php . ' ' . CMS_ROOT
            . ' и затем chmod 775 ' . $dir;
    } else {
        $lines[] = 'Поставьте на папку права 775 и убедитесь, что её владелец — '
            . 'тот же пользователь, от которого работает сайт.';
    }

    return implode(' ', $lines);
}

/**
 * Сохраняет присланный файл.
 *
 * @param array  $file  элемент $_FILES
 * @param string $base  желаемое имя без расширения (например, slug товара)
 * @return array{ok:bool,path?:string,width?:int,height?:int,size?:int,message?:string}
 */
function upload_image(array $file, string $base): array
{
    $code = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($code === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'message' => 'Файл не выбран.'];
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'message' => 'Файл слишком большой для настроек сервера.'];
    }
    if ($code !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Файл не загрузился, попробуйте ещё раз.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        return ['ok' => false, 'message' => 'Файл не принят.'];
    }
    if ((int)($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'message' => 'Файл больше ' . (int)(UPLOAD_MAX_BYTES / 1048576) . ' МБ.'];
    }

    // Настоящий ли это снимок, решает не браузер и не расширение, а разбор
    // содержимого: «фото.jpg» с кодом внутри сюда не пройдёт.
    $info = @getimagesize($tmp);
    if (!is_array($info) || empty($info[0]) || empty($info[1])) {
        return ['ok' => false, 'message' => 'Это не изображение.'];
    }

    $type = (int)($info[2] ?? 0);
    if (!isset(UPLOAD_TYPES[$type])) {
        return ['ok' => false, 'message' => 'Подходят только JPG, PNG и WebP.'];
    }

    $extension = UPLOAD_TYPES[$type];
    $base = preg_replace('~[^a-z0-9._-]+~', '-', mb_strtolower($base)) ?: 'photo';
    $base = trim($base, '-.');
    $base = $base !== '' ? $base : 'photo';

    $dir = upload_dir();
    $name = $base . '.' . $extension;
    $n = 1;
    while (is_file($dir . '/' . $name)) {
        $name = $base . '-' . (++$n) . '.' . $extension;
    }

    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return ['ok' => false, 'message' => upload_dir_problem($dir)];
    }

    @chmod($dir . '/' . $name, 0644);

    return [
        'ok'     => true,
        'path'   => UPLOAD_DIR . '/' . $name,
        'width'  => (int)$info[0],
        'height' => (int)$info[1],
        'size'   => (int)filesize($dir . '/' . $name),
    ];
}

/**
 * Делает фотографию главной для товара.
 *
 * Главная — та, что видна в каталоге, в карточке, в Open Graph и в фиде.
 */
function upload_set_main(int $productId, int $imageId): void
{
    cms_query('UPDATE product_images SET is_main = 0 WHERE product_id = ?', [$productId]);
    cms_query('UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?', [$imageId, $productId]);
}

/** Удаляет запись о фотографии и сам файл, если он больше никому не нужен. */
function upload_delete(int $imageId): void
{
    $image = cms_one('SELECT * FROM product_images WHERE id = ?', [$imageId]);
    if (!$image) {
        return;
    }

    cms_query('DELETE FROM product_images WHERE id = ?', [$imageId]);

    $stillUsed = cms_value('SELECT COUNT(*) FROM product_images WHERE path = ?', [$image['path']]);
    if ((int)$stillUsed === 0 && !str_contains((string)$image['path'], 'placeholder.')) {
        @unlink(CMS_ROOT . '/' . ltrim((string)$image['path'], '/'));
    }

    // Если удалили главную, главной становится следующая по порядку —
    // иначе товар остался бы без фотографии при том, что снимки есть.
    if ((int)$image['is_main'] === 1) {
        $next = cms_value(
            'SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1',
            [(int)$image['product_id']]
        );
        if ($next) {
            upload_set_main((int)$image['product_id'], (int)$next);
        }
    }
}
