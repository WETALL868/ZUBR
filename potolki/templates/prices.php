<?php
/**
 * Прайс-лист. Все цифры берутся из config/prices.php:
 * на странице нет ни одной вручную вписанной цены, поэтому
 * прайс и калькулятор не могут разойтись.
 */

$p = config('prices');

seo([
    'title'       => 'Цены на натяжные потолки — прайс-лист ' . config('brand.name'),
    'description' => 'Полный прайс-лист: полотна, системы примыкания, освещение, конструкции и дополнительные работы. Цены за м², погонный метр и штуку. Расчёт сметы онлайн.',
    'h1'          => 'Цены на натяжные потолки',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Цены', 'url' => '/prices/'],
    ],
]);
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Прайс-лист · версия <?= e((string) $p['version']) ?></div>
                <h1>Цены на натяжные потолки</h1>
            </div>
            <p class="lead">
                Здесь весь прайс целиком, а не одна заманчивая цифра «от… за метр». Ниже видно, из чего складывается смета:
                материал считается по площади, профиль — по периметру, свет и конструкции — поштучно и метрами.
            </p>
        </div>

        <div class="price-legend">
            <div class="price-note">
                <strong>Материал и работа — разные строки.</strong>
                Дешёвое полотно не означает дешёвый монтаж, поэтому мы показываем обе позиции отдельно.
            </div>
            <div class="price-note">
                <strong>Профиль считается по периметру.</strong>
                Поэтому узкий коридор в 4 м² стоит непропорционально дорого: периметр большой, площадь маленькая.
            </div>
            <div class="price-note">
                <strong>Итог не равен сумме за метр.</strong>
                Точную стоимость даёт <a href="<?= e(site_url('/calculator/')) ?>">калькулятор</a>, окончательную — смета после замера.
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <h2>Полотна</h2>
        <div class="table-wrap" style="margin-top:1.5rem">
            <table>
                <caption>Цена материала за квадратный метр. Монтаж считается отдельной строкой.</caption>
                <thead>
                    <tr>
                        <th scope="col">Полотно</th>
                        <th scope="col" class="num">Материал, ₽/м²</th>
                        <th scope="col" class="num">Надбавка к монтажу, ₽/м²</th>
                        <th scope="col" class="num">Без шва до, м</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($p['canvas'] as $canvas): ?>
                        <tr>
                            <td><?= e($canvas['title']) ?></td>
                            <td class="num"><?= e(money((float) $canvas['material'], false)) ?></td>
                            <td class="num"><?= (float) $canvas['install_extra'] > 0 ? e(money((float) $canvas['install_extra'], false)) : '—' ?></td>
                            <td class="num"><?= e((string) ($canvas['seamless_up_to'] ?? $canvas['max_width_m'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top:3rem">Монтаж полотна</h2>
        <dl class="speclist" style="margin-top:1rem">
            <div>
                <dt>Базовая ставка монтажа</dt>
                <dd><?= e(money((float) $p['install']['base_per_m2'])) ?> за м²</dd>
            </div>
            <div>
                <dt>Минимальная оплачиваемая площадь</dt>
                <dd><?= e((string) $p['install']['min_billable_area_m2']) ?> м² — работы в комнате меньшей площади считаются по этой величине</dd>
            </div>
            <div>
                <dt>Минимальная сумма заказа</dt>
                <dd><?= e(money((float) $p['minimum_order'])) ?> — показывается отдельной строкой, если сумма работ вышла меньше</dd>
            </div>
        </dl>
    </div>
</section>

<section class="section section--tech">
    <div class="container">
        <h2>Системы примыкания</h2>
        <p class="lead" style="margin-top:1rem">Считаются по периметру помещения. В расчёт всегда входит ровно один вариант: это взаимоисключающие решения, а не дополняющие друг друга.</p>

        <div class="table-wrap" style="margin-top:1.5rem">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Система</th>
                        <th scope="col" class="num">₽ за пог. м</th>
                        <th scope="col">Подробнее</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $systemLinks = [
                        'standard' => 'standartnoe-primykanie',
                        'shadow'   => 'teneviye',
                        'gapless'  => 'besshelevye',
                        'floating' => 'paryashchie',
                        'contour'  => 'konturnye',
                    ];
                    ?>
                    <?php foreach ($p['mounting'] as $key => $mounting): ?>
                        <tr>
                            <td><?= e($mounting['title']) ?></td>
                            <td class="num"><?= e(money((float) $mounting['rate'], false)) ?></td>
                            <td>
                                <?php if (isset($systemLinks[$key]) && content_item('systems', $systemLinks[$key]) !== null): ?>
                                    <a href="<?= e(site_url('/systems/' . $systemLinks[$key] . '/')) ?>">Как это выглядит</a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>Дополнительный угол сверх четырёх</td>
                        <td class="num"><?= e(money((float) $p['extra_corner'], false)) ?> / шт.</td>
                        <td>Ниши, эркеры и выступы добавляют углы</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <h2>Освещение</h2>
        <div class="table-wrap" style="margin-top:1.5rem">
            <table>
                <caption>Стоимость светильников как товара не входит в работы: можно ставить свои.</caption>
                <thead>
                    <tr>
                        <th scope="col">Позиция</th>
                        <th scope="col" class="num">Цена</th>
                        <th scope="col">Единица</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lightingRows = [
                        ['Точечный светильник: закладная', $p['lighting']['spot_platform'], 'шт.'],
                        ['Точечный светильник: раскрой и термокольцо', $p['lighting']['spot_cutout'], 'шт.'],
                        ['Точечный светильник: установка и подключение', $p['lighting']['spot_install'], 'шт.'],
                        ['Люстра: закладная', $p['lighting']['chandelier_platform'], 'шт.'],
                        ['Люстра: монтаж', $p['lighting']['chandelier_install'], 'шт.'],
                        ['Световая линия', $p['lighting']['light_line_per_m'], 'пог. м'],
                        ['Встроенная трековая система', $p['lighting']['track_per_m'], 'пог. м'],
                        ['Накладной или подвесной трек', $p['lighting']['track_surface_per_m'], 'пог. м'],
                        ['Светодиодная лента с монтажом', $p['lighting']['led_strip_per_m'], 'пог. м'],
                        ['Блок питания ' . $p['lighting']['power_supply']['capacity_w'] . ' Вт', $p['lighting']['power_supply']['price'], 'шт.'],
                    ];
                    ?>
                    <?php foreach ($lightingRows as [$title, $price, $unit]): ?>
                        <tr>
                            <td><?= e($title) ?></td>
                            <td class="num"><?= e(money((float) $price, false)) ?></td>
                            <td><?= e($unit) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top:3rem">Конструкции и дополнительные работы</h2>
        <div class="table-wrap" style="margin-top:1.5rem">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Позиция</th>
                        <th scope="col" class="num">Цена</th>
                        <th scope="col">Единица</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $structureRows = [
                        ['Скрытый карниз (ниша под шторы)', $p['structures']['hidden_cornice_per_m'], 'пог. м'],
                        ['Электрокарниз', $p['structures']['electric_cornice_per_m'], 'пог. м'],
                        ['Второй уровень', $p['structures']['second_level_per_m'], 'пог. м'],
                        ['Фотопечать', $p['structures']['photo_print_per_m2'], 'м²'],
                        ['Обход трубы', $p['structures']['pipe_bypass'], 'шт.'],
                        ['Обход шкафа или колонны', $p['structures']['cabinet_bypass'], 'шт.'],
                        ['Вентиляционная решётка, диффузор', $p['structures']['vent'], 'шт.'],
                        ['Ревизионный люк', $p['structures']['hatch'], 'шт.'],
                        ['Демонтаж старого потолка', $p['structures']['removal_per_m2'], 'м²'],
                    ];
                    ?>
                    <?php foreach ($structureRows as [$title, $price, $unit]): ?>
                        <tr>
                            <td><?= e($title) ?></td>
                            <td class="num"><?= e(money((float) $price, false)) ?></td>
                            <td><?= e($unit) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section section--warm">
    <div class="container">
        <h2>Коэффициенты и выезд</h2>
        <p class="lead" style="margin-top:1rem">
            Коэффициенты применяются только к монтажным работам. Материал полотна, светильники
            и электрокарниз от высоты стен не дорожают — это принципиальный момент.
        </p>

        <div class="grid grid--2" style="margin-top:2rem">
            <div class="table-wrap">
                <table>
                    <caption>Высота помещения</caption>
                    <thead>
                        <tr><th scope="col">Высота</th><th scope="col" class="num">Коэффициент на работы</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['factors']['height'] as $step): ?>
                            <tr>
                                <td><?= e($step['title']) ?></td>
                                <td class="num"><?= $step['factor'] === null ? 'индивидуально' : e(number_format((float) $step['factor'], 2, ',', ' ')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-wrap">
                <table>
                    <caption>Материал стен</caption>
                    <thead>
                        <tr><th scope="col">Материал</th><th scope="col" class="num">Коэффициент на работы</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['factors']['wall'] as $wall): ?>
                            <tr>
                                <td><?= e($wall['title']) ?></td>
                                <td class="num"><?= e(number_format((float) $wall['factor'], 2, ',', ' ')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <dl class="speclist" style="margin-top:2rem">
            <div>
                <dt>Бесплатная зона выезда</dt>
                <dd>До <?= e((string) $p['travel']['free_km']) ?> км от МКАД включительно</dd>
            </div>
            <div>
                <dt>Выезд сверх бесплатной зоны</dt>
                <dd><?= e(money((float) $p['travel']['rate_per_km'])) ?> за километр, отдельной строкой в смете</dd>
            </div>
            <div>
                <dt>Максимальное расстояние</dt>
                <dd><?= e((string) $p['travel']['max_km']) ?> км от МКАД, дальше — по согласованию</dd>
            </div>
            <div>
                <dt>Срок изготовления</dt>
                <dd><?= e((string) $p['terms_days']['production_min']) ?>–<?= e((string) $p['terms_days']['production_max']) ?> рабочих дней после замера</dd>
            </div>
        </dl>

        <?php if ((float) $p['discount']['percent'] > 0 && filled($p['discount']['title'])): ?>
            <div class="notice" style="margin-top:2rem">
                <strong><?= e((string) $p['discount']['title']) ?></strong>
                Скидка <?= e((string) $p['discount']['percent']) ?> % применяется к работам и материалам,
                кроме выезда, фотопечати и блоков питания.
            </div>
        <?php endif; ?>

        <div class="notice notice--warn" style="margin-top:2rem">
            <strong>Цены не являются публичной офертой.</strong>
            Прайс версии <?= e((string) $p['version']) ?> действует для предварительных расчётов.
            Окончательная стоимость фиксируется в смете после замера — и после подписания не растёт.
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Посчитать смету по вашим размерам';
$ctaText  = 'Калькулятор считает по этому же прайсу и показывает каждую строку. Если удобнее голосом — оставьте телефон.';
include APP_ROOT . '/templates/partials/cta.php';
?>
