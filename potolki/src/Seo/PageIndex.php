<?php

declare(strict_types=1);

namespace Potolki\Seo;

/**
 * Реестр всех страниц сайта — единственный источник правды об URL.
 *
 * Отсюда берут данные:
 *   • tools/build-routes.php — создаёт физические каталоги с index.php;
 *   • sitemap.php            — собирает XML только из индексируемых страниц;
 *   • /sitemap/              — HTML-карта сайта;
 *   • tests/links_test.php   — проверяет, что каждому URL соответствует файл;
 *   • tools/url-table.php    — выгружает таблицу URL и мета-тегов.
 *
 * Благодаря этому невозможна ситуация «страница есть в sitemap, но её нет
 * на сайте» или «страница есть, но о ней никто не знает».
 */
final class PageIndex
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        static $pages = null;

        if ($pages !== null) {
            return $pages;
        }

        $pages = array_merge(
            self::staticPages(),
            self::catalogPages(),
            self::blogPages(),
            self::portfolioPages(),
            self::geoPages(),
            self::legalPages(),
        );

        return $pages;
    }

    /** Только те страницы, которые разрешено индексировать. */
    public static function indexable(): array
    {
        return array_values(array_filter(self::all(), static fn (array $p): bool => $p['index'] === true));
    }

    public static function byUrl(string $url): ?array
    {
        foreach (self::all() as $page) {
            if ($page['url'] === $url) {
                return $page;
            }
        }
        return null;
    }

    /** @return array<int, array<string, mixed>> страницы одного типа */
    public static function ofType(string $type): array
    {
        return array_values(array_filter(self::all(), static fn (array $p): bool => $p['type'] === $type));
    }

    // ── Наборы страниц ────────────────────────────────────────────────────

    private static function staticPages(): array
    {
        $pages = [
            ['url' => '/',             'template' => 'home',        'type' => 'home',    'priority' => 1.0, 'changefreq' => 'weekly',  'title' => 'Главная'],
            ['url' => '/prices/',      'template' => 'prices',      'type' => 'prices',  'priority' => 0.9, 'changefreq' => 'weekly',  'title' => 'Цены'],
            ['url' => '/calculator/',  'template' => 'calculator',  'type' => 'tool',    'priority' => 0.9, 'changefreq' => 'monthly', 'title' => 'Калькулятор'],
            ['url' => '/measurement/', 'template' => 'measurement', 'type' => 'service', 'priority' => 0.8, 'changefreq' => 'monthly', 'title' => 'Бесплатный замер'],
            ['url' => '/services/',    'template' => 'hub',         'type' => 'hub',     'priority' => 0.7, 'changefreq' => 'monthly', 'title' => 'Услуги',            'params' => ['set' => 'services']],
            ['url' => '/ceilings/',    'template' => 'hub',         'type' => 'hub',     'priority' => 0.8, 'changefreq' => 'monthly', 'title' => 'Виды полотен',      'params' => ['set' => 'ceilings']],
            ['url' => '/systems/',     'template' => 'hub',         'type' => 'hub',     'priority' => 0.8, 'changefreq' => 'monthly', 'title' => 'Конструкции',       'params' => ['set' => 'systems']],
            ['url' => '/lighting/',    'template' => 'hub',         'type' => 'hub',     'priority' => 0.8, 'changefreq' => 'monthly', 'title' => 'Освещение',         'params' => ['set' => 'lighting']],
            ['url' => '/rooms/',       'template' => 'hub',         'type' => 'hub',     'priority' => 0.7, 'changefreq' => 'monthly', 'title' => 'Решения по помещениям', 'params' => ['set' => 'rooms']],
            ['url' => '/portfolio/',   'template' => 'portfolio',   'type' => 'hub',     'priority' => 0.7, 'changefreq' => 'weekly',  'title' => 'Работы'],
            ['url' => '/blog/',        'template' => 'blog-index',  'type' => 'hub',     'priority' => 0.6, 'changefreq' => 'weekly',  'title' => 'Полезные материалы'],
            ['url' => '/about/',       'template' => 'about',       'type' => 'page',    'priority' => 0.5, 'changefreq' => 'yearly',  'title' => 'О компании'],
            ['url' => '/contacts/',    'template' => 'contacts',    'type' => 'page',    'priority' => 0.8, 'changefreq' => 'monthly', 'title' => 'Контакты'],
            ['url' => '/faq/',         'template' => 'faq',         'type' => 'page',    'priority' => 0.6, 'changefreq' => 'monthly', 'title' => 'Вопросы и ответы'],
            ['url' => '/guarantees/',  'template' => 'guarantees',  'type' => 'page',    'priority' => 0.5, 'changefreq' => 'yearly',  'title' => 'Гарантии и документы'],
            ['url' => '/payment/',     'template' => 'payment',     'type' => 'page',    'priority' => 0.5, 'changefreq' => 'yearly',  'title' => 'Оплата'],
            ['url' => '/geography/',   'template' => 'geo-hub-all', 'type' => 'hub',     'priority' => 0.6, 'changefreq' => 'monthly', 'title' => 'Где мы работаем'],
            ['url' => '/sitemap/',     'template' => 'sitemap-html','type' => 'page',    'priority' => 0.3, 'changefreq' => 'weekly',  'title' => 'Карта сайта'],
        ];

        // Модульные страницы: если модуль выключен, страницы просто нет.
        if (config('features.reviews')) {
            $pages[] = ['url' => '/reviews/', 'template' => 'reviews', 'type' => 'page', 'priority' => 0.5, 'changefreq' => 'monthly', 'title' => 'Отзывы'];
        }
        if (config('features.promotions')) {
            $pages[] = ['url' => '/promotions/', 'template' => 'promotions', 'type' => 'page', 'priority' => 0.5, 'changefreq' => 'weekly', 'title' => 'Акции'];
        }

        // Служебная страница благодарности — существует, но не индексируется.
        $pages[] = ['url' => '/thanks/', 'template' => 'thanks', 'type' => 'service-page', 'priority' => 0.1, 'changefreq' => 'yearly', 'title' => 'Заявка принята', 'index' => false];

        return array_map(static fn (array $p): array => self::normalize($p), $pages);
    }

    private static function catalogPages(): array
    {
        $sets = [
            'services' => ['dir' => '/services/', 'template' => 'service', 'type' => 'service', 'priority' => 0.8],
            'ceilings' => ['dir' => '/ceilings/', 'template' => 'catalog-item', 'type' => 'ceiling', 'priority' => 0.9],
            'systems'  => ['dir' => '/systems/',  'template' => 'catalog-item', 'type' => 'system',  'priority' => 0.9],
            'lighting' => ['dir' => '/lighting/', 'template' => 'catalog-item', 'type' => 'lighting','priority' => 0.8],
            'rooms'    => ['dir' => '/rooms/',    'template' => 'catalog-item', 'type' => 'room',    'priority' => 0.7],
        ];

        $pages = [];

        foreach ($sets as $set => $meta) {
            foreach (content($set) as $slug => $item) {
                if (($item['enabled'] ?? true) === false) {
                    continue;
                }
                $pages[] = self::normalize([
                    'url'        => $meta['dir'] . $slug . '/',
                    'template'   => $meta['template'],
                    'type'       => $meta['type'],
                    'priority'   => $meta['priority'],
                    'changefreq' => 'monthly',
                    'title'      => $item['title'] ?? $slug,
                    'params'     => ['set' => $set, 'slug' => $slug],
                    'index'      => ($item['index'] ?? true) === true,
                ]);
            }
        }

        return $pages;
    }

    private static function blogPages(): array
    {
        $pages = [];

        foreach (content('blog') as $slug => $post) {
            $pages[] = self::normalize([
                'url'        => '/blog/' . $slug . '/',
                'template'   => 'blog-post',
                'type'       => 'article',
                'priority'   => 0.5,
                'changefreq' => 'yearly',
                'title'      => $post['title'] ?? $slug,
                'params'     => ['slug' => $slug],
                'index'      => ($post['status'] ?? 'published') === 'published',
                'updated'    => $post['updated'] ?? null,
            ]);
        }

        return $pages;
    }

    private static function portfolioPages(): array
    {
        $pages = [];

        foreach (content('portfolio') as $slug => $case) {
            $pages[] = self::normalize([
                'url'        => '/portfolio/' . $slug . '/',
                'template'   => 'portfolio-case',
                'type'       => 'case',
                'priority'   => 0.5,
                'changefreq' => 'yearly',
                'title'      => $case['title'] ?? $slug,
                'params'     => ['slug' => $slug],
                'index'      => ($case['status'] ?? 'published') === 'published',
            ]);
        }

        return $pages;
    }

    private static function geoPages(): array
    {
        // Хабы-списки. Сами территории добавляются ниже из config/locations.php,
        // чтобы правила индексации были едиными для всех страниц.
        $pages = [
            self::normalize(['url' => '/moskva/okruga/', 'template' => 'geo-hub', 'type' => 'geo-hub', 'priority' => 0.6, 'changefreq' => 'monthly', 'title' => 'Округа Москвы', 'params' => ['group' => 'okrug', 'parent' => 'moskva']]),
            self::normalize(['url' => '/moskva/rajony/', 'template' => 'geo-hub', 'type' => 'geo-hub', 'priority' => 0.5, 'changefreq' => 'monthly', 'title' => 'Районы Москвы', 'params' => ['group' => 'rajon', 'parent' => 'moskva']]),
            self::normalize(['url' => '/moskovskaya-oblast/goroda/', 'template' => 'geo-hub', 'type' => 'geo-hub', 'priority' => 0.6, 'changefreq' => 'monthly', 'title' => 'Города Московской области', 'params' => ['group' => 'mo_city', 'parent' => 'moskovskaya-oblast']]),
        ];

        $metroEnabled = (bool) config('geo.metro_enabled') && (bool) config('features.metro_pages');

        if ($metroEnabled) {
            $pages[] = self::normalize(['url' => '/metro/', 'template' => 'geo-hub', 'type' => 'geo-hub', 'priority' => 0.4, 'changefreq' => 'monthly', 'title' => 'Станции метро', 'params' => ['group' => 'metro']]);
        }

        // Москва и область уже добавлены выше как хабы верхнего уровня.
        $existing = array_column($pages, 'url');

        foreach (config('locations') as $slug => $location) {
            $url = self::geoUrl($slug, $location);

            if ($url === null || in_array($url, $existing, true)) {
                continue;
            }
            if ($location['type'] === 'metro' && !$metroEnabled) {
                continue;
            }

            // Индексация возможна только у опубликованной территории,
            // которая обслуживается и прошла проверку качества контента.
            $index = ($location['status'] ?? 'draft') === 'published'
                && ($location['index'] ?? false) === true
                && ($location['served'] ?? false) === true
                && GeoQuality::issues($location) === [];

            $pages[] = self::normalize([
                'url'        => $url,
                'template'   => 'geo',
                'type'       => 'geo-' . $location['type'],
                'priority'   => match ($location['type']) {
                    'city', 'region' => 0.9,
                    'mo_city' => 0.7,
                    default   => 0.6,
                },
                'changefreq' => 'monthly',
                'title'      => $location['seo']['h1'] ?? $location['name'],
                'params'     => ['slug' => $slug],
                'index'      => $index,
                'stage'      => $location['stage'] ?? null,
                'status'     => $location['status'] ?? 'draft',
            ]);
        }

        return $pages;
    }

    public static function geoUrl(string $slug, array $location): ?string
    {
        return match ($location['type']) {
            'city'    => '/moskva/',
            'region'  => '/moskovskaya-oblast/',
            'okrug'   => '/moskva/okruga/' . $slug . '/',
            'rajon'   => '/moskva/rajony/' . $slug . '/',
            'mo_city' => '/moskovskaya-oblast/goroda/' . $slug . '/',
            'metro'   => '/metro/' . substr($slug, strlen('metro-')) . '/',
            default   => null,
        };
    }

    private static function legalPages(): array
    {
        $pages = [];

        // Пока не заполнены реквизиты оператора, документы отдаются с noindex
        // (см. templates/legal.php) — значит, и в sitemap их быть не должно.
        $ready = legal_data_ready();

        foreach (content('legal') as $slug => $doc) {
            $pages[] = self::normalize([
                'url'        => '/legal/' . $slug . '/',
                'template'   => 'legal',
                'type'       => 'legal',
                'priority'   => 0.3,
                'changefreq' => 'yearly',
                'title'      => $doc['title'] ?? $slug,
                'params'     => ['slug' => $slug],
                'index'      => ($doc['index'] ?? true) === true && $ready,
                'status'     => $ready ? 'published' : 'draft',
            ]);
        }

        return $pages;
    }

    private static function normalize(array $page): array
    {
        return array_merge([
            'url'        => '/',
            'template'   => 'page',
            'type'       => 'page',
            'title'      => '',
            'params'     => [],
            'index'      => true,
            'priority'   => 0.5,
            'changefreq' => 'monthly',
            'updated'    => null,
            'status'     => 'published',
            'stage'      => null,
        ], $page);
    }
}
