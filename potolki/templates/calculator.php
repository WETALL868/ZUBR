<?php
/**
 * Страница калькулятора.
 *
 * С JavaScript расчёт идёт через /api/calculate.php по мере ввода.
 * Без JavaScript та же форма отправляется сюда обычным POST,
 * и результат считается на сервере ниже.
 */

use Potolki\Calculator\Calculator;

$needsCalculator = true;

seo([
    'title'       => 'Калькулятор натяжных потолков — расчёт сметы онлайн | ' . config('brand.name'),
    'description' => 'Онлайн-калькулятор натяжных потолков: быстрый расчёт, подробная смета и расчёт по комнатам. Показывает каждую строку — полотно, профиль, свет, работы.',
    'h1'          => 'Калькулятор натяжных потолков',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Калькулятор', 'url' => '/calculator/'],
    ],
]);

$calcResult = null;
$calcMode = 'detailed';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Расчёт без JavaScript: считаем на сервере по тем же правилам.
    $calcResult = Calculator::fromConfig()->calculate([
        'rooms'       => $_POST['rooms'] ?? [],
        'distance_km' => $_POST['distance_km'] ?? 0,
    ]);
    $calcMode = 'rooms';
}
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Расчёт по прайсу <?= e((string) config('prices.version')) ?></div>
                <h1>Калькулятор натяжных потолков</h1>
            </div>
            <p class="lead">
                Три режима: быстрая оценка диапазона, подробный расчёт с расшифровкой и расчёт квартиры по комнатам.
                Все позиции видны построчно — с количеством, единицей измерения и ставкой.
            </p>
        </div>

        <div class="grid grid--3" style="margin-bottom:2.5rem">
            <div class="card card--tech">
                <span class="card__index">Режим 1</span>
                <h2 class="card__title">Быстрый расчёт</h2>
                <p class="card__text">Площадь, полотно, примыкание, светильники — и вы видите диапазон ±<?= e((string) config('prices.uncertainty_percent')) ?> %.</p>
            </div>
            <div class="card card--tech">
                <span class="card__index">Режим 2</span>
                <h2 class="card__title">Подробный</h2>
                <p class="card__text">Периметр, углы, высота, материал стен, свет, ниши, обходы, демонтаж — полная смета по позициям.</p>
            </div>
            <div class="card card--tech">
                <span class="card__index">Режим 3</span>
                <h2 class="card__title">По комнатам</h2>
                <p class="card__text">Добавляйте помещения, дублируйте похожие. Итог собирается по всей квартире с общим выездом.</p>
            </div>
        </div>

        <?php include APP_ROOT . '/templates/partials/calc.php'; ?>
    </div>
</section>

<section class="section section--tight">
    <div class="container container--narrow">
        <h2>Как читать расчёт</h2>
        <dl class="speclist" style="margin-top:1.5rem">
            <div>
                <dt>Полотно</dt>
                <dd>Материал по площади помещения. От типа полотна зависит и небольшая надбавка к монтажу.</dd>
            </div>
            <div>
                <dt>Монтаж</dt>
                <dd>Работа по площади. В маленьких помещениях считается по минимальной оплачиваемой площади.</dd>
            </div>
            <div>
                <dt>Профиль</dt>
                <dd>Погонные метры по периметру. Теневые и парящие системы дороже стандартных в несколько раз.</dd>
            </div>
            <div>
                <dt>Коэффициенты</dt>
                <dd>Высота и материал стен влияют только на монтажные работы. Материал полотна и светильники от них не дорожают.</dd>
            </div>
            <div>
                <dt>Выезд</dt>
                <dd>Только километры сверх бесплатной зоны, отдельной строкой.</dd>
            </div>
            <div>
                <dt>Минимальная сумма</dt>
                <dd>Если работ вышло меньше минимума, он показывается отдельной строкой, а не прячется в итог.</dd>
            </div>
        </dl>

        <div class="notice notice--warn" style="margin-top:2rem">
            <strong>Почему это предварительный расчёт.</strong>
            Калькулятор не видит перепад плиты, кривизну стен и фактическое расположение труб — это определяется на замере.
            Итоговая сумма фиксируется в смете и после подписания не меняется.
        </div>
    </div>
</section>
