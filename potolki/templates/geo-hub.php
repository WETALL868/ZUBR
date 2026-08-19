<?php
/**
 * Хаб списка территорий: округа, районы, города, станции метро.
 *
 * Списки длинные, поэтому здесь есть поиск и группировка.
 * В шапке сайта эти ссылки не выводятся — глубина достигается
 * хабами и картой сайта, а не пунктами меню.
 *
 * Параметры: $group ('okrug' | 'rajon' | 'mo_city' | 'metro'), $parent.
 */

$meta = [
    'okrug' => [
        'url'   => '/moskva/okruga/',
        'h1'    => 'Административные округа Москвы',
        'title' => 'Натяжные потолки по округам Москвы — ЦАО, САО, СВАО и другие',
        'description' => 'Монтаж натяжных потолков по административным округам Москвы: особенности застройки, условия выезда и расчёт стоимости.',
        'lead'  => 'Работаем по всем округам. Цены одинаковые везде — отличается только застройка, а значит и то, какие решения имеет смысл обсуждать.',
        'parent'=> ['title' => 'Москва', 'url' => '/moskva/'],
    ],
    'rajon' => [
        'url'   => '/moskva/rajony/',
        'h1'    => 'Районы Москвы',
        'title' => 'Натяжные потолки по районам Москвы — полный список',
        'description' => 'Список районов Москвы, где мы устанавливаем натяжные потолки. Выезд замерщика по всему городу, цены единые.',
        'lead'  => 'Полный список районов с группировкой по округам. Страницы районов наполняются постепенно: мы публикуем их только тогда, когда есть что сказать по существу.',
        'parent'=> ['title' => 'Москва', 'url' => '/moskva/'],
    ],
    'mo_city' => [
        'url'   => '/moskovskaya-oblast/goroda/',
        'h1'    => 'Города Московской области',
        'title' => 'Натяжные потолки в городах Московской области — список',
        'description' => 'Города Подмосковья, где мы работаем: условия выезда, расстояние от МКАД, расчёт стоимости с учётом километража.',
        'lead'  => 'До 30 км от МКАД выезд входит в стоимость, дальше считается километраж. У каждого города указано ориентировочное расстояние.',
        'parent'=> ['title' => 'Московская область', 'url' => '/moskovskaya-oblast/'],
    ],
    'metro' => [
        'url'   => '/metro/',
        'h1'    => 'Станции метро',
        'title' => 'Натяжные потолки рядом с метро',
        'description' => 'Список станций метро, по которым готовятся страницы.',
        'lead'  => 'Раздел развивается поштучно: страница станции появляется только тогда, когда для неё есть собственное содержание.',
        'parent'=> ['title' => 'Москва', 'url' => '/moskva/'],
    ],
];

$m = $meta[$group];
$parentSlug = $parent ?? null;
$items = locations_of($group, $parentSlug);

seo([
    'title'       => $m['title'] . ' | ' . config('brand.name'),
    'description' => $m['description'],
    'h1'          => $m['h1'],
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => $m['parent']['title'], 'url' => $m['parent']['url']],
        ['title' => $m['h1'], 'url' => $m['url']],
    ],
]);

// Районы группируются по округам, остальное — по алфавиту.
$groups = [];

if ($group === 'rajon') {
    foreach (locations_of('okrug', 'moskva') as $okrugSlug => $okrug) {
        $districts = locations_of('rajon', $okrugSlug);
        if ($districts !== []) {
            $groups[$okrug['name']] = $districts;
        }
    }
} else {
    $sorted = $items;
    uasort($sorted, static fn (array $a, array $b): int => strcoll((string) $a['name'], (string) $b['name']));

    foreach ($sorted as $itemSlug => $item) {
        $letter = mb_strtoupper(mb_substr((string) $item['name'], 0, 1));
        $groups[$letter][$itemSlug] = $item;
    }
}

$published = array_filter($items, static fn (array $i): bool => location_indexable($i));
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow"><?= e($m['parent']['title']) ?></div>
                <h1><?= e($m['h1']) ?></h1>
            </div>
            <p class="lead"><?= e($m['lead']) ?></p>
        </div>

        <div class="geo-search">
            <label for="geo-search-input">Поиск по списку</label>
            <input type="search" id="geo-search-input" data-geo-search placeholder="Начните вводить название" autocomplete="off">
            <span class="hint" data-geo-count></span>
        </div>

        <p class="meta-line">
            <span>Всего в списке: <?= count($items) ?></span>
            <span>С готовыми страницами: <?= count($published) ?></span>
            <span>Остальные наполняются</span>
        </p>

        <div class="geo-groups" style="margin-top:2rem">
            <?php foreach ($groups as $groupTitle => $groupItems): ?>
                <div data-geo-group>
                    <h2 class="geo-group__title"><?= e((string) $groupTitle) ?></h2>
                    <ul class="geo-list">
                        <?php foreach ($groupItems as $itemSlug => $item): ?>
                            <li data-geo-item="<?= e(mb_strtolower((string) $item['name'])) ?>">
                                <a href="<?= e(site_url((string) location_url($itemSlug))) ?>"
                                   <?= location_indexable($item) ? '' : 'data-draft="1"' ?>>
                                    <?= e($item['name']) ?><?php if ($group === 'mo_city' && (int) ($item['travel']['distance_km'] ?? 0) > 0): ?>
                                        <span style="color:var(--ink-3)"> · <?= e((string) $item['travel']['distance_km']) ?> км</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="notice" style="margin-top:2.5rem">
            <strong>Почему опубликованы не все страницы.</strong>
            Страница территории имеет смысл, только если на ней есть что-то своё: особенности домов,
            условия выезда, работы поблизости. Размножать один текст с заменой названия мы не будем —
            это не помогает ни вам, ни поиску. Но заявку можно оставить по любой территории:
            расчёт и вызов замерщика работают везде, где мы обслуживаем.
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Не нашли свой адрес?';
$ctaText  = 'Напишите адрес в комментарии — посчитаем километраж и скажем, входит ли он в бесплатную зону выезда.';
include APP_ROOT . '/templates/partials/cta.php';
?>
