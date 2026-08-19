<?php

declare(strict_types=1);

namespace Potolki\Seo;

/**
 * Проверка достаточности содержания географической страницы.
 *
 * Это защита от дорвеев, встроенная в код, а не «договорённость на словах».
 * Пока страница не проходит проверку, она:
 *   • отдаётся с noindex, follow;
 *   • не попадает в sitemap;
 *   • попадает в отчёт tools/url-table.php как требующая наполнения.
 *
 * Опубликовать пустую страницу «на всякий случай» технически невозможно.
 */
final class GeoQuality
{
    /** Минимальная длина собственного вступления, символов. */
    private const MIN_LEAD_LENGTH = 180;

    /** Минимум локальных вопросов. */
    private const MIN_FAQ = 2;

    /** Минимум фактов о застройке. */
    private const MIN_HOUSING = 2;

    /**
     * @return string[] список проблем; пустой массив — страницу можно индексировать
     */
    public static function issues(array $location): array
    {
        $issues = [];

        if (($location['served'] ?? false) !== true) {
            $issues[] = 'территория не обслуживается';
        }

        $lead = trim((string) ($location['seo']['lead'] ?? ''));
        if (mb_strlen($lead) < self::MIN_LEAD_LENGTH) {
            $issues[] = sprintf('вступление короче %d символов', self::MIN_LEAD_LENGTH);
        }

        if (self::looksTemplated($lead)) {
            $issues[] = 'вступление выглядит шаблонным (текст-заглушка)';
        }

        if (count((array) ($location['faq'] ?? [])) < self::MIN_FAQ) {
            $issues[] = sprintf('меньше %d локальных вопросов', self::MIN_FAQ);
        }

        if (count((array) ($location['housing'] ?? [])) < self::MIN_HOUSING) {
            $issues[] = sprintf('меньше %d фактов о застройке', self::MIN_HOUSING);
        }

        if (count((array) ($location['services'] ?? [])) < 3) {
            $issues[] = 'меньше трёх связанных услуг';
        }

        if (!filled($location['seo']['title'] ?? null) || !filled($location['seo']['description'] ?? null)) {
            $issues[] = 'не заполнены title или description';
        }

        return $issues;
    }

    public static function passes(array $location): bool
    {
        return self::issues($location) === [];
    }

    /**
     * Ищет признаки заглушки: текст «страница готовится», а также
     * дословные совпадения вступлений у разных территорий.
     */
    private static function looksTemplated(string $lead): bool
    {
        $markers = ['готовится', 'в проработке', 'заполните', 'lorem', 'текст будет'];

        $lower = mb_strtolower($lead);
        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Находит территории с одинаковым вступлением: верный признак того,
     * что текст размножили заменой топонима.
     *
     * @return array<string, string[]> хеш текста => slug-и
     */
    public static function duplicateLeads(array $locations): array
    {
        $byHash = [];

        foreach ($locations as $slug => $location) {
            if (($location['status'] ?? 'draft') !== 'published') {
                continue;
            }
            $lead = trim((string) ($location['seo']['lead'] ?? ''));
            if ($lead === '') {
                continue;
            }
            // Название территории убираем: иначе «уникальность» достигается
            // одной лишь подстановкой топонима.
            $normalized = str_replace(
                [(string) ($location['name'] ?? ''), (string) ($location['name_pred'] ?? ''), (string) ($location['name_rod'] ?? '')],
                '',
                $lead
            );
            $hash = md5(preg_replace('/\s+/u', ' ', mb_strtolower($normalized)) ?? '');
            $byHash[$hash][] = $slug;
        }

        return array_filter($byHash, static fn (array $slugs): bool => count($slugs) > 1);
    }
}
