<?php

declare(strict_types=1);

namespace Potolki\Security;

/**
 * Защита форм от отправки с чужого сайта.
 *
 * Токен живёт в сессии, сравнивается через hash_equals (без утечки по времени)
 * и обновляется по истечении срока жизни.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf';
    private const LIFETIME = 7200; // 2 часа

    public static function token(): string
    {
        session_start_safe();

        $data = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_array($data) || empty($data['token']) || ($data['created'] ?? 0) + self::LIFETIME < time()) {
            $data = ['token' => bin2hex(random_bytes(32)), 'created' => time()];
            $_SESSION[self::SESSION_KEY] = $data;
        }

        return (string) $data['token'];
    }

    public static function check(?string $token): bool
    {
        session_start_safe();

        $data = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_array($data) || empty($data['token']) || !is_string($token) || $token === '') {
            return false;
        }

        if (($data['created'] ?? 0) + self::LIFETIME < time()) {
            return false;
        }

        return hash_equals((string) $data['token'], $token);
    }

    /** Скрытое поле для вставки в форму. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }
}
