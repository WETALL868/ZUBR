<?php
/**
 * HTML-карта сайта.
 *
 * Здесь допустима полная иерархия: это единственное место, где уместно
 * показать все страницы разом, включая те, что не попали в меню.
 */

use Potolki\Seo\PageIndex;

seo([
    'title'       => 'Карта сайта — ' . config('brand.name'),
    'description' => 'Полная структура сайта: услуги, виды потолков, конструкции, освещение, помещения, материалы, география и документы.',
    'h1'          => 'Карта сайта',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Карта сайта', 'url' => '/sitemap/'],
    ],
]);

$pages = PageIndex::all();

/** @return array<int, array> страницы по типам */
$byType = static function (array $types) use ($pages): array {
    return array_values(array_filter($pages, static fn (array $p): bool => in_array($p['type'], $types, true)));
};

$groups = [
    'Основные страницы' => $byType(['home', 'prices', 'tool', 'page', 'hub']),
    'Услуги'            => $byType(['service']),
    'Виды полотен'      => $byType(['ceiling']),
    'Конструкции'       => $byType(['system']),
    'Освещение'         => $byType(['lighting']),
    'Помещения'         => $byType(['room']),
    'Материалы'         => $byType(['article']),
    'Работы'            => $byType(['case']),
    'Документы'         => $byType(['legal']),
];

$geoGroups = [
    'Москва и округа'          => $byType(['geo-city', 'geo-okrug']),
    'Районы Москвы'            => $byType(['geo-rajon']),
    'Московская область'       => $byType(['geo-region', 'geo-mo_city']),
    'Станции метро'            => $byType(['geo-metro']),
];
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Структура</div>
                <h1>Карта сайта</h1>
            </div>
            <p class="lead">
                Всего страниц: <?= count($pages) ?>, из них открыты для индексации: <?= count(PageIndex::indexable()) ?>.
                Страницы, отмеченные серым, готовятся к публикации и закрыты от поиска.
            </p>
        </div>

        <div class="geo-groups">
            <?php foreach ($groups as $title => $items): ?>
                <?php if ($items === []) { continue; } ?>
                <div>
                    <h2 class="geo-group__title"><?= e((string) $title) ?></h2>
                    <ul class="geo-list">
                        <?php foreach ($items as $page): ?>
                            <li>
                                <a href="<?= e(site_url($page['url'])) ?>" <?= $page['index'] ? '' : 'data-draft="1"' ?>>
                                    <?= e($page['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight section--warm">
    <div class="container">
        <div class="section-head">
            <h2>География</h2>
            <p class="lead">Территории, по которым созданы страницы. Серым отмечены те, что ещё наполняются.</p>
        </div>

        <div class="geo-groups">
            <?php foreach ($geoGroups as $title => $items): ?>
                <?php if ($items === []) { continue; } ?>
                <div>
                    <h3 class="geo-group__title"><?= e((string) $title) ?></h3>
                    <ul class="geo-list">
                        <?php foreach ($items as $page): ?>
                            <li>
                                <a href="<?= e(site_url($page['url'])) ?>" <?= $page['index'] ? '' : 'data-draft="1"' ?>>
                                    <?= e($page['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top:2rem">
            <a class="link-arrow" href="<?= e(rtrim((string) config('site.base_path'), '/') . '/sitemap.xml') ?>">XML-карта для поисковых систем</a>
        </p>
    </div>
</section>
