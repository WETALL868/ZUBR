<?php

declare(strict_types=1);

use Potolki\Seo\PageIndex;

/**
 * Ссылки и маршруты: каждой странице соответствует файл на диске,
 * а каждая внутренняя ссылка ведёт на существующую страницу.
 */

T::suite('Маршруты: физические файлы');

$missing = [];

foreach (PageIndex::all() as $page) {
    $relative = trim($page['url'], '/');
    $file = $relative === '' ? APP_ROOT . '/index.php' : APP_ROOT . '/' . $relative . '/index.php';

    if (!is_file($file)) {
        $missing[] = $page['url'];
    }
}

T::eq($missing, [], 'для каждой страницы реестра создан index.php (php tools/build-routes.php)');

$templates = [];
foreach (PageIndex::all() as $page) {
    $templates[$page['template']] = true;
}

$missingTemplates = [];
foreach (array_keys($templates) as $template) {
    if (!is_file(APP_ROOT . '/templates/' . $template . '.php')) {
        $missingTemplates[] = $template;
    }
}
T::eq($missingTemplates, [], 'все используемые шаблоны существуют');

T::suite('Ссылки: перелинковка каталога');

$broken = [];

foreach (['ceilings', 'systems', 'lighting', 'rooms', 'services'] as $set) {
    foreach (content($set) as $slug => $item) {
        foreach ((array) ($item['related'] ?? []) as $related) {
            if (find_item((string) $related) === null) {
                $broken[] = $set . '/' . $slug . ' → ' . $related;
            }
        }
    }
}

T::eq($broken, [], 'все ссылки «смотрите также» ведут на существующие страницы');

$brokenBlog = [];
foreach (content('blog') as $slug => $post) {
    foreach ((array) ($post['related'] ?? []) as $related) {
        if (content_item('blog', (string) $related) === null) {
            $brokenBlog[] = $slug . ' → blog/' . $related;
        }
    }
    foreach ((array) ($post['related_pages'] ?? []) as $path) {
        if (PageIndex::byUrl('/' . trim((string) $path, '/') . '/') === null) {
            $brokenBlog[] = $slug . ' → /' . $path . '/';
        }
    }
}
T::eq($brokenBlog, [], 'все ссылки из статей ведут на существующие страницы');

T::suite('Ссылки: пресеты калькулятора');

$prices = config('prices');
$badPresets = [];

foreach (['ceilings', 'systems', 'lighting', 'rooms', 'services'] as $set) {
    foreach (content($set) as $slug => $item) {
        foreach ((array) ($item['calc_preset'] ?? []) as $key => $value) {
            if ($key === 'canvas' && !isset($prices['canvas'][$value])) {
                $badPresets[] = $set . '/' . $slug . ': неизвестное полотно ' . $value;
            }
            if ($key === 'mounting' && !isset($prices['mounting'][$value])) {
                $badPresets[] = $set . '/' . $slug . ': неизвестная система примыкания ' . $value;
            }
        }
    }
}

T::eq($badPresets, [], 'пресеты калькулятора ссылаются на существующие позиции прайса');

T::suite('Ссылки: примеры расчёта в статьях');

$badExamples = [];

foreach (content('blog') as $slug => $post) {
    foreach ((array) ($post['calc_examples'] ?? []) as $example) {
        $result = Potolki\Calculator\Calculator::fromConfig()->calculate($example['input']);
        if (!$result['ok']) {
            $badExamples[] = $slug . ' → ' . $example['title'] . ': ' . implode(', ', $result['errors']);
        }
    }
}

T::eq($badExamples, [], 'все примеры расчёта в статьях считаются без ошибок');

T::suite('Файлы: ассеты и защита каталогов');

foreach (['css/main.css', 'js/app.js', 'js/calculator.js', 'fonts/fonts.css', 'icons/favicon.svg'] as $asset) {
    T::ok(is_file(APP_ROOT . '/assets/' . $asset), 'существует ассет ' . $asset);
}

$fonts = glob(APP_ROOT . '/assets/fonts/*.woff2') ?: [];
T::ok(count($fonts) >= 4, 'шрифты лежат локально (' . count($fonts) . ' файлов), без внешних CDN');

$css = (string) file_get_contents(APP_ROOT . '/assets/css/main.css');
T::ok(!str_contains($css, 'https://fonts.googleapis.com'), 'в CSS нет обращений к внешним шрифтовым сервисам');

foreach (['config', 'src', 'storage', 'tests', 'tools', 'includes', 'templates', 'content'] as $dir) {
    T::ok(is_file(APP_ROOT . '/' . $dir . '/.htaccess'), 'каталог /' . $dir . '/ закрыт от веб-доступа');
}

T::ok(is_file(APP_ROOT . '/robots.txt'), 'robots.txt на месте');
T::ok(is_file(APP_ROOT . '/sitemap.php'), 'генератор sitemap на месте');
T::ok(is_file(APP_ROOT . '/404.php'), 'обработчик 404 на месте');
T::ok(is_file(APP_ROOT . '/README_DEPLOY.md'), 'инструкция по установке на месте');
T::ok(is_file(APP_ROOT . '/DATA_REQUIRED.md'), 'список требуемых данных на месте');
T::ok(is_file(APP_ROOT . '/IMAGE_BRIEF.md'), 'бриф на изображения на месте');

T::suite('Цены: не дублируются в коде');

$jsFiles = glob(APP_ROOT . '/assets/js/*.js') ?: [];
$priceLeak = [];

foreach ($jsFiles as $file) {
    $body = (string) file_get_contents($file);
    // Ищем «подозрительные» тарифы: трёх-четырёхзначные числа рядом со словом «руб»
    if (preg_match('/(руб|₽)\s*[:=]\s*\d{2,}/u', $body) === 1) {
        $priceLeak[] = basename($file);
    }
}

T::eq($priceLeak, [], 'в JavaScript нет зашитых цен — расчёт приходит с сервера');

$templateLeak = [];
foreach (glob(APP_ROOT . '/templates/*.php') ?: [] as $file) {
    $body = (string) file_get_contents($file);
    if (preg_match('/\d{3,}\s*₽/u', $body) === 1) {
        $templateLeak[] = basename($file);
    }
}
T::eq($templateLeak, [], 'в шаблонах нет вручную вписанных цен');
