<?php

declare(strict_types=1);

/**
 * Работа с очередью писем.
 *
 *   php tools/mail-queue.php status   — сколько писем ждёт отправки
 *   php tools/mail-queue.php send     — попытаться отправить всё
 *   php tools/mail-queue.php clean    — удалить письма старше срока хранения
 *
 * Команду send удобно повесить в cron раз в 10 минут — пример строки cron
 * приведён в README_DEPLOY.md, раздел «Настройка почты».
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Только из командной строки.');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

use Potolki\Mail\Mailer;
use Potolki\Mail\Queue;

$command = $argv[1] ?? 'status';

switch ($command) {
    case 'status':
        $pending = Queue::pending();
        echo sprintf("Писем в очереди: %d\n", count($pending));
        foreach ($pending as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            echo sprintf(
                "  %s — заявка %s, попыток: %d%s\n",
                basename($file),
                $data['id'] ?? '?',
                (int) ($data['attempts'] ?? 0),
                filled($data['last_error'] ?? null) ? ', ошибка: ' . $data['last_error'] : ''
            );
        }
        echo sprintf("Транспорт: %s\n", config('mail.transport'));
        break;

    case 'send':
        $summary = Queue::flush(Mailer::fromConfig());
        echo sprintf(
            "Отправлено: %d, осталось в очереди: %d, отброшено после исчерпания попыток: %d\n",
            $summary['sent'],
            $summary['failed'],
            $summary['given_up']
        );
        if ($summary['given_up'] > 0) {
            echo "ВНИМАНИЕ: часть писем не удалось отправить. Найдите заявки в storage/logs и свяжитесь с клиентами вручную.\n";
        }
        break;

    case 'clean':
        $removed = Queue::cleanup();
        echo sprintf("Удалено старых писем: %d\n", $removed);
        break;

    default:
        echo "Команды: status | send | clean\n";
        exit(1);
}
