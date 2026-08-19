<?php

declare(strict_types=1);

namespace Potolki\Mail;

/**
 * Файловая очередь писем.
 *
 * Если почтовый сервер временно недоступен, заявка не теряется:
 * письмо сохраняется в storage/mail-queue и отправляется повторно
 * командой `php tools/mail-queue.php send` (её можно повесить на cron).
 *
 * В очереди лежат персональные данные из заявки, поэтому:
 *  • каталог закрыт от веб-доступа (.htaccess + инструкция для Nginx);
 *  • успешно отправленные файлы удаляются сразу;
 *  • старые файлы чистятся по сроку из config/site.php → runtime.queue_retention_days.
 */
final class Queue
{
    public static function dir(): string
    {
        return APP_ROOT . '/storage/mail-queue';
    }

    public static function push(array $message): bool
    {
        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }

        $message['queued_at'] = date('c');
        $message['attempts'] = (int) ($message['attempts'] ?? 0);

        $file = sprintf('%s/%s-%s.json', $dir, date('Ymd-His'), bin2hex(random_bytes(4)));

        return @file_put_contents($file, json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }

    /** @return string[] пути к файлам очереди */
    public static function pending(): array
    {
        $files = glob(self::dir() . '/*.json') ?: [];
        sort($files);
        return $files;
    }

    /**
     * Повторная отправка. Возвращает сводку по попыткам.
     */
    public static function flush(Mailer $mailer): array
    {
        $maxAttempts = (int) config('mail.queue.max_attempts', 5);
        $summary = ['sent' => 0, 'failed' => 0, 'given_up' => 0];

        foreach (self::pending() as $file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (!is_array($data)) {
                @unlink($file);
                continue;
            }

            $result = $mailer->send([
                'to'       => $data['to'] ?? [],
                'bcc'      => $data['bcc'] ?? [],
                'subject'  => $data['subject'] ?? '',
                'html'     => $data['html'] ?? '',
                'text'     => $data['text'] ?? '',
                'reply_to' => $data['reply_to'] ?? null,
                'context'  => ['lead_id' => $data['id'] ?? null, 'from_queue' => true],
            ]);

            if ($result['sent']) {
                @unlink($file);
                $summary['sent']++;
                continue;
            }

            $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
            $data['last_error'] = $result['error'];
            $data['last_attempt_at'] = date('c');

            if ($data['attempts'] >= $maxAttempts) {
                // Больше не пытаемся: файл удаляем, но факт фиксируем в журнале,
                // чтобы владелец увидел потерянную заявку и связался с клиентом.
                @unlink($file);
                $summary['given_up']++;
                app_log('error', 'mail.queue.given_up', [
                    'id'       => $data['id'] ?? null,
                    'attempts' => $data['attempts'],
                    'error'    => $result['error'],
                ]);
                continue;
            }

            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            $summary['failed']++;
        }

        return $summary;
    }

    /** Удаляет письма старше срока хранения. */
    public static function cleanup(): int
    {
        $days = (int) config('runtime.queue_retention_days', 14);
        if ($days <= 0) {
            return 0;
        }

        $threshold = time() - $days * 86400;
        $removed = 0;

        foreach (self::pending() as $file) {
            if (@filemtime($file) < $threshold) {
                @unlink($file);
                $removed++;
            }
        }

        return $removed;
    }
}
