<?php

declare(strict_types=1);

namespace Potolki\Mail;

use Potolki\Security\Sanitize;
use RuntimeException;
use Throwable;

/**
 * Отправка писем: SMTP (рекомендуется), mail() или режим журнала.
 *
 * Каждое письмо уходит в двух версиях — HTML и обычным текстом:
 * так оно читается и в почтовых клиентах без картинок, и в спам-фильтрах
 * выглядит нормальным письмом, а не рекламной вставкой.
 *
 * Если отправка не удалась, письмо сохраняется в очередь storage/mail-queue
 * и может быть отправлено повторно: php tools/mail-queue.php send
 */
final class Mailer
{
    public function __construct(private array $config)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('mail'));
    }

    /**
     * @param array $message [
     *   'to' => string[], 'subject' => string, 'html' => string, 'text' => string,
     *   'reply_to' => ?string, 'bcc' => string[], 'context' => array
     * ]
     * @return array{sent: bool, transport: string, queued: bool, error: ?string, id: string}
     */
    public function send(array $message): array
    {
        $transport = (string) ($this->config['transport'] ?? 'log');
        $id = $message['context']['lead_id'] ?? bin2hex(random_bytes(6));

        $to = array_values(array_filter(array_map(
            static fn ($addr) => Sanitize::email($addr),
            (array) ($message['to'] ?? [])
        )));

        if ($to === []) {
            return ['sent' => false, 'transport' => $transport, 'queued' => false, 'error' => 'Нет корректного адреса получателя', 'id' => (string) $id];
        }

        $bcc = array_values(array_filter(array_map(
            static fn ($addr) => Sanitize::email($addr),
            (array) ($message['bcc'] ?? [])
        )));

        $built = $this->build($message, $to, $bcc);

        if (strlen($built['raw']) > (int) ($this->config['max_body_bytes'] ?? 262144)) {
            return ['sent' => false, 'transport' => $transport, 'queued' => false, 'error' => 'Письмо слишком большое', 'id' => (string) $id];
        }

        try {
            match ($transport) {
                'smtp' => $this->sendSmtp($built, array_merge($to, $bcc)),
                'mail' => $this->sendMail($built, $to),
                'log'  => $this->sendLog($built),
                default => throw new RuntimeException('Неизвестный транспорт почты: ' . $transport),
            };

            app_log('info', 'mail.sent', ['id' => $id, 'transport' => $transport, 'recipients' => count($to)]);

            return ['sent' => true, 'transport' => $transport, 'queued' => false, 'error' => null, 'id' => (string) $id];
        } catch (Throwable $e) {
            $queued = false;

            // Письмо, которое уже пришло из очереди, повторно в неё не кладём:
            // его судьбой управляет Queue::flush().
            $fromQueue = !empty($message['context']['from_queue']);

            if (!$fromQueue && !empty($this->config['queue']['enabled'])) {
                $queued = Queue::push([
                    'id'      => $id,
                    'to'      => $to,
                    'bcc'     => $bcc,
                    'subject' => $message['subject'] ?? '',
                    'html'    => $message['html'] ?? '',
                    'text'    => $message['text'] ?? '',
                    'reply_to'=> $message['reply_to'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }

            app_log('error', 'mail.failed', [
                'id'        => $id,
                'transport' => $transport,
                'queued'    => $queued,
                'error'     => $e->getMessage(),
            ]);

            return ['sent' => false, 'transport' => $transport, 'queued' => $queued, 'error' => $e->getMessage(), 'id' => (string) $id];
        }
    }

    /** Собирает готовое письмо: заголовки + multipart/alternative. */
    private function build(array $message, array $to, array $bcc): array
    {
        $fromEmail = Sanitize::email($this->config['from']['email'] ?? '') ?? 'no-reply@localhost';
        $fromName  = Sanitize::header((string) ($this->config['from']['name'] ?? ''));
        $subject   = Sanitize::header((string) ($message['subject'] ?? 'Заявка с сайта'));

        $boundary = 'b' . bin2hex(random_bytes(12));

        $text = trim((string) ($message['text'] ?? ''));
        $html = trim((string) ($message['html'] ?? ''));
        if ($text === '') {
            $text = trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</tr>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
        }

        $headers = [
            'From'         => $this->address($fromEmail, $fromName),
            'To'           => implode(', ', $to),
            'Subject'      => $this->encodeHeader($subject),
            'Date'         => date('r'),
            'Message-ID'   => sprintf('<%s@%s>', bin2hex(random_bytes(10)), config('site.domain', 'localhost')),
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer'     => 'site-form',
            'Auto-Submitted' => 'auto-generated',
        ];

        $replyTo = Sanitize::email($message['reply_to'] ?? null);
        if ($replyTo !== null) {
            $headers['Reply-To'] = $replyTo;
        }

        if ($bcc !== []) {
            $headers['Bcc'] = implode(', ', $bcc);
        }

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text)) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$boundary}--\r\n";

        $rawHeaders = '';
        foreach ($headers as $name => $value) {
            $rawHeaders .= $name . ': ' . Sanitize::header((string) $value) . "\r\n";
        }

        return [
            'headers'    => $headers,
            'raw_headers'=> $rawHeaders,
            'body'       => $body,
            'raw'        => $rawHeaders . "\r\n" . $body,
            'from'       => $fromEmail,
            'subject'    => $subject,
        ];
    }

    private function sendSmtp(array $built, array $recipients): void
    {
        $client = new SmtpClient((array) ($this->config['smtp'] ?? []));
        $client->send($built['from'], $recipients, $built['raw']);
    }

    private function sendMail(array $built, array $to): void
    {
        if (!function_exists('mail')) {
            throw new RuntimeException('Функция mail() недоступна на этом хостинге.');
        }

        $headers = $built['headers'];
        unset($headers['To'], $headers['Subject']);

        $rawHeaders = '';
        foreach ($headers as $name => $value) {
            $rawHeaders .= $name . ': ' . Sanitize::header((string) $value) . "\r\n";
        }

        $ok = @mail(
            implode(', ', $to),
            $this->encodeHeader($built['subject']),
            $built['body'],
            rtrim($rawHeaders, "\r\n"),
            '-f' . $built['from']
        );

        if ($ok !== true) {
            throw new RuntimeException('Функция mail() вернула ошибку. Проверьте настройки почты хостинга.');
        }
    }

    /** Режим отладки: письмо не уходит, а сохраняется в лог целиком. */
    private function sendLog(array $built): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/mail-' . date('Y-m-d') . '.log';
        $entry = "\n===== " . date('Y-m-d H:i:s') . " =====\n" . $built['raw_headers'] . "\n" . $built['body'];
        if (@file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Не удалось записать письмо в журнал.');
        }
    }

    private function address(string $email, string $name): string
    {
        return $name === '' ? $email : $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        $value = Sanitize::header($value);
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
