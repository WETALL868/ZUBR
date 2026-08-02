<?php
declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api'
    . DIRECTORY_SEPARATOR . 'appeals' . DIRECTORY_SEPARATOR . 'appeal-id.php';
require_admin();

// Принимаются и новые короткие номера (N48271), и номера прежнего формата:
// обращения, сохранённые до обновления, должны продолжать открываться.
$id = (string)($_GET['id'] ?? '');
if (!is_appeal_id($id)) {
    http_response_code(404);
    exit;
}

$record = load_record(storage_dir() . DIRECTORY_SEPARATOR . $id . '.record.php');
if (!$record || empty($record['attachment']['stored_name'])) {
    http_response_code(404);
    exit;
}

$storedName = basename((string)$record['attachment']['stored_name']);
$path = storage_dir() . DIRECTORY_SEPARATOR . $storedName;
$stream = @fopen($path, 'rb');
if (!$stream) {
    http_response_code(404);
    exit;
}
fgets($stream);

$downloadName = basename((string)$record['attachment']['original_name']);
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'attachment';
header('Content-Type: ' . (string)$record['attachment']['mime']);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Length: ' . (string)$record['attachment']['size']);
fpassthru($stream);
fclose($stream);
