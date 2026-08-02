<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

$publicId = 'НИ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
$attachment = null;

if (isset($_FILES['attachment']) && (int)$_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['attachment'];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        respond(422, ['ok' => false, 'error' => 'Не удалось загрузить вложение.']);
    }
    if ((int)$file['size'] > 5 * 1024 * 1024) {
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
        respond(422, ['ok' => false, 'error' => 'Разрешены только PDF, JPG и PNG.']);
    }

    $storedName = $publicId . '.upload.php';
    $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
    $input = fopen((string)$file['tmp_name'], 'rb');
    $output = fopen($storedPath, 'wb');
    if (!$input || !$output) {
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

$recordPath = $storageDir . DIRECTORY_SEPARATOR . $publicId . '.record.php';
$recordBody = "<?php exit; ?>\n" . base64_encode(serialize($record));
if (file_put_contents($recordPath, $recordBody, LOCK_EX) === false) {
    if ($attachment) @unlink($storageDir . DIRECTORY_SEPARATOR . $attachment['stored_name']);
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

respond(200, ['ok' => true, 'publicId' => $publicId]);
