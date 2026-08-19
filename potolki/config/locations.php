<?php
/**
 * Единый источник географических страниц сайта.
 *
 * Файл собирает территории из четырёх наборов данных. Ключ массива — slug,
 * он же последний сегмент URL.
 *
 * ПРАВИЛО, КОТОРОЕ НЕЛЬЗЯ НАРУШАТЬ:
 * добавление территории сюда НЕ публикует страницу автоматически.
 * Страница попадает в sitemap и получает index,follow только если
 *   status === 'published'  И  index === true  И  контент прошёл проверку
 *   достаточности (см. src/Seo/GeoQuality.php и tests/geo_quality_test.php).
 * Иначе страница отдаётся с noindex,follow и исключается из sitemap.
 *
 * Поля территории:
 *   type        'city' | 'okrug' | 'rajon' | 'mo_city' | 'metro' | 'region'
 *   name        именительный падеж («Химки»)
 *   name_rod    родительный («Химок») — для конструкций «в центре ...»
 *   name_pred   предложный с предлогом («в Химках») — основной рабочий падеж
 *   parent      slug родительской территории
 *   served      обслуживается ли фактически (false — страницу не публикуем)
 *   stage       1 | 2 | 3 — очередь публикации
 *   status      'published' | 'draft'
 *   index       true | false — разрешена ли индексация
 *   travel      условия выезда: зона, расстояние, ориентировочный срок
 *   housing     особенности застройки (общие, без выдуманных ЖК и адресов)
 *   services    приоритетные услуги для этой территории
 *   neighbors   соседние территории для перелинковки
 *   cases       реальные кейсы (пока пусто — заполняется владельцем)
 *   seo         title, description, h1, lead
 *   faq         локальные вопросы и ответы
 *
 * Расстояния и сроки выезда помечены как предварительные:
 * проверьте их по реальным маршрутам, см. DATA_REQUIRED.md.
 */

$locations = [];

foreach (['moscow', 'okruga', 'rajony', 'mo-goroda', 'metro'] as $set) {
    $file = __DIR__ . '/locations/' . $set . '.php';
    if (is_file($file)) {
        /** @var array $part */
        $part = require $file;
        $locations = array_merge($locations, $part);
    }
}

return $locations;
