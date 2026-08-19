<?php

declare(strict_types=1);

/**
 * Уборка временных данных: логи и очередь писем.
 *
 *   php tools/cleanup.php
 *
 * Сроки хранения задаются в config/site.php:
 *   runtime.log_retention_days, runtime.queue_retention_days.
 *
 * Смысл не в экономии места, а в минимизации данных: в логах и очереди
 * лежат сведения о заявках, и хранить их дольше нужного не следует.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

use Potolki\Mail\Queue;

$logDays = (int) config('runtime.log_retention_days', 30);
$threshold = time() - $logDays * 86400;
$removedLogs = 0;

foreach (glob(APP_ROOT . '/storage/logs/*.log') ?: [] as $file) {
    if (filemtime($file) < $threshold) {
        unlink($file);
        $removedLogs++;
    }
}

$removedQueue = Queue::cleanup();

$removedLimits = 0;
foreach (glob(APP_ROOT . '/storage/rate-limit/*/*.json') ?: [] as $file) {
    if (filemtime($file) < time() - 86400) {
        unlink($file);
        $removedLimits++;
    }
}

echo sprintf(
    "Удалено: логов %d (старше %d дней), писем из очереди %d, счётчиков частоты %d\n",
    $removedLogs,
    $logDays,
    $removedQueue,
    $removedLimits
);
