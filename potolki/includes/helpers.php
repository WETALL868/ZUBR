<?php

use Potolki\Seo\GeoQuality;
use Potolki\Seo\PageIndex;

/**
 * Помощники шаблонов: ссылки, перелинковка, география, форматирование.
 */

/** Наборы каталога: ключ набора => каталог URL. */
function catalog_sets(): array
{
    return [
        'ceilings' => '/ceilings/',
        'systems'  => '/systems/',
        'lighting' => '/lighting/',
        'rooms'    => '/rooms/',
        'services' => '/services/',
    ];
}

/**
 * Находит элемент каталога по slug в любом наборе.
 * @return array{set: string, slug: string, item: array, url: string}|null
 */
function find_item(string $slug): ?array
{
    foreach (catalog_sets() as $set => $dir) {
        $item = content_item($set, $slug);
        if ($item !== null && ($item['enabled'] ?? true) !== false) {
            return ['set' => $set, 'slug' => $slug, 'item' => $item, 'url' => $dir . $slug . '/'];
        }
    }

    // Отдельные страницы вне каталога — тоже допустимы в перелинковке.
    $standalone = [
        'measurement' => ['title' => 'Бесплатный замер', 'url' => '/measurement/'],
        'calculator'  => ['title' => 'Калькулятор',      'url' => '/calculator/'],
        'prices'      => ['title' => 'Цены',             'url' => '/prices/'],
        'portfolio'   => ['title' => 'Работы',           'url' => '/portfolio/'],
    ];

    if (isset($standalone[$slug])) {
        return ['set' => 'page', 'slug' => $slug, 'item' => $standalone[$slug], 'url' => $standalone[$slug]['url']];
    }

    return null;
}

/**
 * Превращает список slug-ов в ссылки. Несуществующие молча пропускаются:
 * перелинковка не должна ломать страницу.
 *
 * @return array<int, array{url: string, title: string, text: string}>
 */
function related_links(array $slugs): array
{
    $links = [];

    foreach ($slugs as $slug) {
        $found = find_item((string) $slug);
        if ($found === null) {
            continue;
        }
        $links[] = [
            'url'   => $found['url'],
            'title' => $found['item']['menu_title'] ?? $found['item']['title'],
            'text'  => mb_substr((string) ($found['item']['lead'] ?? ''), 0, 120),
        ];
    }

    return $links;
}

/** Ссылки на статьи блога и страницы вида 'ceilings/matovye'. */
function page_links(array $paths): array
{
    $links = [];

    foreach ($paths as $path) {
        $url = '/' . trim((string) $path, '/') . '/';
        $page = PageIndex::byUrl($url);
        if ($page === null || $page['index'] === false) {
            continue;
        }
        $links[] = ['url' => $url, 'title' => $page['title']];
    }

    return $links;
}

/** Статьи блога по slug-ам. */
function blog_links(array $slugs): array
{
    $links = [];

    foreach ($slugs as $slug) {
        $post = content_item('blog', (string) $slug);
        if ($post === null || ($post['status'] ?? 'published') !== 'published') {
            continue;
        }
        $links[] = ['url' => '/blog/' . $slug . '/', 'title' => $post['title'], 'lead' => $post['lead'] ?? ''];
    }

    return $links;
}

// ── География ────────────────────────────────────────────────────────────

function location(string $slug): ?array
{
    $locations = config('locations');
    return $locations[$slug] ?? null;
}

function location_url(string $slug): ?string
{
    $item = location($slug);
    return $item === null ? null : PageIndex::geoUrl($slug, $item);
}

/**
 * Территории заданного типа, при необходимости — только дочерние.
 * @return array<string, array>
 */
function locations_of(string $type, ?string $parent = null): array
{
    $result = [];

    foreach (config('locations') as $slug => $item) {
        if ($item['type'] !== $type) {
            continue;
        }
        if ($parent !== null && ($item['parent'] ?? null) !== $parent) {
            continue;
        }
        $result[$slug] = $item;
    }

    return $result;
}

/** Можно ли индексировать географическую страницу прямо сейчас. */
function location_indexable(array $item): bool
{
    return ($item['status'] ?? 'draft') === 'published'
        && ($item['index'] ?? false) === true
        && ($item['served'] ?? false) === true
        && GeoQuality::issues($item) === [];
}

/** Расстояние до территории в километрах для подстановки в калькулятор. */
function location_distance(array $item): int
{
    return (int) ($item['travel']['distance_km'] ?? 0);
}

// ── Форматирование ───────────────────────────────────────────────────────

function date_ru(?string $date): string
{
    if (!filled($date)) {
        return '';
    }

    $months = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    $timestamp = strtotime((string) $date);
    if ($timestamp === false) {
        return (string) $date;
    }

    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

/** Подстановка реквизитов в юридические тексты. */
function legal_placeholders(string $text): string
{
    $map = [
        '{operator}' => config('legal.operator_name') ?? '_____________________',
        '{inn}'      => config('legal.inn') ?? '__________',
        '{ogrn}'     => config('legal.ogrn') ?? '__________',
        '{address}'  => config('legal.legal_address') ?? '_____________________',
        '{email}'    => config('legal.privacy_email') ?? config('contacts.email') ?? '_____________',
        '{phone}'    => config('contacts.phone.display') ?? '',
        '{domain}'   => config('site.domain') ?? '',
    ];

    return strtr($text, $map);
}

/** Заполнены ли данные оператора: без них юридические страницы не публикуются. */
function legal_data_ready(): bool
{
    return filled(config('legal.operator_name'))
        && filled(config('legal.inn'))
        && filled(config('legal.legal_address'));
}

// ── Микроразметка FAQ ────────────────────────────────────────────────────

/**
 * Добавляет FAQPage в разметку. Вызывать только если вопросы реально
 * выведены на странице.
 *
 * @param array<int, array{0: string, 1: string}|array{q: string, a: string}> $faq
 */
function schema_faq(array $faq): void
{
    if ($faq === []) {
        return;
    }

    $items = [];

    foreach ($faq as $entry) {
        $question = $entry['q'] ?? ($entry[0] ?? null);
        $answer   = $entry['a'] ?? ($entry[1] ?? null);
        if (!filled($question) || !filled($answer)) {
            continue;
        }
        $items[] = [
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
        ];
    }

    if ($items !== []) {
        schema_add(['@type' => 'FAQPage', 'mainEntity' => $items]);
    }
}

/** Разметка услуги. */
function schema_service(string $name, string $description): void
{
    schema_add([
        '@type'       => 'Service',
        'name'        => $name,
        'description' => $description,
        'provider'    => ['@id' => absolute_url('/') . '#organization'],
        'areaServed'  => [
            ['@type' => 'City', 'name' => (string) config('geo.base_city')],
            ['@type' => 'AdministrativeArea', 'name' => (string) config('geo.region')],
        ],
    ]);
}

/** Нормализует FAQ к виду [[q, a], ...] независимо от формата в контенте. */
function faq_pairs(array $faq): array
{
    $pairs = [];

    foreach ($faq as $entry) {
        if (isset($entry['q'], $entry['a'])) {
            $pairs[] = ['q' => $entry['q'], 'a' => $entry['a']];
        } elseif (isset($entry[0], $entry[1])) {
            $pairs[] = ['q' => $entry[0], 'a' => $entry[1]];
        }
    }

    return $pairs;
}
