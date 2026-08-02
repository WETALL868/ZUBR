<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'appeal-id.php';

header('X-Content-Type-Options: nosniff');

/**
 * Форма отправляется скриптом (fetch) и получает JSON.
 * Если скрипты отключены, браузер отправляет форму обычным способом —
 * тогда отвечаем страницей, а не голым JSON.
 */
function wants_json(): bool
{
    return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function respond(int $status, array $payload): void
{
    http_response_code($status);

    if (wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Ответ без JavaScript: та же информация обычной страницей.
    header('Content-Type: text/html; charset=utf-8');
    $ok = !empty($payload['ok']);
    $title = $ok ? 'Ваше сообщение отправлено' : 'Обращение не отправлено';
    $number = (string)($payload['publicId'] ?? '');
    $error = (string)($payload['error'] ?? '');
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">',
        '<title>', $escape($title), ' — СНП «Новая Искань»</title>',
        '<link rel="stylesheet" href="/_next/static/chunks/17orm0rfv_bic.css">',
        '<link rel="stylesheet" href="/assets/site.css">',
        '</head><body><main class="content-section content-width">',
        '<div class="modal-window" style="margin:60px auto">',
        '<span>', $ok ? 'Обращение принято' : 'Ошибка', '</span>',
        '<h2>', $escape($title), '</h2>';

    if ($ok && $number !== '') {
        echo '<p>Номер вашего обращения:</p>',
            '<span class="appeal-number">', $escape($number), '</span>',
            '<p>Сохраните номер — по нему правление найдёт обращение.</p>';
    } else {
        echo '<p>', $escape($error !== '' ? $error : 'Не удалось отправить обращение.'), '</p>';
    }

    echo '<a class="button button-dark" href="/appeal">Вернуться к форме</a>',
        '</div></main></body></html>';
    exit;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function text_slice(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Допустима только отправка формы.']);
}

// Скрытое поле-ловушка: заполняют его только автоматические рассылки.
if (!empty($_POST['website'] ?? '')) {
    respond(400, ['ok' => false, 'error' => 'Не удалось отправить обращение.']);
}

$fullName = trim((string)($_POST['fullName'] ?? ''));
$plotNumber = trim((string)($_POST['plotNumber'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$topic = trim((string)($_POST['topic'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($fullName === '' || text_length($fullName) > 120) {
    respond(422, ['ok' => false, 'error' => 'Укажите имя и фамилию.']);
}
if ($topic === '' || text_length($topic) > 120) {
    respond(422, ['ok' => false, 'error' => 'Выберите тему обращения.']);
}
if (text_length($message) < 10 || text_length($message) > 5000) {
    respond(422, ['ok' => false, 'error' => 'Текст обращения должен содержать от 10 до 5000 символов.']);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'error' => 'Проверьте адрес электронной почты.']);
}
if (!isset($_POST['personalConsent'], $_POST['agreementConsent'])) {
    respond(422, ['ok' => false, 'error' => 'Необходимо принять согласия под формой.']);
}

$storageDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'submissions';
if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true) && !is_dir($storageDir)) {
    respond(500, ['ok' => false, 'error' => 'Сервер не смог создать папку для обращений.']);
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
    if (is_resource($recordHandle)) {
        fclose($recordHandle);
    }
    @unlink($recordPath);
};

$attachment = null;

if (isset($_FILES['attachment']) && (int)$_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['attachment'];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $releaseReservation();
        respond(422, ['ok' => false, 'error' => 'Не удалось загрузить вложение.']);
    }
    if ((int)$file['size'] > 5 * 1024 * 1024) {
        $releaseReservation();
        respond(422, ['ok' => false, 'error' => 'Размер вложения не должен превышать 5 МБ.']);
    }

    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file((string)$file['tmp_name']);
    }
    $signature = (string)file_get_contents((string)$file['tmp_name'], false, null, 0, 12);
    if (substr($signature, 0, 5) === '%PDF-') $mime = 'application/pdf';
    if (substr($signature, 0, 3) === "\xFF\xD8\xFF") $mime = 'image/jpeg';
    if (substr($signature, 0, 8) === "\x89PNG\r\n\x1A\n") $mime = 'image/png';
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($allowed[$mime])) {
        $releaseReservation();
        respond(422, ['ok' => false, 'error' => 'Разрешены только PDF, JPG и PNG.']);
    }

    // Расширение .php и заглушка в начале файла не дают выполнить
    // или отдать вложение по прямой ссылке.
    $storedName = $publicId . '.upload.php';
    $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
    $input = fopen((string)$file['tmp_name'], 'rb');
    $output = fopen($storedPath, 'wb');
    if (!$input || !$output) {
        if ($input) fclose($input);
        if ($output) fclose($output);
        $releaseReservation();
        respond(500, ['ok' => false, 'error' => 'Не удалось сохранить вложение.']);
    }
    fwrite($output, "<?php exit; ?>\n");
    stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);
    chmod($storedPath, 0640);

    $attachment = [
        'stored_name' => $storedName,
        'original_name' => basename((string)$file['name']),
        'mime' => $mime,
        'size' => (int)$file['size'],
    ];
}

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
    if ($attachment) @unlink($storageDir . DIRECTORY_SEPARATOR . $attachment['stored_name']);
    @unlink($recordPath);
    respond(500, ['ok' => false, 'error' => 'Не удалось сохранить обращение.']);
}
chmod($recordPath, 0640);

$config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config.php';
$recipient = trim((string)($config['recipient_email'] ?? ''));
if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    $subject = 'Новое обращение ' . $publicId;
    $mailBody = "Номер: {$publicId}\nИмя: {$fullName}\nУчасток: {$plotNumber}\nТелефон: {$phone}\nEmail: {$email}\nТема: {$topic}\n\n{$message}";
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    @mail($recipient, $encodedSubject, $mailBody, "Content-Type: text/plain; charset=UTF-8\r\n");
}

// Успех подтверждается только здесь — после того как запись действительно
// оказалась на диске.
respond(200, ['ok' => true, 'publicId' => $publicId]);
