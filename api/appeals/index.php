<?php
/**
 * Приём обращений с формы «Задать вопрос».
 *
 * Главное правило этого файла: ответ всегда должен быть JSON.
 * Раньше формат выбирался по заголовку Accept — JSON отдавался только
 * если в Accept была строка application/json. Обычный fetch() без явного
 * заголовка присылает «Accept: * /*», попадал в ветку HTML и получал
 * страницу, начинающуюся с <!doctype>. Браузер сообщал об этом так:
 * «Unexpected token '<', "<!doctype "... is not valid JSON».
 *
 * Теперь логика обратная: JSON по умолчанию, а HTML-страница —
 * только когда браузер явно просит text/html и не просит JSON, то есть
 * при обычной отправке формы с отключённым JavaScript.
 */
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'appeal-id.php';

// Сообщения PHP не должны попадать в тело ответа и ломать разбор JSON.
ini_set('display_errors', '0');
ini_set('html_errors', '0');

// Вывод буферизуется: если случится фатальная ошибка, буфер отбрасывается
// и посетитель получает корректный JSON, а не обрывок страницы.
ob_start();

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/** Максимальный размер вложения — 5 МБ включительно. */
const ATTACHMENT_MAX_BYTES = 5 * 1024 * 1024;

/** Разрешённые типы вложений: тип => расширение. */
const ATTACHMENT_TYPES = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];

/**
 * Нужна ли посетителю HTML-страница вместо JSON.
 *
 * По умолчанию — нет. HTML отдаётся только обычной отправке формы
 * браузером: там Accept содержит text/html и не содержит application/json.
 */
function wants_html(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));

    // Запрос от скрипта — всегда JSON.
    if (str_contains($accept, 'application/json')) return false;
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== '') return false;

    return str_contains($accept, 'text/html');
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Единственный выход из скрипта. Всегда завершает работу.
 *
 * @param int $status HTTP-статус: 200 — сохранено, 400 — неверные данные,
 *                    413 — слишком большой файл, 415 — неподдерживаемый
 *                    формат, 500 — внутренняя ошибка.
 */
function respond(int $status, array $payload): void
{
    // Всё, что могло попасть в вывод раньше (предупреждения, пробелы),
    // отбрасывается: ответ должен состоять только из тела ниже.
    while (ob_get_level() > 0) ob_end_clean();

    http_response_code($status);

    if (!wants_html()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Ответ для отправки формы без JavaScript: та же информация страницей.
    header('Content-Type: text/html; charset=utf-8');
    $ok = !empty($payload['ok']);
    $title = $ok ? 'Ваше сообщение отправлено' : 'Обращение не отправлено';
    $number = (string)($payload['appealNumber'] ?? '');
    $error = (string)($payload['error'] ?? '');

    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">',
        '<title>', escape_html($title), ' — СНП «Новая Искань»</title>',
        '<link rel="stylesheet" href="/_next/static/chunks/17orm0rfv_bic.css">',
        '<link rel="stylesheet" href="/assets/site.css">',
        '</head><body><main class="content-section content-width">',
        '<div class="modal-window" style="margin:60px auto">',
        '<span>', $ok ? 'Обращение принято' : 'Ошибка', '</span>',
        '<h2>', escape_html($title), '</h2>';

    if ($ok && $number !== '') {
        echo '<p>Номер вашего обращения:</p>',
            '<span class="appeal-number">', escape_html($number), '</span>',
            '<p>Сохраните номер — по нему правление найдёт обращение.</p>';
    } else {
        echo '<p>', escape_html($error !== '' ? $error : 'Не удалось отправить обращение.'), '</p>';
    }

    echo '<a class="button button-dark" href="/appeal">Вернуться к форме</a>',
        '</div></main></body></html>';
    exit;
}

/**
 * Фатальная ошибка тоже должна дойти до посетителя как JSON,
 * иначе клиент снова получит обрывок HTML.
 */
register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($error !== null && in_array($error['type'], $fatal, true)) {
        if (!headers_sent()) {
            respond(500, [
                'ok' => false,
                'error' => 'Внутренняя ошибка сервера. Попробуйте ещё раз позже.',
            ]);
        }
    }
});

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function text_slice(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

/** Переводит «8M», «512K», «2G» из настроек PHP в байты. */
function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') return 0;

    $number = (int)$value;
    return match (strtolower(substr($value, -1))) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => $number,
    };
}

/* ------------------------------------------------------------------ *
 * Метод запроса
 * ------------------------------------------------------------------ */

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));

if ($method !== 'POST') {
    // Ответ на GET тоже JSON: так по адресу /api/appeals/ сразу видно,
    // что маршрут доходит до PHP, а не отдаёт HTML-страницу хостинга.
    respond(405, [
        'ok' => false,
        'error' => 'Обращение отправляется методом POST.',
        'api' => 'appeals',
        'method' => 'POST',
    ]);
}

/* ------------------------------------------------------------------ *
 * Запрос целиком не дошёл до PHP
 *
 * Если тело больше post_max_size, PHP очищает $_POST и $_FILES, но
 * заголовок Content-Length остаётся. Без этой проверки посетитель
 * увидел бы «Укажите имя и фамилию» вместо настоящей причины.
 * ------------------------------------------------------------------ */

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMax = ini_bytes((string)ini_get('post_max_size'));

if ($contentLength > 0 && !$_POST && !$_FILES) {
    $limit = $postMax > 0 ? round($postMax / 1024 / 1024, 1) : null;
    respond(413, [
        'ok' => false,
        'error' => $limit !== null
            ? "Запрос превысил допустимый размер ({$limit} МБ). Приложите файл меньшего размера."
            : 'Запрос превысил допустимый размер. Приложите файл меньшего размера.',
    ]);
}

// Скрытое поле-ловушка: заполняют его только автоматические рассылки.
if (!empty($_POST['website'] ?? '')) {
    respond(400, ['ok' => false, 'error' => 'Не удалось отправить обращение.']);
}

/* ------------------------------------------------------------------ *
 * Проверка полей
 * ------------------------------------------------------------------ */

$fullName = trim((string)($_POST['fullName'] ?? ''));
$plotNumber = trim((string)($_POST['plotNumber'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$topic = trim((string)($_POST['topic'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($fullName === '' || text_length($fullName) > 120) {
    respond(400, ['ok' => false, 'error' => 'Укажите имя и фамилию.']);
}
if ($topic === '' || text_length($topic) > 120) {
    respond(400, ['ok' => false, 'error' => 'Выберите тему обращения.']);
}
if (text_length($message) < 10 || text_length($message) > 5000) {
    respond(400, ['ok' => false, 'error' => 'Текст обращения должен содержать от 10 до 5000 символов.']);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['ok' => false, 'error' => 'Проверьте адрес электронной почты.']);
}
if (!isset($_POST['personalConsent'], $_POST['agreementConsent'])) {
    respond(400, ['ok' => false, 'error' => 'Необходимо принять согласия под формой.']);
}

/* ------------------------------------------------------------------ *
 * Хранилище
 * ------------------------------------------------------------------ */

$storageDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'submissions';
if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true) && !is_dir($storageDir)) {
    respond(500, ['ok' => false, 'error' => 'Сервер не смог создать папку для обращений.']);
}
if (!is_writable($storageDir)) {
    respond(500, ['ok' => false, 'error' => 'Папка для обращений недоступна для записи.']);
}

// Номер подбирается и занимается до записи данных: если свободный номер
// не нашёлся, обращение не сохраняется и посетитель видит честную ошибку,
// а не выдуманное подтверждение.
$reserved = reserve_appeal_id($storageDir);
if ($reserved === null) {
    respond(500, ['ok' => false, 'error' => 'Не удалось присвоить номер обращению. Попробуйте ещё раз.']);
}
[$publicId, $recordHandle, $recordPath] = $reserved;

/**
 * Снимает бронь номера, если сохранить обращение не удалось:
 * иначе номер остался бы занятым пустым файлом.
 */
$releaseReservation = static function () use ($recordHandle, $recordPath): void {
    if (is_resource($recordHandle)) fclose($recordHandle);
    @unlink($recordPath);
};

/* ------------------------------------------------------------------ *
 * Вложение
 * ------------------------------------------------------------------ */

$attachment = null;

if (isset($_FILES['attachment']) && (int)$_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['attachment'];
    $uploadError = (int)$file['error'];

    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        $releaseReservation();
        $limit = ini_bytes((string)ini_get('upload_max_filesize'));
        respond(413, [
            'ok' => false,
            'error' => $limit > 0 && $limit < ATTACHMENT_MAX_BYTES
                ? 'Сервер принимает файлы не больше ' . round($limit / 1024 / 1024, 1)
                  . ' МБ. Обратитесь к администратору сайта.'
                : 'Размер вложения не должен превышать 5 МБ.',
        ]);
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        $releaseReservation();
        respond(500, ['ok' => false, 'error' => 'Не удалось загрузить вложение. Попробуйте ещё раз.']);
    }

    $temporary = (string)$file['tmp_name'];
    if (!is_uploaded_file($temporary)) {
        $releaseReservation();
        respond(400, ['ok' => false, 'error' => 'Не удалось загрузить вложение.']);
    }

    $size = (int)$file['size'];
    if ($size <= 0) {
        $releaseReservation();
        respond(400, ['ok' => false, 'error' => 'Файл пустой.']);
    }
    // Ровно 5 МБ принимается, больше — нет.
    if ($size > ATTACHMENT_MAX_BYTES) {
        $releaseReservation();
        respond(413, ['ok' => false, 'error' => 'Размер вложения не должен превышать 5 МБ.']);
    }

    // Тип определяется по содержимому файла, а не по расширению:
    // расширению доверять нельзя.
    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($temporary);
    }
    $signature = (string)file_get_contents($temporary, false, null, 0, 12);
    if (substr($signature, 0, 5) === '%PDF-') $mime = 'application/pdf';
    if (substr($signature, 0, 3) === "\xFF\xD8\xFF") $mime = 'image/jpeg';
    if (substr($signature, 0, 8) === "\x89PNG\r\n\x1A\n") $mime = 'image/png';

    if (!isset(ATTACHMENT_TYPES[$mime])) {
        $releaseReservation();
        respond(415, ['ok' => false, 'error' => 'Разрешены только PDF, JPG и PNG.']);
    }

    // Имя хранения задаётся сервером и не зависит от присланного:
    // расширение .php и заглушка в начале не дают ни выполнить файл,
    // ни отдать его по прямой ссылке.
    $storedName = $publicId . '.upload.php';
    $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;

    $input = fopen($temporary, 'rb');
    $output = $input ? fopen($storedPath, 'wb') : false;
    if (!$input || !$output) {
        if ($input) fclose($input);
        if ($output) fclose($output);
        @unlink($storedPath);
        $releaseReservation();
        respond(500, ['ok' => false, 'error' => 'Не удалось сохранить вложение.']);
    }

    fwrite($output, "<?php exit; ?>\n");
    $copied = stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);

    if ($copied === false || $copied !== $size) {
        @unlink($storedPath);
        $releaseReservation();
        respond(500, ['ok' => false, 'error' => 'Не удалось сохранить вложение.']);
    }
    chmod($storedPath, 0640);

    $attachment = [
        'stored_name' => $storedName,
        'original_name' => text_slice(basename((string)$file['name']), 160),
        'mime' => $mime,
        'size' => $size,
    ];
}

/* ------------------------------------------------------------------ *
 * Запись обращения
 * ------------------------------------------------------------------ */

$record = [
    'public_id' => $publicId,
    'created_at' => date(DATE_ATOM),
    'full_name' => $fullName,
    'plot_number' => text_slice($plotNumber, 40),
    'phone' => text_slice($phone, 40),
    'email' => text_slice($email, 160),
    'topic' => $topic,
    'message' => $message,
    'attachment' => $attachment,
];

$recordBody = "<?php exit; ?>\n" . base64_encode(serialize($record));
$written = fwrite($recordHandle, $recordBody);
fclose($recordHandle);

if ($written === false || $written < strlen($recordBody)) {
    // Обращение не сохранено — вложение тоже не должно остаться.
    if ($attachment) @unlink($storageDir . DIRECTORY_SEPARATOR . $attachment['stored_name']);
    @unlink($recordPath);
    respond(500, ['ok' => false, 'error' => 'Не удалось сохранить обращение.']);
}
chmod($recordPath, 0640);

/* ------------------------------------------------------------------ *
 * Уведомление на почту — необязательное дополнение
 * ------------------------------------------------------------------ */

$config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config.php';
$recipient = trim((string)($config['recipient_email'] ?? ''));
if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    $subject = 'Новое обращение ' . $publicId;
    $mailBody = "Номер: {$publicId}\nИмя: {$fullName}\nУчасток: {$plotNumber}\n"
        . "Телефон: {$phone}\nEmail: {$email}\nТема: {$topic}\n\n{$message}";
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    @mail($recipient, $encodedSubject, $mailBody, "Content-Type: text/plain; charset=UTF-8\r\n");
}

// Успех подтверждается только здесь — после того как запись действительно
// оказалась на диске.
respond(200, [
    'ok' => true,
    'appealNumber' => $publicId,
    // Прежнее имя поля: чтобы страница из кеша браузера тоже показала номер.
    'publicId' => $publicId,
    'message' => 'Ваше сообщение отправлено',
]);
