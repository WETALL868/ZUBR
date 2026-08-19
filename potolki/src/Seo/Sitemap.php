<?php

declare(strict_types=1);

namespace Potolki\Seo;

/**
 * Генерация sitemap из реестра страниц.
 *
 * В карту попадают только страницы с index = true: черновики,
 * служебные страницы и территории, не прошедшие проверку качества,
 * исключаются автоматически.
 *
 * При большом количестве URL карта разбивается на части и отдаётся
 * индексом sitemap (sitemap.php?part=pages / geo / blog).
 */
final class Sitemap
{
    /** Порог, после которого карта делится на части. */
    private const SPLIT_THRESHOLD = 500;

    public static function parts(): array
    {
        return [
            'pages' => static fn (array $page): bool => !str_starts_with($page['type'], 'geo') && $page['type'] !== 'article',
            'geo'   => static fn (array $page): bool => str_starts_with($page['type'], 'geo'),
            'blog'  => static fn (array $page): bool => $page['type'] === 'article',
        ];
    }

    public static function needsSplit(): bool
    {
        return count(PageIndex::indexable()) > self::SPLIT_THRESHOLD;
    }

    /** XML одной части или всей карты сразу. */
    public static function urlset(?string $part = null): string
    {
        $pages = PageIndex::indexable();

        if ($part !== null && isset(self::parts()[$part])) {
            $pages = array_values(array_filter($pages, self::parts()[$part]));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $page) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e(absolute_url($page['url'])) . "</loc>\n";
            if (filled($page['updated'])) {
                $xml .= '    <lastmod>' . e(substr((string) $page['updated'], 0, 10)) . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . e($page['changefreq']) . "</changefreq>\n";
            $xml .= '    <priority>' . number_format((float) $page['priority'], 1, '.', '') . "</priority>\n";
            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>';
    }

    /** Индекс карт сайта, если частей несколько. */
    public static function index(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (array_keys(self::parts()) as $part) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . e(self::fileUrl('/sitemap.xml') . '?part=' . $part) . "</loc>\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        return $xml . '</sitemapindex>';
    }

    /**
     * Карта для демонстрационного режима: пустая, с пояснением внутри.
     * Так случайно отправленный в поисковую систему файл ничего не откроет.
     */
    public static function stagingUrlset(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<!-- Демонстрационная версия сайта. Индексация запрещена целиком: '
            . 'config/site.php → runtime.staging. Карта будет заполнена после переноса '
            . 'на рабочий домен. -->' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }

    /**
     * Содержимое robots.txt. В демонстрационном режиме — полный запрет,
     * в рабочем — обычные правила со ссылкой на карту сайта.
     */
    public static function robotsTxt(): string
    {
        $sitemap = self::fileUrl('/sitemap.xml');

        if (is_staging()) {
            return <<<TXT
# robots.txt — ДЕМОНСТРАЦИОННЫЙ РЕЖИМ
#
# Сайт закрыт от индексации целиком: config/site.php → runtime.staging = true.
# Дополнительно каждая страница отдаёт meta robots и заголовок X-Robots-Tag
# со значением noindex, nofollow.
#
# После переноса на рабочий домен, заполнения данных и ручной проверки:
#   1. поставьте runtime.staging = false;
#   2. выполните php tools/build-routes.php — файл перезапишется рабочими правилами.

User-agent: *
Disallow: /

TXT;
        }

        return <<<TXT
# robots.txt
# Файл создаётся командой php tools/build-routes.php по данным из config.

User-agent: *
Allow: /

# Служебные каталоги и точки API
Disallow: /api/
Disallow: /config/
Disallow: /src/
Disallow: /storage/
Disallow: /tests/
Disallow: /tools/
Disallow: /includes/
Disallow: /templates/
Disallow: /content/

# Служебные страницы и результаты отправки форм
Disallow: /thanks/
Disallow: /*?form=
Disallow: /*?diag=

# Параметры не создают новых страниц — исключаем дубли
Disallow: /*?utm_
Disallow: /*?part=
Clean-param: utm_source&utm_medium&utm_campaign&utm_content&utm_term&form&diag

Sitemap: {$sitemap}

TXT;
    }

    /** Абсолютный адрес файла в корне сайта (без завершающего слэша). */
    public static function fileUrl(string $file): string
    {
        return config('site.scheme') . '://' . config('site.domain')
            . rtrim((string) config('site.base_path', ''), '/') . '/' . ltrim($file, '/');
    }
}
