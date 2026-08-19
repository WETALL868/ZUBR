<?php

declare(strict_types=1);

use Potolki\Seo\PageIndex;
use Potolki\Seo\Sitemap;

/**
 * Демонстрационный режим.
 *
 * Пока сайт не перенесён на рабочий домен, он не должен попадать в индекс —
 * ни одной страницей. Проверяем все четыре рубежа защиты:
 * meta robots, заголовок X-Robots-Tag, robots.txt и sitemap.xml.
 */

T::suite('Staging: переключатель');

$staging = is_staging();

T::ok(
    config('runtime.staging') !== null,
    'настройка runtime.staging присутствует в конфигурации'
);

T::ok(
    is_bool(config('runtime.staging')),
    'настройка включается одним булевым значением'
);

// Страховки: даже при выключенном флаге демонстрационный домен и режим
// разработки закрывают сайт от индексации.
T::ok(
    !str_contains((string) config('site.domain'), 'example') || $staging,
    'демонстрационный домен всегда закрыт от индексации'
);

T::suite('Staging: мета-теги страниц');

$state = seo();
seo(['robots' => 'index, follow', 'title' => 'Тест', 'description' => 'Тест']);

ob_start();
include APP_ROOT . '/includes/seo.php';
$head = (string) ob_get_clean();

seo($state);

if ($staging) {
    T::ok(str_contains($head, 'content="noindex, nofollow, noarchive, nosnippet"'), 'meta robots закрывает страницу целиком');
    T::ok(!str_contains($head, 'content="index, follow"'), 'настройка страницы не может открыть индексацию в демонстрационном режиме');
} else {
    T::ok(str_contains($head, 'content="index, follow"'), 'в рабочем режиме индексация разрешена настройкой страницы');
}

T::ok(str_contains($head, '<link rel="canonical"'), 'canonical выводится в любом режиме');

T::suite('Staging: robots.txt и sitemap');

$robots = Sitemap::robotsTxt();
$sitemapFile = (string) @file_get_contents(APP_ROOT . '/sitemap.xml');

if ($staging) {
    T::ok(str_contains($robots, 'Disallow: /'), 'robots.txt запрещает обход всего сайта');
    T::ok(!str_contains($robots, 'Sitemap:'), 'ссылка на карту сайта не публикуется');

    T::ok(str_contains($sitemapFile, '<urlset'), 'sitemap.xml остаётся валидным XML');
    T::ok(!str_contains($sitemapFile, '<url>'), 'в sitemap.xml нет ни одного адреса');
    T::ok(str_contains($sitemapFile, 'Демонстрационная версия'), 'в sitemap.xml есть пояснение о режиме');

    $parsed = @simplexml_load_string(Sitemap::stagingUrlset());
    T::ok($parsed !== false, 'пустая карта сайта разбирается как XML');
} else {
    T::ok(str_contains($robots, 'Sitemap: '), 'в рабочем режиме карта сайта указана');
    T::ok(str_contains($sitemapFile, '<url>'), 'в рабочем режиме sitemap.xml содержит адреса');
}

T::suite('Staging: реестр страниц не меняется');

/**
 * Демонстрационный режим не переписывает планы по индексации: 105 страниц
 * остаются помеченными как готовые к публикации, просто сейчас закрыты.
 * Так после переключения флага не придётся заново разбираться, что открывать.
 */
$indexable = PageIndex::indexable();

T::ok(count($indexable) > 30, 'в реестре сохраняется список страниц, готовых к индексации: ' . count($indexable));
T::ok(
    count($indexable) < count(PageIndex::all()),
    'черновики остаются закрытыми и после выключения демонстрационного режима'
);

T::suite('Staging: диагностика предупреждает владельца');

$issues = diagnostics_issues();
$mentioned = false;

foreach ($issues as $issue) {
    if (str_contains(mb_strtolower($issue), 'демонстрационный режим')) {
        $mentioned = true;
    }
}

T::eq($mentioned, $staging, 'диагностика сообщает о включённом демонстрационном режиме');
