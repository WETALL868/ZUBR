<?php
/**
 * Калькулятор: форма и панель итога.
 *
 * Переменные:
 *   $calcMode   — режим по умолчанию: 'quick' | 'detailed' | 'rooms'
 *   $calcCity   — название территории для подстановки в заявку
 *   $calcDistance — расстояние от МКАД, км
 *   $calcResult — результат серверного расчёта (режим без JavaScript)
 *
 * Без JavaScript форма отправляется обычным POST на /calculator/,
 * и страница показывает готовую смету. С JavaScript та же форма
 * пересчитывается на лету через /api/calculate.php.
 */

$prices = config('prices');
$calcMode = $calcMode ?? 'quick';
$calcCity = $calcCity ?? null;
$calcDistance = (int) ($calcDistance ?? 0);
$calcResult = $calcResult ?? null;

$canvasOptions = [];
foreach ($prices['canvas'] as $key => $canvas) {
    $canvasOptions[$key] = $canvas['title'];
}

$mountingOptions = [];
foreach ($prices['mounting'] as $key => $mounting) {
    $mountingOptions[$key] = $mounting['title'];
}

$wallOptions = [];
foreach ($prices['factors']['wall'] as $key => $wall) {
    $wallOptions[$key] = $wall['title'];
}

/** Поля одного помещения. Используются и в форме, и в шаблоне для новых комнат. */
$roomFields = static function (int $index) use ($canvasOptions, $mountingOptions, $wallOptions): void {
    $n = 'rooms[' . $index . ']';
    $id = 'room-' . $index;
    ?>
    <div class="calc__room" data-room>
        <div class="calc__room-head">
            <h3 class="calc__room-title" data-room-number>Помещение <?= $index + 1 ?></h3>
            <div class="calc__room-actions" data-visible-in="rooms">
                <button type="button" class="btn btn--ghost btn--small" data-copy-room>Дублировать</button>
                <button type="button" class="btn btn--ghost btn--small" data-remove-room hidden>Удалить</button>
            </div>
        </div>

        <div class="calc__group">
            <div class="calc__group-title">Размеры</div>
            <div class="form__row">
                <div class="field" data-visible-in="rooms">
                    <label for="<?= $id ?>-title">Название</label>
                    <input type="text" id="<?= $id ?>-title" name="<?= $n ?>[title]" maxlength="60" placeholder="Гостиная">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-length">Длина, м</label>
                    <input type="number" id="<?= $id ?>-length" name="<?= $n ?>[length]" min="0.5" max="30" step="0.1" inputmode="decimal" placeholder="4">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-width">Ширина, м</label>
                    <input type="number" id="<?= $id ?>-width" name="<?= $n ?>[width]" min="0.5" max="30" step="0.1" inputmode="decimal" placeholder="5">
                </div>
            </div>

            <div class="form__row" data-visible-in="detailed rooms">
                <div class="field">
                    <label for="<?= $id ?>-area">Площадь, м²</label>
                    <input type="number" id="<?= $id ?>-area" name="<?= $n ?>[area]" min="1" max="500" step="0.1" inputmode="decimal" placeholder="если форма сложная">
                    <span class="hint">Заполните, если комната не прямоугольная</span>
                </div>
                <div class="field">
                    <label for="<?= $id ?>-perimeter">Периметр, пог. м</label>
                    <input type="number" id="<?= $id ?>-perimeter" name="<?= $n ?>[perimeter]" min="2" max="300" step="0.1" inputmode="decimal" placeholder="по периметру стен">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-corners">Количество углов</label>
                    <input type="number" id="<?= $id ?>-corners" name="<?= $n ?>[corners]" min="3" max="60" step="1" inputmode="numeric" value="4">
                    <span class="hint">Каждый угол сверх четырёх считается отдельно</span>
                </div>
            </div>
        </div>

        <div class="calc__group">
            <div class="calc__group-title">Полотно и примыкание</div>
            <div class="form__row">
                <div class="field">
                    <label for="<?= $id ?>-canvas">Полотно</label>
                    <select id="<?= $id ?>-canvas" name="<?= $n ?>[canvas]">
                        <?php foreach ($canvasOptions as $key => $title): ?>
                            <option value="<?= e($key) ?>"><?= e($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="<?= $id ?>-mounting">Примыкание к стене</label>
                    <select id="<?= $id ?>-mounting" name="<?= $n ?>[mounting]">
                        <?php foreach ($mountingOptions as $key => $title): ?>
                            <option value="<?= e($key) ?>"><?= e($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Варианты взаимоисключающие: считается один</span>
                </div>
            </div>

            <div class="form__row" data-visible-in="detailed rooms">
                <div class="field">
                    <label for="<?= $id ?>-height">Высота потолка, м</label>
                    <input type="number" id="<?= $id ?>-height" name="<?= $n ?>[height]" min="2" max="6" step="0.05" inputmode="decimal" value="2.7">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-wall">Материал стен</label>
                    <select id="<?= $id ?>-wall" name="<?= $n ?>[wall]">
                        <?php foreach ($wallOptions as $key => $title): ?>
                            <option value="<?= e($key) ?>"><?= e($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="calc__group">
            <div class="calc__group-title">Освещение</div>
            <div class="form__row">
                <div class="field">
                    <label for="<?= $id ?>-spots">Точечные светильники, шт.</label>
                    <input type="number" id="<?= $id ?>-spots" name="<?= $n ?>[spots]" min="0" max="200" step="1" inputmode="numeric" value="0">
                </div>
                <div class="field" data-visible-in="detailed rooms">
                    <label for="<?= $id ?>-chandeliers">Люстры, шт.</label>
                    <input type="number" id="<?= $id ?>-chandeliers" name="<?= $n ?>[chandeliers]" min="0" max="20" step="1" inputmode="numeric" value="0">
                </div>
                <div class="field" data-visible-in="detailed rooms">
                    <label for="<?= $id ?>-light">Световые линии, пог. м</label>
                    <input type="number" id="<?= $id ?>-light" name="<?= $n ?>[light_line_m]" min="0" max="200" step="0.1" inputmode="decimal" value="0">
                </div>
            </div>

            <div class="form__row" data-visible-in="detailed rooms">
                <div class="field">
                    <label for="<?= $id ?>-track">Встроенный трек, пог. м</label>
                    <input type="number" id="<?= $id ?>-track" name="<?= $n ?>[track_m]" min="0" max="200" step="0.1" inputmode="decimal" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-track-surface">Накладной трек, пог. м</label>
                    <input type="number" id="<?= $id ?>-track-surface" name="<?= $n ?>[track_surface_m]" min="0" max="200" step="0.1" inputmode="decimal" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-led">Светодиодная лента, пог. м</label>
                    <input type="number" id="<?= $id ?>-led" name="<?= $n ?>[led_m]" min="0" max="300" step="0.1" inputmode="decimal" value="0">
                    <span class="hint">Блоки питания посчитаются автоматически</span>
                </div>
            </div>
        </div>

        <div class="calc__group" data-visible-in="detailed rooms">
            <div class="calc__group-title">Конструкции и дополнительные работы</div>
            <div class="form__row">
                <div class="field">
                    <label for="<?= $id ?>-cornice">Скрытый карниз, пог. м</label>
                    <input type="number" id="<?= $id ?>-cornice" name="<?= $n ?>[hidden_cornice_m]" min="0" max="100" step="0.1" inputmode="decimal" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-ecornice">Электрокарниз, пог. м</label>
                    <input type="number" id="<?= $id ?>-ecornice" name="<?= $n ?>[electric_cornice_m]" min="0" max="100" step="0.1" inputmode="decimal" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-second">Второй уровень, пог. м</label>
                    <input type="number" id="<?= $id ?>-second" name="<?= $n ?>[second_level_m]" min="0" max="200" step="0.1" inputmode="decimal" value="0">
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="<?= $id ?>-pipes">Обход труб, шт.</label>
                    <input type="number" id="<?= $id ?>-pipes" name="<?= $n ?>[pipes]" min="0" max="30" step="1" inputmode="numeric" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-cabinets">Обход шкафов, шт.</label>
                    <input type="number" id="<?= $id ?>-cabinets" name="<?= $n ?>[cabinets]" min="0" max="20" step="1" inputmode="numeric" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-vents">Вентиляционные решётки, шт.</label>
                    <input type="number" id="<?= $id ?>-vents" name="<?= $n ?>[vents]" min="0" max="30" step="1" inputmode="numeric" value="0">
                </div>
                <div class="field">
                    <label for="<?= $id ?>-hatches">Ревизионные люки, шт.</label>
                    <input type="number" id="<?= $id ?>-hatches" name="<?= $n ?>[hatches]" min="0" max="20" step="1" inputmode="numeric" value="0">
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="<?= $id ?>-print">Фотопечать, м²</label>
                    <input type="number" id="<?= $id ?>-print" name="<?= $n ?>[photo_print_m2]" min="0" max="200" step="0.1" inputmode="decimal" value="0">
                </div>
                <div class="field">
                    <label class="consent" for="<?= $id ?>-removal" style="margin-top:1.7rem">
                        <input type="checkbox" id="<?= $id ?>-removal" name="<?= $n ?>[removal]" value="1">
                        <span>Нужен демонтаж старого потолка</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="calc">
    <div class="calc__panel">
        <form id="calc-form"
              method="post"
              action="<?= e(site_url('/calculator/')) ?>"
              data-endpoint="<?= e(site_url('/api/calculate.php')) ?>"
              data-start-mode="<?= e($calcMode) ?>"
              data-current-mode="<?= e($calcMode) ?>">

            <div class="calc__tabs" role="tablist" aria-label="Режим расчёта">
                <button type="button" class="calc__tab" role="tab" data-mode="quick" aria-selected="<?= $calcMode === 'quick' ? 'true' : 'false' ?>">Быстрый расчёт</button>
                <button type="button" class="calc__tab" role="tab" data-mode="detailed" aria-selected="<?= $calcMode === 'detailed' ? 'true' : 'false' ?>">Подробный</button>
                <button type="button" class="calc__tab" role="tab" data-mode="rooms" aria-selected="<?= $calcMode === 'rooms' ? 'true' : 'false' ?>">По комнатам</button>
            </div>

            <div data-rooms>
                <?php $roomFields(0); ?>
            </div>

            <div class="btn-row" data-visible-in="rooms" style="margin-top:1.25rem">
                <button type="button" class="btn btn--ghost btn--small" data-add-room>Добавить помещение</button>
            </div>

            <div class="calc__group">
                <div class="calc__group-title">Выезд</div>
                <div class="form__row">
                    <div class="field">
                        <label for="calc-distance">Расстояние от МКАД, км</label>
                        <input type="number" id="calc-distance" name="distance_km" min="0" max="500" step="1"
                               inputmode="numeric" value="<?= e((string) $calcDistance) ?>">
                        <span class="hint">
                            Первые <?= e((string) config('prices.travel.free_km')) ?> км — бесплатно.
                            В пределах Москвы оставьте 0.
                        </span>
                    </div>
                </div>
            </div>

            <noscript>
                <div class="btn-row" style="margin-top:1.5rem">
                    <button type="submit" class="btn btn--accent">Посчитать</button>
                </div>
                <p class="form__note">Без JavaScript расчёт выполняется на сервере после отправки формы — результат появится ниже.</p>
            </noscript>
        </form>
    </div>

    <aside class="calc__summary" id="calc-summary" aria-live="polite">
        <h3>Предварительная смета</h3>

        <div class="calc__total" data-summary-total><?= $calcResult && $calcResult['ok'] ? e(money((float) $calcResult['totals']['total'])) : '—' ?></div>
        <div class="calc__range" data-summary-range><?= $calcResult ? '' : 'Заполните размеры — расчёт появится здесь' ?></div>

        <div class="calc__lines" data-summary-body>
            <?php if ($calcResult && $calcResult['ok']): ?>
                <?php foreach ($calcResult['rooms'] as $room): ?>
                    <div class="calc__line calc__line--room">
                        <span><?= e($room['title']) ?><small><?= e((string) $room['area']) ?> м², периметр <?= e((string) $room['perimeter']) ?> пог. м</small></span>
                        <span><?= e(money((float) $room['subtotal'])) ?></span>
                    </div>
                    <?php foreach ($room['lines'] as $line): ?>
                        <div class="calc__line">
                            <span><?= e($line['title']) ?><small><?= e((string) $line['qty']) ?> <?= e($line['unit']) ?> × <?= e(money((float) $line['rate'])) ?></small></span>
                            <span><?= e(money((float) $line['sum'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?php if (!empty($calcResult['travel']['sum'])): ?>
                    <div class="calc__line">
                        <span>Выезд<small><?= e((string) $calcResult['travel']['billable_km']) ?> км сверх бесплатной зоны</small></span>
                        <span><?= e(money((float) $calcResult['travel']['sum'])) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($calcResult['totals']['minimum_applied']): ?>
                    <div class="calc__line">
                        <span>Минимальная сумма заказа</span>
                        <span><?= e(money((float) $calcResult['totals']['minimum_order'])) ?></span>
                    </div>
                <?php endif; ?>

                <div class="calc__line calc__line--total">
                    <span>Итого</span>
                    <span><?= e(money((float) $calcResult['totals']['total'])) ?></span>
                </div>

                <?php if (!empty($calcResult['warnings'])): ?>
                    <div class="calc__warnings">
                        <?php foreach ($calcResult['warnings'] as $warning): ?>
                            <div><?= e($warning) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($calcResult && !$calcResult['ok']): ?>
                <div class="calc__warnings">
                    <?php foreach ($calcResult['errors'] as $error): ?>
                        <div><?= e((string) $error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <a class="btn btn--accent" href="#calc-lead">Отправить расчёт менеджеру</a>
        <button type="button" class="btn btn--outline-light" data-print-estimate>Распечатать смету</button>

        <p class="calc__disclaimer" data-summary-number>
            <?= $calcResult && $calcResult['ok'] ? 'Номер расчёта: ' . e((string) $calcResult['quote_number']) : '' ?>
        </p>

        <p class="calc__disclaimer">
            Предварительный расчёт по прайсу <?= e((string) config('prices.version')) ?>.
            Не является публичной офертой: окончательная стоимость определяется после замера
            и фиксируется в смете и договоре.
        </p>
    </aside>
</div>

<template id="room-template">
    <?php $roomFields(1); ?>
</template>

<section class="section section--tight" id="calc-lead">
    <div class="section-head">
        <h2>Отправить расчёт и вызвать замерщика</h2>
        <p class="lead">Приложим ваш расчёт к заявке — менеджер увидит те же цифры, что и вы, и не будет переспрашивать параметры.</p>
    </div>
    <?php render_lead_form([
        'form'      => 'calc',
        'id'        => 'calc-lead-form',
        'fields'    => ['name', 'phone', 'email', 'comment'],
        'button'    => 'Отправить расчёт',
        'with_calc' => true,
        'city'      => $calcCity,
    ]); ?>
</section>
