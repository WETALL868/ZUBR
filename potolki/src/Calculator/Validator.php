<?php

declare(strict_types=1);

namespace Potolki\Calculator;

/**
 * Проверка и нормализация входных данных калькулятора.
 *
 * Сервер никогда не доверяет числам из браузера: любое значение
 * приводится к нужному типу и зажимается в допустимый диапазон.
 * Отрицательные и абсурдные значения не «исправляются молча» —
 * по ним возвращается понятная ошибка.
 */
final class Validator
{
    /** Допустимые диапазоны: поле => [min, max, единица] */
    public const RANGES = [
        'length'              => [0.5, 30.0,  'м'],
        'width'               => [0.5, 30.0,  'м'],
        'area'                => [1.0, 500.0, 'м²'],
        'perimeter'           => [2.0, 300.0, 'м'],
        'corners'             => [3,   60,    'шт.'],
        'height'              => [2.0, 6.0,   'м'],
        'spots'               => [0,   200,   'шт.'],
        'chandeliers'         => [0,   20,    'шт.'],
        'pipes'               => [0,   30,    'шт.'],
        'light_line_m'        => [0,   200.0, 'пог. м'],
        'track_m'             => [0,   200.0, 'пог. м'],
        'track_surface_m'     => [0,   200.0, 'пог. м'],
        'led_m'               => [0,   300.0, 'пог. м'],
        'hidden_cornice_m'    => [0,   100.0, 'пог. м'],
        'electric_cornice_m'  => [0,   100.0, 'пог. м'],
        'second_level_m'      => [0,   200.0, 'пог. м'],
        'photo_print_m2'      => [0,   200.0, 'м²'],
        'vents'               => [0,   30,    'шт.'],
        'hatches'             => [0,   20,    'шт.'],
        'cabinets'            => [0,   20,    'шт.'],
        'distance_km'         => [0,   500,   'км'],
        'rooms_count'         => [1,   20,    'шт.'],
    ];

    private array $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /** Добавить собственную ошибку (для составных проверок). */
    public function add(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Число с проверкой диапазона. Возвращает null, если значение
     * не задано, и добавляет ошибку, если значение вне диапазона.
     */
    public function number(mixed $value, string $field, ?string $label = null): ?float
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace([' ', ' ', ','], ['', '', '.'], trim($value));
        }

        if (!is_numeric($value)) {
            $this->errors[$field] = sprintf('Поле «%s»: нужно число.', $label ?? $field);
            return null;
        }

        $number = (float) $value;

        if (!isset(self::RANGES[$field])) {
            return $number;
        }

        [$min, $max, $unit] = self::RANGES[$field];

        if ($number < 0) {
            $this->errors[$field] = sprintf('Поле «%s»: отрицательное значение недопустимо.', $label ?? $field);
            return null;
        }

        if ($number > 0 && $number < $min) {
            $this->errors[$field] = sprintf('Поле «%s»: минимум %s %s.', $label ?? $field, self::num($min), $unit);
            return null;
        }

        if ($number > $max) {
            $this->errors[$field] = sprintf(
                'Поле «%s»: максимум %s %s. Для больших объектов нужен индивидуальный расчёт после замера.',
                $label ?? $field,
                self::num($max),
                $unit
            );
            return null;
        }

        return $number;
    }

    public function int(mixed $value, string $field, ?string $label = null): int
    {
        $number = $this->number($value, $field, $label);
        return $number === null ? 0 : (int) round($number);
    }

    /** Значение из списка разрешённых. Неизвестный ключ — ошибка, а не «подставим первый». */
    public function choice(mixed $value, array $allowed, string $field, string $label, ?string $default = null): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $value = (string) $value;
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = sprintf('Поле «%s»: неизвестный вариант.', $label);
            return $default;
        }
        return $value;
    }

    public function bool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'yes', 'true'], true);
    }

    private static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}
