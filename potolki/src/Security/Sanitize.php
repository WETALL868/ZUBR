<?php

declare(strict_types=1);

namespace Potolki\Security;

/**
 * Нормализация пользовательского ввода.
 *
 * Всё, что приходит из формы, проходит через эти методы:
 *  • обрезается по длине (защита от раздувания письма и логов);
 *  • очищается от переводов строк там, где они недопустимы
 *    (иначе через поле «Имя» можно подделать заголовки письма);
 *  • HTML не допускается ни в одном текстовом поле.
 */
final class Sanitize
{
    /** Однострочное текстовое поле. */
    public static function line(mixed $value, int $maxLength = 120): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = strip_tags($value);
        $value = str_replace(["\r", "\n", "\t", "\0"], ' ', $value);
        $value = preg_replace('/\s{2,}/u', ' ', $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength);
    }

    /** Многострочный комментарий: переводы строк сохраняем, HTML — нет. */
    public static function text(mixed $value, int $maxLength = 2000): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $value);
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength);
    }

    /**
     * Телефон приводится к виду +7XXXXXXXXXX.
     * Возвращает null, если номер не похож на российский.
     */
    public static function phone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', is_scalar($value) ? (string) $value : '') ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
            return '+7' . substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '+7' . $digits;
        }

        // Иностранные номера принимаем как есть, но в разумных пределах.
        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        return null;
    }

    /** Красивое отображение российского номера. */
    public static function phoneDisplay(string $normalized): string
    {
        if (preg_match('/^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/', $normalized, $m)) {
            return sprintf('+7 (%s) %s-%s-%s', $m[1], $m[2], $m[3], $m[4]);
        }
        return $normalized;
    }

    /** Email или null. Заодно защищает от подстановки заголовков письма. */
    public static function email(mixed $value): ?string
    {
        $value = self::line($value, 190);
        if ($value === '') {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_EMAIL);
        if ($value === false) {
            return null;
        }

        // Никаких управляющих символов в адресе.
        if (preg_match('/[\r\n\t]/', (string) $value)) {
            return null;
        }

        return (string) $value;
    }

    /** Значение из списка допустимых. */
    public static function choice(mixed $value, array $allowed, ?string $default = null): ?string
    {
        $value = is_scalar($value) ? (string) $value : '';
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** Заголовок письма: без переводов строк, иначе возможна инъекция заголовков. */
    public static function header(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }

    /** Внутренний путь страницы (для поля «страница отправки»). */
    public static function path(mixed $value): string
    {
        $value = self::line($value, 300);
        $path = parse_url($value, PHP_URL_PATH) ?: '/';
        return '/' . ltrim(preg_replace('/[^\w\-\/\.]/u', '', $path) ?? '', '/');
    }
}
