<?php

declare(strict_types=1);

namespace Potolki\Mail;

use RuntimeException;

/**
 * Минимальный SMTP-клиент на сокетах.
 *
 * Зачем свой: чтобы сайт не требовал Composer и работал на обычном хостинге
 * сразу после загрузки файлов. Поддерживает SSL (обычно порт 465),
 * STARTTLS (587) и авторизацию LOGIN / PLAIN.
 *
 * Класс ничего не знает о содержимом письма — только доставляет байты.
 */
final class SmtpClient
{
    /** @var resource|null */
    private $socket = null;
    private array $log = [];

    public function __construct(private array $config)
    {
    }

    /**
     * @param string   $from      адрес отправителя (конверт)
     * @param string[] $recipients список адресов
     * @param string   $message   готовое письмо: заголовки + тело
     */
    public function send(string $from, array $recipients, string $message): void
    {
        $host = (string) ($this->config['host'] ?? '');
        $port = (int) ($this->config['port'] ?? 465);
        $encryption = (string) ($this->config['encryption'] ?? 'ssl');
        $timeout = (int) ($this->config['timeout'] ?? 15);

        if ($host === '') {
            throw new RuntimeException('SMTP: не указан хост.');
        }
        if ($recipients === []) {
            throw new RuntimeException('SMTP: не указан получатель.');
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => (bool) ($this->config['verify_peer'] ?? true),
                'verify_peer_name'  => (bool) ($this->config['verify_peer'] ?? true),
                'allow_self_signed' => !($this->config['verify_peer'] ?? true),
            ],
        ]);

        $address = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $socket = @stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if ($socket === false) {
            throw new RuntimeException(sprintf('SMTP: не удалось подключиться к %s (%d %s)', $address, $errno, $errstr));
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, $timeout);

        try {
            $this->expect([220]);

            $hostname = $this->clientHostname();
            $this->command('EHLO ' . $hostname, [250]);

            if ($encryption === 'tls') {
                $this->command('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP: не удалось включить TLS.');
                }
                $this->command('EHLO ' . $hostname, [250]);
            }

            $username = (string) ($this->config['username'] ?? '');
            $password = (string) ($this->config['password'] ?? '');

            if ($username !== '') {
                $this->authenticate($username, $password);
            }

            $this->command('MAIL FROM:<' . $from . '>', [250]);
            foreach ($recipients as $recipient) {
                $this->command('RCPT TO:<' . $recipient . '>', [250, 251]);
            }

            $this->command('DATA', [354]);
            // Точка в начале строки экранируется, иначе письмо оборвётся.
            $body = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", str_replace("\r\n", "\n", $message)));
            $this->write($body . "\r\n.");
            $this->expect([250]);

            $this->command('QUIT', [221, 250]);
        } finally {
            if (is_resource($this->socket)) {
                @fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    /** Технический лог обмена — без пароля, для диагностики в storage/logs. */
    public function log(): array
    {
        return $this->log;
    }

    private function authenticate(string $username, string $password): void
    {
        // AUTH LOGIN — самый совместимый вариант, PLAIN как запасной.
        try {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($username), [334]);
            $this->command(base64_encode($password), [235]);
        } catch (RuntimeException) {
            $this->command('AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password), [235]);
        }
    }

    private function command(string $command, array $expected): string
    {
        $this->write($command);
        return $this->expect($expected);
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('SMTP: соединение закрыто.');
        }
        $visible = str_starts_with($data, 'AUTH') || strlen($data) > 200 ? '[скрыто]' : $data;
        $this->log[] = '> ' . $visible;
        fwrite($this->socket, $data . "\r\n");
    }

    private function expect(array $codes): string
    {
        $response = '';

        while (is_resource($this->socket)) {
            $line = fgets($this->socket, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Многострочный ответ: «250-...» продолжается, «250 ...» — последний.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $this->log[] = '< ' . trim($response);
        $code = (int) substr(trim($response), 0, 3);

        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP: неожиданный ответ сервера — ' . trim($response));
        }

        return $response;
    }

    private function clientHostname(): string
    {
        $host = (string) config('site.domain', 'localhost');
        return preg_match('/^[\w\.\-]+$/', $host) ? $host : 'localhost';
    }
}
