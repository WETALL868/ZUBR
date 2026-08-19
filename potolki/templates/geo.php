<?php
/**
 * Географическая страница: Москва, округ, район, город области, метро.
 *
 * Индексация зависит от качества содержания: если территория не прошла
 * проверку GeoQuality, страница работает, но отдаётся с noindex, follow
 * и не попадает в sitemap. Так устроена защита от дорвеев.
 */

use Potolki\Seo\GeoQuality;
use Potolki\Seo\PageIndex;

$loc = location($slug);

if ($loc === null) {
    render_404();
}

if ($loc['type'] === 'metro' && !(config('geo.metro_enabled') && config('features.metro_pages'))) {
    render_404();
}

$needsCalculator = true;

$url = PageIndex::geoUrl($slug, $loc);
$indexable = location_indexable($loc);
$issues = GeoQuality::issues($loc);

$parent = filled($loc['parent'] ?? null) ? location((string) $loc['parent']) : null;
$parentUrl = filled($loc['parent'] ?? null) ? location_url((string) $loc['parent']) : null;

$crumbs = [['title' => 'Главная', 'url' => '/']];

switch ($loc['type']) {
    case 'okrug':
        $crumbs[] = ['title' => 'Москва', 'url' => '/moskva/'];
        $crumbs[] = ['title' => 'Округа', 'url' => '/moskva/okruga/'];
        break;
    case 'rajon':
        $crumbs[] = ['title' => 'Москва', 'url' => '/moskva/'];
        $crumbs[] = ['title' => 'Районы', 'url' => '/moskva/rajony/'];
        break;
    case 'mo_city':
        $crumbs[] = ['title' => 'Московская область', 'url' => '/moskovskaya-oblast/'];
        $crumbs[] = ['title' => 'Города', 'url' => '/moskovskaya-oblast/goroda/'];
        break;
    case 'metro':
        $crumbs[] = ['title' => 'Станции метро', 'url' => '/metro/'];
        break;
}

$crumbs[] = ['title' => $loc['name'], 'url' => $url];

seo([
    'title'       => $loc['seo']['title'] . ' | ' . config('brand.name'),
    'description' => $loc['seo']['description'],
    'h1'          => $loc['seo']['h1'],
    'canonical'   => absolute_url($url),
    'robots'      => $indexable ? 'index, follow' : 'noindex, follow',
    'breadcrumbs' => $crumbs,
]);

if ($indexable) {
    schema_service(
        $loc['seo']['h1'],
        $loc['seo']['description']
    );
}

$services = related_links($loc['services'] ?? []);
$neighbors = [];

foreach ((array) ($loc['neighbors'] ?? []) as $neighborSlug) {
    $neighbor = location((string) $neighborSlug);
    if ($neighbor === null) {
        continue;
    }
    $neighbors[] = ['url' => location_url((string) $neighborSlug), 'title' => $neighbor['name']];
}

$travel = $loc['travel'];
$faq = faq_pairs($loc['faq'] ?? []);
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">
                    <?= e($loc['type'] === 'mo_city' ? 'Московская область' : ($loc['type'] === 'region' ? 'Область' : 'Москва')) ?>
                </div>
                <h1><?= e($loc['seo']['h1']) ?></h1>
            </div>
            <p class="lead"><?= e($loc['seo']['lead']) ?></p>
        </div>

        <?php if (!$indexable && diagnostics_enabled()): ?>
            <div class="notice notice--warn">
                <strong>Страница не индексируется (видно только вам).</strong>
                Причины: <?= e(implode('; ', $issues)) ?>.
                Заполните данные территории в config/locations/ и запустите php tests/run.php geo.
            </div>
        <?php endif; ?>

        <dl class="hero__facts">
            <div class="hero__fact">
                <dt>Выезд замерщика</dt>
                <dd>
                    <?php if (($travel['zone'] ?? '') === 'free'): ?>
                        Бесплатно
                    <?php elseif (($travel['zone'] ?? '') === 'paid'): ?>
                        <?= e((string) $travel['distance_km']) ?> км от МКАД
                    <?php else: ?>
                        По зоне выезда
                    <?php endif; ?>
                </dd>
            </div>
            <div class="hero__fact">
                <dt>Сроки</dt>
                <dd><?= e((string) ($travel['time'] ?? 'По согласованию')) ?></dd>
            </div>
            <div class="hero__fact">
                <dt>Изготовление</dt>
                <dd><?= e((string) config('prices.terms_days.production_min')) ?>–<?= e((string) config('prices.terms_days.production_max')) ?> рабочих дней</dd>
            </div>
            <div class="hero__fact">
                <dt>Расчёт</dt>
                <dd><a href="#geo-calc">Посчитать смету</a></dd>
            </div>
        </dl>
    </div>
</section>

<?php if (!empty($loc['housing'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Особенности</div>
                <h2>Какие дома здесь встречаются</h2>
            </div>
            <p class="lead">От типа дома зависит крепёж, отступ от плиты и то, какие решения имеет смысл обсуждать на замере.</p>
        </div>

        <div class="grid grid--3">
            <?php foreach ($loc['housing'] as $index => $line): ?>
                <div class="card">
                    <span class="card__index"><?= sprintf('%02d', $index + 1) ?></span>
                    <p class="card__text" style="color:var(--ink);font-size:var(--step-body)"><?= e($line) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($services !== []): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Что заказывают чаще всего</div>
            <h2>Решения <?= e($loc['name_pred']) ?></h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($services as $service): ?>
                <a class="card" href="<?= e(site_url($service['url'])) ?>">
                    <span class="card__title"><?= e($service['title']) ?></span>
                    <?php if (filled($service['text'])): ?>
                        <p class="card__text"><?= e($service['text']) ?>…</p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--tech">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Выезд и стоимость</div>
                <h2>Как считается работа <?= e($loc['name_pred']) ?></h2>
            </div>
            <p class="lead">Цены на работы и материалы одинаковые для всей зоны обслуживания. Отличается только выезд — и он всегда виден отдельной строкой.</p>
        </div>

        <dl class="speclist">
            <div>
                <dt>Замер</dt>
                <dd>
                    <?php if (($travel['zone'] ?? '') === 'free'): ?>
                        Бесплатно, ни к чему не обязывает. <?= e((string) $travel['time']) ?>.
                    <?php else: ?>
                        Бесплатно в пределах <?= e((string) config('prices.travel.free_km')) ?> км от МКАД.
                        <?= filled($travel['surcharge'] ?? null) ? e((string) $travel['surcharge']) . '.' : '' ?>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Выезд</dt>
                <dd>
                    <?php if (($travel['zone'] ?? '') === 'free'): ?>
                        Входит в стоимость: километраж не начисляется.
                    <?php else: ?>
                        <?= e(money((float) config('prices.travel.rate_per_km'))) ?> за километр сверх бесплатной зоны.
                        Ориентировочное расстояние — <?= e((string) $travel['distance_km']) ?> км от МКАД, точное считаем по адресу.
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Наценки за территорию</dt>
                <dd>Нет. Цены на полотно, профиль, свет и работы одинаковые везде, где мы работаем.</dd>
            </div>
            <div>
                <dt>Минимальная сумма заказа</dt>
                <dd><?= e(money((float) config('prices.minimum_order'))) ?>, показывается отдельной строкой в расчёте.</dd>
            </div>
        </dl>
    </div>
</section>

<section class="section" id="geo-calc">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Смета</div>
                <h2>Расчёт с учётом выезда <?= e($loc['name_pred']) ?></h2>
            </div>
            <p class="lead">Расстояние уже подставлено — можно менять. Название территории уйдёт вместе с заявкой, повторять его не нужно.</p>
        </div>

        <?php
        $calcMode = 'quick';
        $calcCity = $loc['name'];
        $calcDistance = location_distance($loc);
        include APP_ROOT . '/templates/partials/calc.php';
        ?>
    </div>
</section>

<section class="section section--warm">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Работы</div>
            <h2>Примеры выполненных работ</h2>
        </div>

        <?php
        $localCases = [];
        foreach (content('portfolio') as $caseSlug => $case) {
            if (($case['location'] ?? null) === $slug) {
                $localCases[$caseSlug] = $case;
            }
        }
        ?>

        <?php if ($localCases !== []): ?>
            <div class="grid grid--3">
                <?php foreach ($localCases as $caseSlug => $case): ?>
                    <a class="card" href="<?= e(site_url('/portfolio/' . $caseSlug . '/')) ?>">
                        <span class="card__title"><?= e($case['title']) ?></span>
                        <span class="card__meta"><span><?= e((string) $case['city']) ?></span><span><?= e((string) $case['area']) ?> м²</span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="notice notice--empty">
                <strong>Отдельных работ <?= e($loc['name_pred']) ?> мы пока не публикуем.</strong>
                Приписывать объекты к территории «для убедительности» мы не будем — это неправда.
                Общий раздел работ пополняется по мере фотосъёмки:
                <a href="<?= e(site_url('/portfolio/')) ?>">смотреть все работы</a>.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($faq !== []): ?>
<section class="section">
    <div class="container container--narrow">
        <div class="section-head">
            <h2>Вопросы <?= e($loc['name_pred']) ?></h2>
        </div>
        <?php
        $faqSchema = $indexable;
        include APP_ROOT . '/templates/partials/faq.php';
        ?>
    </div>
</section>
<?php endif; ?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Рядом</div>
            <h2>Другие территории</h2>
        </div>

        <ul class="tags">
            <?php if ($parent !== null && $parentUrl !== null): ?>
                <li><a class="tag tag--accent" href="<?= e(site_url($parentUrl)) ?>"><?= e($parent['name']) ?></a></li>
            <?php endif; ?>
            <?php foreach ($neighbors as $neighbor): ?>
                <?php if ($neighbor['url'] === null) { continue; } ?>
                <li><a class="tag" href="<?= e(site_url($neighbor['url'])) ?>"><?= e($neighbor['title']) ?></a></li>
            <?php endforeach; ?>
            <li><a class="tag" href="<?= e(site_url('/geography/')) ?>">Все районы и города</a></li>
        </ul>

        <?php if ($loc['type'] === 'okrug'): ?>
            <?php $districts = locations_of('rajon', $slug); ?>
            <?php if ($districts !== []): ?>
                <h3 style="margin-top:2.5rem">Районы округа</h3>
                <ul class="geo-list" style="margin-top:1rem">
                    <?php foreach ($districts as $districtSlug => $district): ?>
                        <li>
                            <a href="<?= e(site_url((string) location_url($districtSlug))) ?>"
                               <?= location_indexable($district) ? '' : 'data-draft="1"' ?>>
                                <?= e($district['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($loc['type'] === 'city'): ?>
            <h3 style="margin-top:2.5rem">Округа Москвы</h3>
            <ul class="geo-list" style="margin-top:1rem">
                <?php foreach (locations_of('okrug', 'moskva') as $okrugSlug => $okrug): ?>
                    <li><a href="<?= e(site_url((string) location_url($okrugSlug))) ?>"><?= e($okrug['name_short'] ?? $okrug['name']) ?> — <?= e($okrug['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($loc['type'] === 'region'): ?>
            <h3 style="margin-top:2.5rem">Города, где мы работаем</h3>
            <ul class="geo-list" style="margin-top:1rem">
                <?php foreach (locations_of('mo_city', 'moskovskaya-oblast') as $citySlug => $city): ?>
                    <li>
                        <a href="<?= e(site_url((string) location_url($citySlug))) ?>"
                           <?= location_indexable($city) ? '' : 'data-draft="1"' ?>>
                            <?= e($city['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Вызвать замерщика ' . $loc['name_pred'];
$ctaText  = 'Согласуем удобное время, приедем с образцами и посчитаем смету на месте. Замер ни к чему не обязывает.';
$ctaForm  = ['city' => $loc['name'], 'form' => 'measure'];
include APP_ROOT . '/templates/partials/cta.php';
?>
