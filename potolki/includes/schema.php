<?php
/**
 * Микроразметка JSON-LD.
 *
 * Правило: размечаем только то, что реально показано на странице
 * и заполнено владельцем. Пустые реквизиты, выдуманные рейтинги
 * и несуществующие отзывы в разметку не попадают — за это прилетают
 * ручные санкции, а пользы ноль.
 */

$state = seo();
$nodes = [];

// ── Организация ───────────────────────────────────────────────────────
$organization = [
    '@type' => filled(config('contacts.office_address')) ? 'LocalBusiness' : 'Organization',
    '@id'   => absolute_url('/') . '#organization',
    'name'  => (string) config('brand.name'),
    'url'   => absolute_url('/'),
    'description' => (string) config('brand.positioning'),
];

if (filled(config('contacts.phone.raw')) && !str_contains((string) config('contacts.phone.raw'), '0000000')) {
    $organization['telephone'] = (string) config('contacts.phone.raw');
}

if (filled(config('contacts.email'))) {
    $organization['email'] = (string) config('contacts.email');
}

if (filled(config('contacts.office_address'))) {
    $organization['address'] = [
        '@type'           => 'PostalAddress',
        'streetAddress'   => (string) config('contacts.office_address'),
        'addressLocality' => (string) config('geo.base_city'),
        'addressCountry'  => 'RU',
    ];

    if (filled(config('contacts.coords'))) {
        $organization['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => config('contacts.coords.lat'),
            'longitude' => config('contacts.coords.lon'),
        ];
    }

    foreach ((array) config('contacts.work_hours_schema') as $spec) {
        $organization['openingHoursSpecification'][] = [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => $spec['days'],
            'opens'     => $spec['open'],
            'closes'    => $spec['close'],
        ];
    }
}

// Зона обслуживания — реальная, из конфигурации.
$organization['areaServed'] = [
    ['@type' => 'City',  'name' => (string) config('geo.base_city')],
    ['@type' => 'AdministrativeArea', 'name' => (string) config('geo.region')],
];

if (filled(config('legal.operator_name')) && filled(config('legal.inn'))) {
    $organization['legalName'] = (string) config('legal.operator_name');
    $organization['taxID'] = (string) config('legal.inn');
}

$nodes[] = $organization;

// ── Хлебные крошки ────────────────────────────────────────────────────
if (!empty($state['breadcrumbs'])) {
    $items = [];
    $position = 1;

    foreach ($state['breadcrumbs'] as $crumb) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $crumb['title'],
            'item'     => absolute_url($crumb['url'] ?? '/'),
        ];
    }

    $nodes[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

// ── Дополнительные узлы, добавленные шаблоном (Service, FAQPage, Article)
foreach ((array) $state['schema'] as $node) {
    $nodes[] = $node;
}

$graph = ['@context' => 'https://schema.org', '@graph' => $nodes];
?>
<script type="application/ld+json"><?= json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
