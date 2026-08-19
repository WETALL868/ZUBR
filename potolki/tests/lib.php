<?php

declare(strict_types=1);

/**
 * Минимальный тест-раннер. Без PHPUnit и Composer:
 * запускается на любом хостинге командой `php tests/run.php`.
 */
final class T
{
    public static int $passed = 0;
    public static array $failures = [];
    private static string $suite = '';

    public static function suite(string $name): void
    {
        self::$suite = $name;
        echo "\n== {$name} ==\n";
    }

    public static function ok(bool $condition, string $name, string $detail = ''): void
    {
        if ($condition) {
            self::$passed++;
            echo "  ok   {$name}\n";
            return;
        }
        self::$failures[] = self::$suite . ' → ' . $name . ($detail !== '' ? ' | ' . $detail : '');
        echo "  FAIL {$name}" . ($detail !== '' ? " | {$detail}" : '') . "\n";
    }

    public static function eq(mixed $actual, mixed $expected, string $name): void
    {
        self::ok(
            $actual === $expected,
            $name,
            sprintf('ожидалось %s, получено %s', var_export($expected, true), var_export($actual, true))
        );
    }

    public static function near(float $actual, float $expected, string $name, float $eps = 0.01): void
    {
        self::ok(
            abs($actual - $expected) <= $eps,
            $name,
            sprintf('ожидалось %.2f, получено %.2f', $expected, $actual)
        );
    }

    public static function summary(): int
    {
        $failed = count(self::$failures);
        echo "\n----------------------------------------\n";
        echo sprintf("Пройдено: %d, провалено: %d\n", self::$passed, $failed);
        foreach (self::$failures as $failure) {
            echo "  ✗ {$failure}\n";
        }
        return $failed === 0 ? 0 : 1;
    }
}

/**
 * Детерминированный прайс для арифметических проверок:
 * круглые числа, чтобы ожидаемые суммы можно было посчитать в уме.
 * Реальный config/prices.php проверяется отдельным smoke-тестом.
 */
function test_prices(array $overrides = []): array
{
    $prices = [
        'version'  => 'test-1',
        'currency' => 'RUB',
        'rounding' => ['step' => 1, 'mode' => 'nearest'],
        'uncertainty_percent' => 10,
        'minimum_order' => 0,
        'canvas' => [
            'pvc_matte' => ['title' => 'ПВХ матовое', 'material' => 100, 'install_extra' => 0, 'max_width_m' => 3.2],
            'fabric'    => ['title' => 'Ткань',       'material' => 500, 'install_extra' => 50, 'max_width_m' => 5.1],
        ],
        'install' => ['base_per_m2' => 200, 'min_billable_area_m2' => 0],
        'mounting' => [
            'standard' => ['title' => 'Стандартное', 'rate' => 100],
            'shadow'   => ['title' => 'Теневое',     'rate' => 500],
        ],
        'extra_corner' => 300,
        'lighting' => [
            'spot_platform' => 100, 'spot_cutout' => 100, 'spot_install' => 100,
            'chandelier_platform' => 400, 'chandelier_install' => 600,
            'light_line_per_m' => 2000, 'track_per_m' => 1000, 'track_surface_per_m' => 800,
            'led_strip_per_m' => 400, 'led_power_per_m' => 10,
            'power_supply' => ['capacity_w' => 100, 'price' => 2000],
            'spot_product' => 0,
        ],
        'structures' => [
            'pipe_bypass' => 500, 'hidden_cornice_per_m' => 1000, 'electric_cornice_per_m' => 3000,
            'second_level_per_m' => 3000, 'photo_print_per_m2' => 1000, 'vent' => 500,
            'hatch' => 2000, 'cabinet_bypass' => 1000, 'removal_per_m2' => 100,
        ],
        'factors' => [
            'height' => [
                ['max' => 3.00, 'factor' => 1.00, 'title' => 'до 3 м'],
                ['max' => 3.50, 'factor' => 1.10, 'title' => '3–3,5 м'],
                ['max' => null, 'factor' => null, 'title' => 'выше — индивидуально'],
            ],
            'wall' => [
                'concrete' => ['title' => 'Бетон', 'factor' => 1.00],
                'drywall'  => ['title' => 'Гипсокартон', 'factor' => 1.20],
            ],
        ],
        'labor_components' => [
            'base_install', 'mounting_system', 'extra_corners',
            'spots_labor', 'chandeliers_labor', 'light_lines', 'track_system',
            'second_level', 'pipe_bypass', 'hidden_cornice', 'old_ceiling_removal',
        ],
        'travel'   => ['free_km' => 30, 'rate_per_km' => 50, 'max_km' => 120],
        'discount' => ['percent' => 0, 'title' => null, 'exclude' => ['travel', 'photo_print', 'power_supplies']],
        'terms_days' => ['production_min' => 2, 'production_max' => 5, 'install_per_room_hours' => 3],
    ];

    foreach ($overrides as $key => $value) {
        $prices[$key] = is_array($value) && is_array($prices[$key] ?? null)
            ? array_replace_recursive($prices[$key], $value)
            : $value;
    }

    return $prices;
}

/** Найти строку сметы по ключу. */
function test_line(array $room, string $key): ?array
{
    foreach ($room['lines'] as $line) {
        if ($line['key'] === $key) {
            return $line;
        }
    }
    return null;
}
