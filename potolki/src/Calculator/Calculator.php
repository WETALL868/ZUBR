<?php

declare(strict_types=1);

namespace Potolki\Calculator;

/**
 * Расчёт предварительной сметы.
 *
 * Принципы:
 *  • все тарифы приходят из config/prices.php, в коде нет ни одной «зашитой» цены;
 *  • коэффициенты сложности применяются только к монтажным работам,
 *    а не ко всему чеку — материал полотна от высоты стен не дорожает;
 *  • результат — не итоговая цифра, а построчная расшифровка:
 *    позиция, количество, единица, тариф, сумма;
 *  • браузер может показать предварительный результат, но окончательный
 *    расчёт всегда выполняется здесь, на сервере.
 *
 * Результат не является публичной офертой: окончательная цена
 * фиксируется в смете после замера.
 */
final class Calculator
{
    public function __construct(private array $prices)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('prices'));
    }

    /**
     * @param array $input ['rooms' => [...], 'distance_km' => int, 'city' => string]
     */
    public function calculate(array $input): array
    {
        $validator = new Validator();

        $roomsInput = $input['rooms'] ?? [];
        if (!is_array($roomsInput) || $roomsInput === []) {
            return $this->failure(['rooms' => 'Добавьте хотя бы одно помещение.']);
        }
        if (count($roomsInput) > Validator::RANGES['rooms_count'][1]) {
            return $this->failure(['rooms' => 'Слишком много помещений для онлайн-расчёта — посчитаем после замера.']);
        }

        $rooms = [];
        $warnings = [];
        $individual = false;

        foreach (array_values($roomsInput) as $index => $room) {
            $calculated = $this->calculateRoom((array) $room, $index, $validator, $warnings, $individual);
            if ($calculated !== null) {
                $rooms[] = $calculated;
            }
        }

        if ($validator->hasErrors()) {
            return $this->failure($validator->errors());
        }

        if ($rooms === []) {
            return $this->failure(['rooms' => 'Не удалось рассчитать ни одно помещение.']);
        }

        // ── Выезд ─────────────────────────────────────────────────────────
        $travel = $this->travel($input['distance_km'] ?? 0, $validator, $warnings, $individual);
        if ($validator->hasErrors()) {
            return $this->failure($validator->errors());
        }

        // ── Итоги ─────────────────────────────────────────────────────────
        $roomsSubtotal = 0.0;
        $discountBase = 0.0;
        $excluded = (array) ($this->prices['discount']['exclude'] ?? []);

        foreach ($rooms as $room) {
            $roomsSubtotal += $room['subtotal'];
            foreach ($room['lines'] as $line) {
                if (!in_array($line['key'], $excluded, true)) {
                    $discountBase += $line['sum'];
                }
            }
        }

        $subtotal = $roomsSubtotal + $travel['sum'];
        if (!in_array('travel', $excluded, true)) {
            $discountBase += $travel['sum'];
        }

        $discountPercent = (float) ($this->prices['discount']['percent'] ?? 0);
        $discount = $discountPercent > 0 ? $discountBase * $discountPercent / 100 : 0.0;

        $afterDiscount = $subtotal - $discount;

        $minimum = (float) ($this->prices['minimum_order'] ?? 0);
        $minimumApplied = $afterDiscount < $minimum;
        $total = $minimumApplied ? $minimum : $afterDiscount;

        $total = $this->roundTotal($total);

        $uncertainty = (float) ($this->prices['uncertainty_percent'] ?? 15);

        return [
            'ok'        => true,
            'errors'    => [],
            'warnings'  => array_values(array_unique($warnings)),
            'rooms'     => $rooms,
            'travel'    => $travel,
            'totals'    => [
                'rooms_subtotal'   => $this->money($roomsSubtotal),
                'subtotal'         => $this->money($subtotal),
                'discount_percent' => $discountPercent,
                'discount'         => $this->money($discount),
                'after_discount'   => $this->money($afterDiscount),
                'minimum_order'    => $minimum,
                'minimum_applied'  => $minimumApplied,
                'total'            => $total,
            ],
            'range' => [
                'min' => $this->roundTotal($total * (1 - $uncertainty / 100)),
                'max' => $this->roundTotal($total * (1 + $uncertainty / 100)),
                'uncertainty_percent' => $uncertainty,
            ],
            'terms' => [
                'production_min' => (int) ($this->prices['terms_days']['production_min'] ?? 0),
                'production_max' => (int) ($this->prices['terms_days']['production_max'] ?? 0),
                'install_hours'  => (int) ($this->prices['terms_days']['install_per_room_hours'] ?? 0) * count($rooms),
            ],
            'individual_quote' => $individual,
            'price_version'    => (string) ($this->prices['version'] ?? 'н/д'),
            'calculated_at'    => date('c'),
            // Номер расчёта: по нему менеджер найдёт те же цифры, что видел клиент.
            'quote_number'     => $this->quoteNumber($rooms, $total),
            'disclaimer'       => 'Предварительный расчёт. Не является публичной офертой: окончательная стоимость определяется после замера и фиксируется в смете и договоре.',
        ];
    }

    // ── Расчёт одного помещения ───────────────────────────────────────────

    private function calculateRoom(array $room, int $index, Validator $validator, array &$warnings, bool &$individual): ?array
    {
        $n = $index + 1;
        $title = isset($room['title']) && is_string($room['title']) && trim($room['title']) !== ''
            ? mb_substr(trim($room['title']), 0, 60)
            : 'Помещение ' . $n;

        // Геометрия: либо длина/ширина, либо площадь/периметр вручную.
        $length = $validator->number($room['length'] ?? null, 'length', 'Длина');
        $width  = $validator->number($room['width'] ?? null, 'width', 'Ширина');
        $area   = $validator->number($room['area'] ?? null, 'area', 'Площадь');
        $perimeter = $validator->number($room['perimeter'] ?? null, 'perimeter', 'Периметр');

        if ($length !== null && $width !== null) {
            $area = $area ?? round($length * $width, 2);
            $perimeter = $perimeter ?? round(2 * ($length + $width), 2);
        }

        if ($area === null) {
            $errors = $validator->errors();
            if (!isset($errors['area']) && !isset($errors['length']) && !isset($errors['width'])) {
                $validator->add('area', sprintf('«%s»: укажите длину и ширину или площадь помещения.', $title));
            }
            return null;
        }

        if ($perimeter === null) {
            // Для сложной формы периметр не угадываем — просим ввести вручную.
            $perimeter = round(2 * (sqrt($area) + sqrt($area)), 2);
            $warnings[] = sprintf(
                '«%s»: периметр не задан, взят как для прямоугольника со сторонами √S. Для комнаты сложной формы укажите периметр вручную.',
                $title
            );
        }

        $corners = $room['corners'] ?? 4;
        $corners = max(3, $validator->int($corners, 'corners', 'Количество углов'));

        $height = $validator->number($room['height'] ?? null, 'height', 'Высота потолка') ?? 2.7;
        $wallType = $validator->choice(
            $room['wall'] ?? null,
            array_keys((array) $this->prices['factors']['wall']),
            'wall',
            'Материал стен',
            'concrete'
        );
        $canvasKey = $validator->choice(
            $room['canvas'] ?? null,
            array_keys((array) $this->prices['canvas']),
            'canvas',
            'Полотно',
            array_key_first((array) $this->prices['canvas'])
        );
        $mountingKey = $validator->choice(
            $room['mounting'] ?? null,
            array_keys((array) $this->prices['mounting']),
            'mounting',
            'Тип примыкания',
            'standard'
        );

        if ($validator->hasErrors()) {
            return null;
        }

        $canvas = $this->prices['canvas'][$canvasKey];
        $mounting = $this->prices['mounting'][$mountingKey];

        // Минимальная оплачиваемая площадь: маленькую комнату считаем по нижней границе.
        $minArea = (float) ($this->prices['install']['min_billable_area_m2'] ?? 0);
        $billableArea = max($area, $minArea);
        if ($billableArea > $area) {
            $warnings[] = sprintf(
                '«%s»: площадь меньше минимальной оплачиваемой (%s м²), работы считаются по %s м².',
                $title,
                $this->num($minArea),
                $this->num($minArea)
            );
        }

        // Ширина полотна: предупреждаем о сварном шве, но не отказываем.
        $maxWidth = (float) ($canvas['max_width_m'] ?? 0);
        $shortSide = ($length !== null && $width !== null) ? min($length, $width) : null;
        if ($maxWidth > 0 && $shortSide !== null && $shortSide > $maxWidth) {
            $warnings[] = sprintf(
                '«%s»: ширина помещения больше рулона (%s м) — потребуется сварной шов или другое полотно.',
                $title,
                $this->num($maxWidth)
            );
        }

        $lighting = $this->prices['lighting'];
        $structures = $this->prices['structures'];

        $spots       = $validator->int($room['spots'] ?? 0, 'spots', 'Точечные светильники');
        $chandeliers = $validator->int($room['chandeliers'] ?? 0, 'chandeliers', 'Люстры');
        $pipes       = $validator->int($room['pipes'] ?? 0, 'pipes', 'Обход труб');
        $vents       = $validator->int($room['vents'] ?? 0, 'vents', 'Вентиляционные решётки');
        $hatches     = $validator->int($room['hatches'] ?? 0, 'hatches', 'Ревизионные люки');
        $cabinets    = $validator->int($room['cabinets'] ?? 0, 'cabinets', 'Обход шкафов');

        $lightLine   = $validator->number($room['light_line_m'] ?? null, 'light_line_m', 'Световые линии') ?? 0.0;
        $track       = $validator->number($room['track_m'] ?? null, 'track_m', 'Встроенный трек') ?? 0.0;
        $trackSurface= $validator->number($room['track_surface_m'] ?? null, 'track_surface_m', 'Накладной трек') ?? 0.0;
        $led         = $validator->number($room['led_m'] ?? null, 'led_m', 'Светодиодная лента') ?? 0.0;
        $cornice     = $validator->number($room['hidden_cornice_m'] ?? null, 'hidden_cornice_m', 'Скрытый карниз') ?? 0.0;
        $eCornice    = $validator->number($room['electric_cornice_m'] ?? null, 'electric_cornice_m', 'Электрокарниз') ?? 0.0;
        $secondLevel = $validator->number($room['second_level_m'] ?? null, 'second_level_m', 'Второй уровень') ?? 0.0;
        $photoPrint  = $validator->number($room['photo_print_m2'] ?? null, 'photo_print_m2', 'Фотопечать') ?? 0.0;
        $removal     = $validator->bool($room['removal'] ?? false);

        if ($validator->hasErrors()) {
            return null;
        }

        if ($photoPrint > $area) {
            $warnings[] = sprintf('«%s»: площадь фотопечати больше площади потолка — проверьте значение.', $title);
        }

        // ── Позиции сметы ─────────────────────────────────────────────────
        $lines = [];

        $lines[] = $this->line('canvas_material', 'Полотно: ' . $canvas['title'], $area, 'м²', (float) $canvas['material']);

        $baseRate = (float) $this->prices['install']['base_per_m2'] + (float) ($canvas['install_extra'] ?? 0);
        $lines[] = $this->line('base_install', 'Монтаж полотна', $billableArea, 'м²', $baseRate);

        $lines[] = $this->line('mounting_system', 'Профиль по периметру: ' . $mounting['title'], $perimeter, 'пог. м', (float) $mounting['rate']);

        $extraCorners = max($corners - 4, 0);
        if ($extraCorners > 0) {
            $lines[] = $this->line('extra_corners', 'Дополнительные углы', $extraCorners, 'шт.', (float) $this->prices['extra_corner']);
        }

        if ($spots > 0) {
            $spotLabor = (float) $lighting['spot_platform'] + (float) $lighting['spot_cutout'] + (float) $lighting['spot_install'];
            $lines[] = $this->line('spots_labor', 'Точечные светильники: закладная, раскрой, установка', $spots, 'шт.', $spotLabor);
            if ((float) ($lighting['spot_product'] ?? 0) > 0) {
                $lines[] = $this->line('spots_product', 'Светильники (товар)', $spots, 'шт.', (float) $lighting['spot_product']);
            }
        }

        if ($chandeliers > 0) {
            $chandelierRate = (float) $lighting['chandelier_platform'] + (float) $lighting['chandelier_install'];
            $lines[] = $this->line('chandeliers_labor', 'Люстра: закладная и монтаж', $chandeliers, 'шт.', $chandelierRate);
        }

        if ($lightLine > 0) {
            $lines[] = $this->line('light_lines', 'Световые линии', $lightLine, 'пог. м', (float) $lighting['light_line_per_m']);
        }
        if ($track > 0) {
            $lines[] = $this->line('track_system', 'Встроенная трековая система', $track, 'пог. м', (float) $lighting['track_per_m']);
        }
        if ($trackSurface > 0) {
            $lines[] = $this->line('track_surface', 'Накладной или подвесной трек', $trackSurface, 'пог. м', (float) $lighting['track_surface_per_m']);
        }

        if ($led > 0) {
            $lines[] = $this->line('led_strip', 'Светодиодная лента с монтажом', $led, 'пог. м', (float) $lighting['led_strip_per_m']);

            $power = $led * (float) $lighting['led_power_per_m'];
            $capacity = (float) $lighting['power_supply']['capacity_w'];
            if ($capacity > 0) {
                $units = (int) ceil($power / $capacity);
                $lines[] = $this->line(
                    'power_supplies',
                    sprintf('Блоки питания (%s Вт суммарно)', $this->num($power)),
                    $units,
                    'шт.',
                    (float) $lighting['power_supply']['price']
                );
            }
        }

        if ($pipes > 0) {
            $lines[] = $this->line('pipe_bypass', 'Обход труб', $pipes, 'шт.', (float) $structures['pipe_bypass']);
        }
        if ($cabinets > 0) {
            $lines[] = $this->line('cabinet_bypass', 'Обход шкафов и сложных конструкций', $cabinets, 'шт.', (float) $structures['cabinet_bypass']);
        }
        if ($cornice > 0) {
            $lines[] = $this->line('hidden_cornice', 'Скрытый карниз (ниша под штору)', $cornice, 'пог. м', (float) $structures['hidden_cornice_per_m']);
        }
        if ($eCornice > 0) {
            $lines[] = $this->line('electric_cornice', 'Электрокарниз', $eCornice, 'пог. м', (float) $structures['electric_cornice_per_m']);
        }
        if ($secondLevel > 0) {
            $lines[] = $this->line('second_level', 'Второй уровень', $secondLevel, 'пог. м', (float) $structures['second_level_per_m']);
        }
        if ($photoPrint > 0) {
            $lines[] = $this->line('photo_print', 'Фотопечать', $photoPrint, 'м²', (float) $structures['photo_print_per_m2']);
        }
        if ($vents > 0) {
            $lines[] = $this->line('ventilation', 'Вентиляционные решётки и диффузоры', $vents, 'шт.', (float) $structures['vent']);
        }
        if ($hatches > 0) {
            $lines[] = $this->line('access_hatches', 'Ревизионные люки', $hatches, 'шт.', (float) $structures['hatch']);
        }
        if ($removal) {
            $lines[] = $this->line('old_ceiling_removal', 'Демонтаж старого потолка', $area, 'м²', (float) $structures['removal_per_m2']);
        }

        // ── Коэффициенты сложности ────────────────────────────────────────
        $heightFactor = $this->heightFactor($height);
        if ($heightFactor === null) {
            $individual = true;
            $heightFactor = 1.0;
            $warnings[] = sprintf(
                '«%s»: высота больше %s м — точную цену назовём после замера, расчёт даёт только ориентир.',
                $title,
                $this->num($this->maxHeightWithFactor())
            );
        }
        $wallFactor = (float) ($this->prices['factors']['wall'][$wallType]['factor'] ?? 1.0);

        $laborKeys = (array) ($this->prices['labor_components'] ?? []);
        $factor = $heightFactor * $wallFactor;

        $laborSurcharge = 0.0;
        if (abs($factor - 1.0) > 0.0001) {
            foreach ($lines as $i => $line) {
                if (in_array($line['key'], $laborKeys, true)) {
                    $adjusted = round($line['sum'] * $factor, 2);
                    $laborSurcharge += $adjusted - $line['sum'];
                    $lines[$i]['sum'] = $adjusted;
                    $lines[$i]['factor'] = round($factor, 3);
                }
            }
        }

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += $line['sum'];
        }

        return [
            'title'     => $title,
            'area'      => round($area, 2),
            'billable_area' => round($billableArea, 2),
            'perimeter' => round($perimeter, 2),
            'corners'   => $corners,
            'height'    => $height,
            'canvas'    => ['key' => $canvasKey, 'title' => $canvas['title']],
            'mounting'  => ['key' => $mountingKey, 'title' => $mounting['title']],
            'factors'   => [
                'height' => round($heightFactor, 3),
                'wall'   => round($wallFactor, 3),
                'wall_title' => (string) ($this->prices['factors']['wall'][$wallType]['title'] ?? ''),
                'labor_surcharge' => $this->money($laborSurcharge),
            ],
            'lines'     => $lines,
            'subtotal'  => $this->money($subtotal),
        ];
    }

    // ── Выезд ─────────────────────────────────────────────────────────────

    private function travel(mixed $distance, Validator $validator, array &$warnings, bool &$individual): array
    {
        $km = $validator->number($distance, 'distance_km', 'Расстояние от МКАД') ?? 0.0;
        $free = (float) ($this->prices['travel']['free_km'] ?? 0);
        $rate = (float) ($this->prices['travel']['rate_per_km'] ?? 0);
        $max  = (float) ($this->prices['travel']['max_km'] ?? 0);

        if ($max > 0 && $km > $max) {
            $individual = true;
            $warnings[] = sprintf(
                'Расстояние больше %s км от МКАД — выезд согласуем отдельно, в расчёт он не включён.',
                $this->num($max)
            );
            return ['distance_km' => $km, 'billable_km' => 0.0, 'free_km' => $free, 'rate' => $rate, 'sum' => 0.0, 'out_of_zone' => true];
        }

        $billable = max($km - $free, 0.0);

        return [
            'distance_km' => $km,
            'billable_km' => round($billable, 1),
            'free_km'     => $free,
            'rate'        => $rate,
            'sum'         => $this->money($billable * $rate),
            'out_of_zone' => false,
        ];
    }

    // ── Вспомогательное ───────────────────────────────────────────────────

    private function heightFactor(float $height): ?float
    {
        foreach ((array) $this->prices['factors']['height'] as $step) {
            if ($step['max'] === null || $height <= (float) $step['max']) {
                return $step['factor'] === null ? null : (float) $step['factor'];
            }
        }
        return 1.0;
    }

    private function maxHeightWithFactor(): float
    {
        $max = 0.0;
        foreach ((array) $this->prices['factors']['height'] as $step) {
            if ($step['max'] !== null && $step['factor'] !== null) {
                $max = max($max, (float) $step['max']);
            }
        }
        return $max;
    }

    private function line(string $key, string $title, float $qty, string $unit, float $rate): array
    {
        return [
            'key'   => $key,
            'title' => $title,
            'qty'   => round($qty, 2),
            'unit'  => $unit,
            'rate'  => $this->money($rate),
            'sum'   => $this->money($qty * $rate),
        ];
    }

    /** Внутренние вычисления — с точностью до копеек. */
    private function money(float $value): float
    {
        return round($value, 2);
    }

    /** Итог для клиента округляется шагом из прайса. */
    private function roundTotal(float $value): float
    {
        $step = (float) ($this->prices['rounding']['step'] ?? 1);
        if ($step <= 0) {
            return round($value);
        }
        $mode = (string) ($this->prices['rounding']['mode'] ?? 'up');
        return $mode === 'nearest'
            ? round($value / $step) * $step
            : ceil($value / $step) * $step;
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }

    /**
     * Короткий номер расчёта. Зависит от состава сметы и даты,
     * поэтому одинаковый расчёт даёт одинаковый номер в течение дня.
     */
    private function quoteNumber(array $rooms, float $total): string
    {
        $signature = json_encode([$rooms, $total, date('Y-m-d')], JSON_UNESCAPED_UNICODE);

        return 'Р-' . date('ymd') . '-' . strtoupper(substr(md5((string) $signature), 0, 4));
    }

    private function failure(array $errors): array
    {
        return [
            'ok'       => false,
            'errors'   => $errors,
            'warnings' => [],
            'rooms'    => [],
            'totals'   => null,
            'price_version' => (string) ($this->prices['version'] ?? 'н/д'),
        ];
    }
}
