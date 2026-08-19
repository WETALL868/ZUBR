<?php

declare(strict_types=1);

namespace Potolki\Security;

/**
 * Ограничение частоты отправки форм.
 *
 * Хранилище — файлы в storage/rate-limit, без базы данных.
 * IP не сохраняется в открытом виде: ключом служит его хеш с солью,
 * чтобы в файловой системе не оставалось лишних персональных данных.
 */
final class RateLimit
{
    public function __construct(
        private string $dir,
        private int $maxAttempts = 5,
        private int $windowSeconds = 600,
    ) {
    }

    public static function default(string $bucket = 'lead'): self
    {
        return new self(APP_ROOT . '/storage/rate-limit/' . $bucket);
    }

    /** Разрешена ли ещё одна отправка. Сама попытка не засчитывается. */
    public function allows(string $identity): bool
    {
        return $this->remaining($identity) > 0;
    }

    public function remaining(string $identity): int
    {
        $attempts = $this->attempts($identity);
        return max(0, $this->maxAttempts - count($attempts));
    }

    /** Засчитать попытку. Возвращает false, если лимит исчерпан. */
    public function hit(string $identity): bool
    {
        $attempts = $this->attempts($identity);

        if (count($attempts) >= $this->maxAttempts) {
            return false;
        }

        $attempts[] = time();
        $this->write($identity, $attempts);

        return true;
    }

    /** Через сколько секунд лимит освободится. */
    public function retryAfter(string $identity): int
    {
        $attempts = $this->attempts($identity);
        if ($attempts === []) {
            return 0;
        }
        return max(0, ($attempts[0] + $this->windowSeconds) - time());
    }

    public static function identity(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        // Соль привязана к домену: файлы нельзя переиспользовать для сопоставления IP.
        $salt = (string) config('site.domain');
        return substr(hash('sha256', $ip . '|' . $salt), 0, 32);
    }

    /** @return int[] */
    private function attempts(string $identity): array
    {
        $file = $this->file($identity);
        if (!is_file($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        $data = $raw === false ? [] : (array) json_decode($raw, true);
        $threshold = time() - $this->windowSeconds;

        $attempts = array_values(array_filter(
            array_map('intval', $data),
            static fn (int $ts): bool => $ts > $threshold
        ));

        sort($attempts);

        return $attempts;
    }

    private function write(string $identity, array $attempts): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
        @file_put_contents($this->file($identity), json_encode($attempts), LOCK_EX);
        $this->cleanup();
    }

    private function file(string $identity): string
    {
        return $this->dir . '/' . preg_replace('/[^a-f0-9]/', '', $identity) . '.json';
    }

    /** Удаляет протухшие файлы, чтобы каталог не рос бесконечно. */
    private function cleanup(): void
    {
        if (random_int(1, 20) !== 1 || !is_dir($this->dir)) {
            return;
        }
        $threshold = time() - $this->windowSeconds * 2;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            if (@filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
