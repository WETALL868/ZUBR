<?php

declare(strict_types=1);

use Potolki\Calculator\Calculator;

/**
 * Тесты расчёта сметы. Считаем на детерминированном прайсе из tests/lib.php,
 * поэтому все ожидаемые суммы проверяются вручную:
 *
 *   полотно 100 ₽/м², монтаж 200 ₽/м², стандартный профиль 100 ₽/пог. м,
 *   доп. угол 300 ₽, теневой профиль 500 ₽/пог. м, световая линия 2000 ₽/пог. м.
 */

T::suite('Калькулятор: базовая геометрия');

$calc = new Calculator(test_prices());

// Комната 4 × 5: S = 20 м², P = 18 пог. м
$result = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5]]]);

T::ok($result['ok'], 'прямоугольная комната считается без ошибок', json_encode($result['errors'], JSON_UNESCAPED_UNICODE));
T::near($result['rooms'][0]['area'], 20.0, 'площадь = длина × ширина');
T::near($result['rooms'][0]['perimeter'], 18.0, 'периметр = 2 × (длина + ширина)');
T::near(test_line($result['rooms'][0], 'canvas_material')['sum'], 2000.0, 'полотно: 20 м² × 100 ₽');
T::near(test_line($result['rooms'][0], 'base_install')['sum'], 4000.0, 'монтаж: 20 м² × 200 ₽');
T::near(test_line($result['rooms'][0], 'mounting_system')['sum'], 1800.0, 'профиль: 18 пог. м × 100 ₽');
T::near($result['totals']['total'], 7800.0, 'итог комнаты 4 × 5 = 7800 ₽');
T::near($result['range']['min'], 7020.0, 'нижняя граница диапазона −10 %');
T::near($result['range']['max'], 8580.0, 'верхняя граница диапазона +10 %');
T::eq($result['price_version'], 'test-1', 'версия прайса возвращается в расчёте');

// Площадь и периметр заданы вручную (комната сложной формы)
$manual = $calc->calculate(['rooms' => [['area' => 20, 'perimeter' => 18]]]);
T::near($manual['totals']['total'], 7800.0, 'ручной ввод площади и периметра даёт тот же итог');

// Периметр не задан — считаем по прямоугольнику и предупреждаем
$noPerimeter = $calc->calculate(['rooms' => [['area' => 16]]]);
T::ok($noPerimeter['warnings'] !== [], 'без периметра выдаётся предупреждение');
T::near($noPerimeter['rooms'][0]['perimeter'], 16.0, 'периметр по умолчанию для S = 16 м² равен 16 пог. м');

T::suite('Калькулятор: несколько помещений');

$rooms = $calc->calculate(['rooms' => [
    ['title' => 'Гостиная', 'length' => 4, 'width' => 5],
    ['title' => 'Спальня',  'length' => 3, 'width' => 3],
]]);

T::eq(count($rooms['rooms']), 2, 'обе комнаты попали в расчёт');
T::eq($rooms['rooms'][0]['title'], 'Гостиная', 'название комнаты сохраняется');
T::near($rooms['rooms'][1]['subtotal'], 3900.0, 'комната 3 × 3: 900 + 1800 + 1200 = 3900 ₽');
T::near($rooms['totals']['total'], 11700.0, 'сумма по двум комнатам = 11 700 ₽');
T::eq($rooms['terms']['install_hours'], 6, 'ориентировочное время монтажа растёт с числом комнат');

T::suite('Калькулятор: углы, профили, свет');

$corners = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'corners' => 6]]]);
T::near(test_line($corners['rooms'][0], 'extra_corners')['sum'], 600.0, 'два угла сверх четырёх = 600 ₽');
T::near($corners['totals']['total'], 8400.0, 'итог с дополнительными углами');

$fourCorners = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'corners' => 4]]]);
T::ok(test_line($fourCorners['rooms'][0], 'extra_corners') === null, 'при четырёх углах строка доплаты не появляется');

$shadow = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'mounting' => 'shadow']]]);
T::near(test_line($shadow['rooms'][0], 'mounting_system')['sum'], 9000.0, 'теневой профиль: 18 пог. м × 500 ₽');
T::near($shadow['totals']['total'], 15000.0, 'итог с теневым профилем');
T::ok(
    test_line($shadow['rooms'][0], 'mounting_system')['title'] !== test_line($result['rooms'][0], 'mounting_system')['title'],
    'в смете видно, какая именно система примыкания посчитана'
);

$light = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'light_line_m' => 4]]]);
T::near(test_line($light['rooms'][0], 'light_lines')['sum'], 8000.0, 'световая линия: 4 пог. м × 2000 ₽');
T::near($light['totals']['total'], 15800.0, 'итог со световой линией');

$spots = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'spots' => 6, 'chandeliers' => 1]]]);
T::near(test_line($spots['rooms'][0], 'spots_labor')['sum'], 1800.0, 'светильники: 6 × (100 + 100 + 100)');
T::near(test_line($spots['rooms'][0], 'chandeliers_labor')['sum'], 1000.0, 'люстра: закладная 400 + монтаж 600');

// Блоки питания: 25 пог. м × 10 Вт = 250 Вт → 3 блока по 100 Вт
$led = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'led_m' => 25]]]);
T::near(test_line($led['rooms'][0], 'led_strip')['sum'], 10000.0, 'лента: 25 пог. м × 400 ₽');
T::near(test_line($led['rooms'][0], 'power_supplies')['qty'], 3.0, 'блоки питания округляются вверх: ceil(250 / 100) = 3');
T::near(test_line($led['rooms'][0], 'power_supplies')['sum'], 6000.0, 'три блока питания по 2000 ₽');

T::suite('Калькулятор: коэффициенты сложности');

// Высота 3,4 м → коэффициент 1,1 только на работы
$high = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'height' => 3.4]]]);
T::near(test_line($high['rooms'][0], 'canvas_material')['sum'], 2000.0, 'материал полотна от высоты не дорожает');
T::near(test_line($high['rooms'][0], 'base_install')['sum'], 4400.0, 'монтаж дорожает на 10 %');
T::near(test_line($high['rooms'][0], 'mounting_system')['sum'], 1980.0, 'профиль дорожает на 10 %');
T::near($high['totals']['total'], 8380.0, 'итог при высоте 3,4 м');

// Высота вне таблицы → индивидуальный расчёт, но результат всё равно показываем
$tooHigh = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'height' => 4.5]]]);
T::ok($tooHigh['ok'], 'высота выше таблицы не ломает расчёт');
T::ok($tooHigh['individual_quote'], 'высота выше таблицы помечается как индивидуальный расчёт');
T::ok($tooHigh['warnings'] !== [], 'по высокой комнате выдаётся предупреждение');

// Материал стен
$drywall = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'wall' => 'drywall']]]);
T::near(test_line($drywall['rooms'][0], 'base_install')['sum'], 4800.0, 'гипсокартон: работы +20 %');
T::near(test_line($drywall['rooms'][0], 'canvas_material')['sum'], 2000.0, 'материал стен не влияет на цену полотна');

// Комбинация коэффициентов: 1,1 × 1,2 = 1,32
$both = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'height' => 3.4, 'wall' => 'drywall']]]);
T::near(test_line($both['rooms'][0], 'base_install')['sum'], 5280.0, 'коэффициенты перемножаются: 4000 × 1,32');

T::suite('Калькулятор: выезд, скидка, минимальный заказ');

$travel = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5]], 'distance_km' => 50]);
T::near($travel['travel']['billable_km'], 20.0, 'платными считаются километры сверх бесплатной зоны');
T::near($travel['travel']['sum'], 1000.0, 'выезд: 20 км × 50 ₽');
T::near($travel['totals']['total'], 8800.0, 'выезд добавляется к итогу');

$inZone = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5]], 'distance_km' => 25]);
T::near($inZone['travel']['sum'], 0.0, 'в бесплатной зоне выезд не оплачивается');

$farAway = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5]], 'distance_km' => 200]);
T::ok($farAway['travel']['out_of_zone'], 'слишком дальний адрес помечается как согласуемый отдельно');
T::ok($farAway['individual_quote'], 'дальний выезд требует индивидуального согласования');

$discountCalc = new Calculator(test_prices(['discount' => ['percent' => 10]]));
$discounted = $discountCalc->calculate(['rooms' => [['length' => 4, 'width' => 5]], 'distance_km' => 50]);
T::near($discounted['totals']['discount'], 780.0, 'скидка 10 % считается без учёта выезда');
T::near($discounted['totals']['total'], 8020.0, 'итог со скидкой: 7800 + 1000 − 780');

$minCalc = new Calculator(test_prices(['minimum_order' => 20000]));
$minimum = $minCalc->calculate(['rooms' => [['length' => 4, 'width' => 5]]]);
T::ok($minimum['totals']['minimum_applied'], 'сработала минимальная сумма заказа');
T::near($minimum['totals']['total'], 20000.0, 'итог поднят до минимальной суммы');
T::near($minimum['totals']['subtotal'], 7800.0, 'сумма работ показывается отдельно от минимума');

T::suite('Калькулятор: округление');

$roundUp = new Calculator(test_prices(['rounding' => ['step' => 10, 'mode' => 'up'], 'install' => ['base_per_m2' => 201]]));
$rounded = $roundUp->calculate(['rooms' => [['length' => 4, 'width' => 5]]]);
// 2000 + 20 × 201 = 2000 + 4020 + 1800 = 7820 → шаг 10 не меняет значение
T::near($rounded['totals']['total'], 7820.0, 'округление вверх с шагом 10 ₽');

$roundOdd = new Calculator(test_prices(['rounding' => ['step' => 100, 'mode' => 'up']]));
$oddResult = $roundOdd->calculate(['rooms' => [['length' => 4, 'width' => 5]]]);
T::near($oddResult['totals']['total'], 7800.0, 'сумма, кратная шагу, не растёт при округлении');

T::suite('Калькулятор: некорректные данные');

$empty = $calc->calculate(['rooms' => []]);
T::ok(!$empty['ok'], 'пустой список помещений отклоняется');

$negative = $calc->calculate(['rooms' => [['length' => -4, 'width' => 5]]]);
T::ok(!$negative['ok'], 'отрицательная длина отклоняется');
T::ok(isset($negative['errors']['length']), 'ошибка привязана к конкретному полю');

$huge = $calc->calculate(['rooms' => [['area' => 5000]]]);
T::ok(!$huge['ok'], 'нереальная площадь отклоняется');

$text = $calc->calculate(['rooms' => [['length' => 'четыре', 'width' => 5]]]);
T::ok(!$text['ok'], 'текст вместо числа отклоняется');

$unknownCanvas = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'canvas' => 'gold-plated']]]);
T::ok(!$unknownCanvas['ok'], 'неизвестное полотно отклоняется, а не подменяется молча');

$tooMany = $calc->calculate(['rooms' => array_fill(0, 25, ['length' => 3, 'width' => 3])]);
T::ok(!$tooMany['ok'], 'слишком много помещений — предлагаем расчёт после замера');

$negativeSpots = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5, 'spots' => -3]]]);
T::ok(!$negativeSpots['ok'], 'отрицательное количество светильников отклоняется');

// Клиент прислал «свой» итог — сервер его игнорирует
$spoofed = $calc->calculate(['rooms' => [['length' => 4, 'width' => 5]], 'total' => 1]);
T::near($spoofed['totals']['total'], 7800.0, 'сумма из браузера игнорируется, сервер считает заново');

T::suite('Калькулятор: минимальная оплачиваемая площадь');

$smallCalc = new Calculator(test_prices(['install' => ['min_billable_area_m2' => 6]]));
$small = $smallCalc->calculate(['rooms' => [['length' => 2, 'width' => 2]]]);
T::near(test_line($small['rooms'][0], 'canvas_material')['sum'], 400.0, 'полотно считается по фактической площади');
T::near(test_line($small['rooms'][0], 'base_install')['sum'], 1200.0, 'работы считаются по минимальной площади 6 м²');
T::ok($small['warnings'] !== [], 'о минимальной площади предупреждаем явно');

T::suite('Калькулятор: рабочий прайс сайта');

$real = Calculator::fromConfig()->calculate([
    'rooms' => [['length' => 4, 'width' => 5, 'spots' => 6, 'mounting' => 'standard']],
    'distance_km' => 0,
]);
T::ok($real['ok'], 'расчёт на боевом прайсе выполняется', json_encode($real['errors'], JSON_UNESCAPED_UNICODE));
T::ok($real['totals']['total'] > 0, 'итог на боевом прайсе больше нуля');
T::eq($real['price_version'], (string) config('prices.version'), 'в расчёт попадает версия боевого прайса');
T::ok(
    str_contains(mb_strtolower($real['disclaimer']), 'не является публичной офертой'),
    'расчёт подписан как предварительный'
);
