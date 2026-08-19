<?php

declare(strict_types=1);

use Potolki\Seo\GeoQuality;
use Potolki\Seo\PageIndex;

/**
 * География: защита от дорвеев.
 *
 * Главная проверка здесь — нельзя опубликовать территорию, у которой
 * нет собственного содержания. Если тест падает, значит кто-то поставил
 * status = 'published' до того, как написал текст.
 */

$locations = config('locations');

T::suite('География: структура');

T::ok(count($locations) > 100, 'справочник территорий заполнен (' . count($locations) . ')');

$missingParents = [];
foreach ($locations as $slug => $item) {
    $parent = $item['parent'] ?? null;
    if ($parent !== null && !isset($locations[$parent])) {
        $missingParents[] = $slug . ' → ' . $parent;
    }
}
T::eq($missingParents, [], 'у каждой территории существующая родительская территория');

$requiredFields = ['type', 'name', 'name_rod', 'name_pred', 'served', 'stage', 'status', 'index', 'travel', 'seo'];
$incomplete = [];
foreach ($locations as $slug => $item) {
    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $item)) {
            $incomplete[] = $slug . ': нет поля ' . $field;
        }
    }
}
T::eq($incomplete, [], 'у всех территорий заполнен обязательный набор полей');

$badUrls = [];
foreach ($locations as $slug => $item) {
    if (PageIndex::geoUrl($slug, $item) === null) {
        $badUrls[] = $slug;
    }
}
T::eq($badUrls, [], 'для каждой территории определён адрес страницы');

T::suite('География: качество опубликованных страниц');

$published = array_filter($locations, static fn (array $i): bool => ($i['status'] ?? 'draft') === 'published');
$problems = [];

foreach ($published as $slug => $item) {
    $issues = GeoQuality::issues($item);
    if ($issues !== []) {
        $problems[] = $slug . ': ' . implode(', ', $issues);
    }
}

T::eq($problems, [], 'все опубликованные территории прошли проверку содержания');
T::ok(count($published) > 5, 'опубликовано ' . count($published) . ' территорий первого этапа');

$duplicates = GeoQuality::duplicateLeads($locations);
T::eq($duplicates, [], 'нет двух территорий с одинаковым вступлением (после удаления топонима)');

T::suite('География: черновики закрыты от индексации');

$leaks = [];
foreach (PageIndex::all() as $page) {
    if (!str_starts_with($page['type'], 'geo-') || $page['type'] === 'geo-hub') {
        continue;
    }
    if ($page['index'] === true && ($page['status'] ?? 'draft') !== 'published') {
        $leaks[] = $page['url'];
    }
}
T::eq($leaks, [], 'ни одна черновая территория не открыта для индексации');

$drafts = array_filter($locations, static fn (array $i): bool => ($i['status'] ?? 'draft') !== 'published');
T::ok(count($drafts) > 0, 'этапы 2 и 3 остаются черновиками: ' . count($drafts) . ' территорий');

T::suite('География: этапы публикации');

$stages = [];
foreach ($locations as $item) {
    $stages[(int) $item['stage']] = ($stages[(int) $item['stage']] ?? 0) + 1;
}
ksort($stages);

foreach ($stages as $stage => $count) {
    T::ok($count > 0, sprintf('этап %d: %d территорий', $stage, $count));
}

$stage1Published = array_filter(
    $locations,
    static fn (array $i): bool => (int) $i['stage'] === 1 && ($i['status'] ?? '') === 'published'
);
T::ok(count($stage1Published) > 0, 'территории первого этапа опубликованы');

$laterPublished = array_filter(
    $locations,
    static fn (array $i): bool => (int) $i['stage'] > 1 && ($i['status'] ?? '') === 'published'
);
T::eq($laterPublished, [], 'территории следующих этапов не публикуются раньше времени');

T::suite('География: связность');

$noServices = [];
foreach ($published as $slug => $item) {
    if (count((array) ($item['services'] ?? [])) < 3) {
        $noServices[] = $slug;
    }
}
T::eq($noServices, [], 'у каждой опубликованной территории есть перелинковка с услугами');

$brokenServices = [];
foreach ($locations as $slug => $item) {
    foreach ((array) ($item['services'] ?? []) as $service) {
        if (find_item((string) $service) === null) {
            $brokenServices[] = $slug . ' → ' . $service;
        }
    }
}
T::eq($brokenServices, [], 'все связанные услуги территорий существуют');

$brokenNeighbors = [];
foreach ($locations as $slug => $item) {
    foreach ((array) ($item['neighbors'] ?? []) as $neighbor) {
        if (!isset($locations[$neighbor])) {
            $brokenNeighbors[] = $slug . ' → ' . $neighbor;
        }
    }
}
T::eq($brokenNeighbors, [], 'все соседние территории существуют');

T::suite('География: обещания в текстах');

/**
 * Тексты территорий не должны утверждать, что у компании есть
 * офис, склад или бригада «прямо здесь», если этого нет.
 */
$forbidden = ['наш офис в', 'мы находимся в', 'наш склад', 'приедем через час', 'бригада в вашем районе'];
$violations = [];

foreach ($locations as $slug => $item) {
    $text = mb_strtolower(json_encode($item['seo'], JSON_UNESCAPED_UNICODE) . json_encode($item['faq'] ?? [], JSON_UNESCAPED_UNICODE));
    foreach ($forbidden as $phrase) {
        if (str_contains($text, $phrase)) {
            $violations[] = $slug . ': «' . $phrase . '»';
        }
    }
}

T::eq($violations, [], 'в текстах территорий нет непроверяемых обещаний о присутствии');
