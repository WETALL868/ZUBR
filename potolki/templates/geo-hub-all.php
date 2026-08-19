<?php
/**
 * /geography/ — компактный переключатель «Где работаем».
 * Именно на него ведут шапка и подвал: два хаба вместо сотен ссылок в меню.
 */

seo([
    'title'       => 'Где мы работаем — Москва и Московская область | ' . config('brand.name'),
    'description' => 'Зона обслуживания: все округа и районы Москвы, города Московской области. Условия выезда, бесплатная зона и километраж.',
    'h1'          => 'Где мы работаем',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Где работаем', 'url' => '/geography/'],
    ],
]);

$okruga = locations_of('okrug', 'moskva');
$cities = locations_of('mo_city', 'moskovskaya-oblast');
$publishedCities = array_filter($cities, static fn (array $c): bool => location_indexable($c));
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Зона обслуживания</div>
                <h1>Где мы работаем</h1>
            </div>
            <p class="lead">
                Москва целиком и города Подмосковья в пределах <?= e((string) config('prices.travel.max_km')) ?> км от МКАД.
                Цены на работы и материалы одинаковые везде — отличается только выезд.
            </p>
        </div>

        <div class="grid grid--2">
            <a class="card" href="<?= e(site_url('/moskva/')) ?>">
                <span class="card__index">Все округа и районы</span>
                <span class="card__title">Москва</span>
                <p class="card__text">Выезд бесплатный, замер обычно в день обращения или на следующий. Работаем и за МКАД в границах города — в Зеленограде, Новомосковском и Троицком округах.</p>
                <span class="card__meta"><span>Округов: <?= count($okruga) ?></span><span>Районов: <?= count(locations_of('rajon')) ?></span></span>
            </a>

            <a class="card" href="<?= e(site_url('/moskovskaya-oblast/')) ?>">
                <span class="card__index">Города и округа области</span>
                <span class="card__title">Московская область</span>
                <p class="card__text">До <?= e((string) config('prices.travel.free_km')) ?> км от МКАД выезд бесплатный, дальше считается километраж — отдельной строкой в смете, без скрытых наценок.</p>
                <span class="card__meta"><span>Городов в списке: <?= count($cities) ?></span><span>С готовыми страницами: <?= count($publishedCities) ?></span></span>
            </a>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2>Округа Москвы</h2>
        </div>
        <ul class="geo-list">
            <?php foreach ($okruga as $slug => $okrug): ?>
                <li>
                    <a href="<?= e(site_url((string) location_url($slug))) ?>">
                        <?= e($okrug['name_short'] ?? $okrug['name']) ?> — <?= e($okrug['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p style="margin-top:1.5rem">
            <a class="link-arrow" href="<?= e(site_url('/moskva/rajony/')) ?>">Список районов Москвы</a>
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2>Города Московской области</h2>
            <p class="lead">Указано ориентировочное расстояние от МКАД: по нему считается выезд.</p>
        </div>
        <ul class="geo-list">
            <?php foreach ($publishedCities as $slug => $city): ?>
                <li>
                    <a href="<?= e(site_url((string) location_url($slug))) ?>">
                        <?= e($city['name']) ?>
                        <span style="color:var(--ink-3)"> · <?= e((string) $city['travel']['distance_km']) ?> км</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p style="margin-top:1.5rem">
            <a class="link-arrow" href="<?= e(site_url('/moskovskaya-oblast/goroda/')) ?>">Полный список городов</a>
        </p>
    </div>
</section>

<?php
$ctaTitle = 'Уточнить условия выезда по адресу';
$ctaText  = 'Напишите адрес — посчитаем километраж, скажем, входит ли он в бесплатную зону, и предложим время замера.';
include APP_ROOT . '/templates/partials/cta.php';
?>
