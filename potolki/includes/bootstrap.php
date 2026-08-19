<?php
/**
 * Точка входа для всех страниц и API.
 *
 * Подключается первой строкой каждого index.php:
 *   $d = __DIR__; while (!is_file($d . '/includes/bootstrap.php') && $d !== '/') { $d = dirname($d); }
 *   require $d . '/includes/bootstrap.php';
 *
 * Ничего не выводит. Не требует Composer, базы данных и расширений
 * сверх стандартной сборки PHP 8.1.
 */

declare(strict_types=1);

if (defined('APP_ROOT')) {
    return;
}

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Требуется PHP 8.1 или новее.');
}

// ── Конфигурация ──────────────────────────────────────────────────────────

/**
 * Доступ к конфигурации через точечную нотацию: config('contacts.phone.display').
 */
function config(string $key = null, mixed $default = null): mixed
{
    static $data = null;

    if ($data === null) {
        $data = [
            'site'     => require APP_ROOT . '/config/site.php',
            'prices'   => require APP_ROOT . '/config/prices.php',
            'mail'     => require APP_ROOT . '/config/mail.php',
            'consent'  => require APP_ROOT . '/config/consent-versions.php',
            'locations'=> require APP_ROOT . '/config/locations.php',
        ];
        // site.php разворачивается в корень: config('brand.name') вместо config('site.brand.name')
        $data = array_merge($data, $data['site']);
    }

    if ($key === null) {
        return $data;
    }

    $value = $data;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

// ── Режим работы и обработка ошибок ───────────────────────────────────────

$isDev = config('runtime.env') === 'development';

ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');
error_reporting(E_ALL);
date_default_timezone_set((string) config('site.timezone', 'Europe/Moscow'));
setlocale(LC_ALL, 'ru_RU.UTF-8', 'ru_RU', 'Russian');
mb_internal_encoding('UTF-8');

set_exception_handler(static function (Throwable $e): void {
    app_log('error', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    if (config('runtime.env') === 'development') {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }
    http_response_code(500);
    // Посетитель не должен видеть техническую ошибку.
    if (is_file(APP_ROOT . '/templates/error-500.php')) {
        include APP_ROOT . '/templates/error-500.php';
    } else {
        echo 'Сервис временно недоступен. Позвоните нам, пожалуйста.';
    }
});

// ── Автозагрузка классов (PSR-4, без Composer) ────────────────────────────

spl_autoload_register(static function (string $class): void {
    $prefix = 'Potolki\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = APP_ROOT . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ── Сессия ────────────────────────────────────────────────────────────────

/**
 * Сессия нужна для CSRF-токена и сохранения UTM-меток.
 * Это технически необходимые cookies — они не требуют согласия,
 * но их назначение раскрыто в политике cookies.
 */
function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('psid');
    @session_start();
}

// ── Запрос ────────────────────────────────────────────────────────────────

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = (string) config('site.base_path', '');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim($path, '/') . ($path === '/' ? '' : '/');
}

function site_url(string $path = '/'): string
{
    $base = rtrim((string) config('site.base_path', ''), '/');
    if ($path === '' || $path === '/') {
        return $base . '/';
    }
    return $base . '/' . trim($path, '/') . '/';
}

function absolute_url(string $path = '/'): string
{
    $scheme = (string) config('site.scheme', 'https');
    $domain = (string) config('site.domain', '');
    return $scheme . '://' . $domain . site_url($path);
}

/**
 * Ссылка на файл ассетов с меткой времени — чтобы браузер не показывал
 * старый CSS после обновления сайта.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = APP_ROOT . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return rtrim((string) config('site.base_path', ''), '/') . '/assets/' . $path . '?v=' . $version;
}

// ── Вывод ─────────────────────────────────────────────────────────────────

/** Экранирование для HTML. Использовать ВСЕГДА при выводе данных. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Экранирование для вставки внутрь JS-строки/JSON. */
function ejs(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/** Денежный формат: 12 400 ₽ */
function money(float|int|null $value, bool $withCurrency = true): string
{
    if ($value === null) {
        return '—';
    }
    $formatted = number_format((float) $value, 0, ',', ' ');
    return $withCurrency ? $formatted . ' ₽' : $formatted;
}

/** Склонение: plural(3, 'светильник', 'светильника', 'светильников') */
function plural(int $count, string $one, string $few, string $many): string
{
    $mod100 = $count % 100;
    $mod10 = $count % 10;
    if ($mod100 >= 11 && $mod100 <= 14) {
        return $many;
    }
    if ($mod10 === 1) {
        return $one;
    }
    if ($mod10 >= 2 && $mod10 <= 4) {
        return $few;
    }
    return $many;
}

/** Значение заполнено владельцем? Пустые данные не выводим и не выдумываем. */
function filled(mixed $value): bool
{
    if (is_array($value)) {
        return $value !== [];
    }
    return $value !== null && $value !== '';
}

// ── Контент ───────────────────────────────────────────────────────────────

/**
 * Загрузка набора контента из /content: content('ceilings'), content('faq').
 */
function content(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = APP_ROOT . '/content/' . $name . '.php';
        $cache[$name] = is_file($file) ? (array) require $file : [];
    }
    return $cache[$name];
}

/** Один элемент каталога по slug. */
function content_item(string $name, string $slug): ?array
{
    $items = content($name);
    return $items[$slug] ?? null;
}

// ── SEO-состояние страницы ────────────────────────────────────────────────

function seo(array $data = []): array
{
    static $state = [
        'title'       => null,
        'description' => null,
        'h1'          => null,
        'canonical'   => null,
        'robots'      => 'index, follow',
        'og_image'    => null,
        'breadcrumbs' => [],
        'updated'     => null,
        'schema'      => [],
    ];

    if ($data !== []) {
        $state = array_merge($state, $data);
    }

    return $state;
}

function schema_add(array $node): void
{
    $state = seo();
    $state['schema'][] = $node;
    seo(['schema' => $state['schema']]);
}

// ── Рендеринг страницы ────────────────────────────────────────────────────

function render(string $template, array $params = []): void
{
    $file = APP_ROOT . '/templates/' . $template . '.php';

    if (!is_file($file)) {
        render_404();
    }

    extract($params, EXTR_SKIP);

    ob_start();
    include $file;
    $content = ob_get_clean();

    include APP_ROOT . '/templates/layout.php';
    exit;
}

function render_404(): never
{
    http_response_code(404);
    seo([
        'title'       => 'Страница не найдена — ' . config('brand.name'),
        'description' => 'Такой страницы на сайте нет. Воспользуйтесь разделами каталога или картой сайта.',
        'robots'      => 'noindex, follow',
        'breadcrumbs' => [],
    ]);
    ob_start();
    include APP_ROOT . '/templates/404.php';
    $content = ob_get_clean();
    include APP_ROOT . '/templates/layout.php';
    exit;
}

// ── Логирование ───────────────────────────────────────────────────────────

/**
 * Пишет техническое событие. Персональные данные сюда не попадают:
 * только тип формы, идентификатор заявки и технический результат.
 */
function app_log(string $level, string $message, array $context = []): void
{
    $levels = ['debug' => 10, 'info' => 20, 'warning' => 30, 'error' => 40];
    $min = $levels[(string) config('runtime.log_level', 'warning')] ?? 30;
    if (($levels[$level] ?? 40) < $min) {
        return;
    }

    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $line = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE)
    );

    @file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
}

// ── UTM-метки ─────────────────────────────────────────────────────────────

/**
 * Сохраняет метки источника в сессии, чтобы приложить их к заявке.
 * Хранятся ограниченное время (runtime.utm_lifetime_days) и не передаются
 * третьим лицам.
 */
function capture_utm(): void
{
    session_start_safe();
    $keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    $found = [];
    foreach ($keys as $key) {
        if (!empty($_GET[$key]) && is_string($_GET[$key])) {
            $found[$key] = mb_substr(preg_replace('/[^\w\-\.\s]/u', '', $_GET[$key]) ?? '', 0, 100);
        }
    }
    if ($found !== []) {
        $_SESSION['utm'] = $found + ['captured_at' => date('c')];
    }
    if (empty($_SESSION['referrer']) && !empty($_SERVER['HTTP_REFERER'])) {
        $host = parse_url((string) $_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if ($host && $host !== config('site.domain')) {
            $_SESSION['referrer'] = mb_substr((string) $_SERVER['HTTP_REFERER'], 0, 300);
        }
    }
}

// ── Диагностика конфигурации (только для владельца) ───────────────────────

/**
 * Показывает владельцу, каких данных не хватает. Включается двумя способами:
 *   • runtime.env = 'development';
 *   • ?diag=КЛЮЧ, если задан runtime.diag_key.
 * Посетителю ничего не показывается никогда.
 */
function diagnostics_enabled(): bool
{
    if (config('runtime.env') === 'development') {
        return true;
    }
    $key = config('runtime.diag_key');
    return filled($key) && isset($_GET['diag']) && hash_equals((string) $key, (string) $_GET['diag']);
}

function diagnostics_issues(): array
{
    $issues = [];

    $checks = [
        'contacts.phone.raw'    => 'Телефон не заменён на рабочий (config/site.php → contacts.phone)',
        'contacts.email'        => 'Не указан публичный email (config/site.php → contacts.email)',
        'contacts.email_leads'  => 'Не указан адрес получателя заявок (config/site.php → contacts.email_leads)',
        'site.domain'           => 'Не указан домен сайта (config/site.php → site.domain)',
        'legal.operator_name'   => 'Не заполнены реквизиты оператора персональных данных (config/site.php → legal)',
        'legal.inn'             => 'Не указан ИНН оператора — страница /legal/requisites/ выводит предупреждение',
    ];

    foreach ($checks as $key => $text) {
        if (!filled(config($key))) {
            $issues[] = $text;
        }
    }

    if (str_contains((string) config('site.domain'), 'example')) {
        $issues[] = 'Домен всё ещё демонстрационный: example-potolki.ru';
    }
    if (str_contains((string) config('contacts.phone.raw'), '0000000')) {
        $issues[] = 'Телефон всё ещё демонстрационный: +7 (000) 000-00-00';
    }
    if (config('mail.transport') === 'log') {
        $issues[] = 'Почта в режиме «log»: письма НЕ отправляются, а пишутся в storage/logs. Настройте SMTP в config/mail.php';
    }
    if (config('prices.approved_by_owner') !== true) {
        $issues[] = 'Прайс-лист не подтверждён владельцем (config/prices.php → approved_by_owner)';
    }
    if (config('features.analytics') && !filled(config('analytics.yandex_metrika_id'))) {
        $issues[] = 'Аналитика включена, но идентификатор Метрики не указан';
    }
    if (!filled(config('guarantee.canvas_years'))) {
        $issues[] = 'Не указаны гарантийные сроки — блоки о гарантии на сайте скрыты';
    }

    return $issues;
}

// ── Функции шаблонов ──────────────────────────────────────────────────────

require APP_ROOT . '/includes/helpers.php';
require APP_ROOT . '/includes/forms.php';

// ── Инициализация запроса ─────────────────────────────────────────────────

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header('X-Frame-Options: SAMEORIGIN');

    // Content-Security-Policy. Карта и аналитика подключаются только
    // при включённых модулях, поэтому источники добавляются условно.
    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        "style-src 'self' 'unsafe-inline'",
        "script-src 'self' 'unsafe-inline'",
        "connect-src 'self'",
        "object-src 'none'",
    ];
    if (config('features.analytics')) {
        $csp[2] = "form-action 'self'";
        $csp[] = "script-src-elem 'self' 'unsafe-inline' https://mc.yandex.ru https://mc.yandex.com";
        $csp[4] = "img-src 'self' data: https://mc.yandex.ru https://mc.yandex.com";
        $csp[8] = "connect-src 'self' https://mc.yandex.ru https://mc.yandex.com";
    }
    if (filled(config('contacts.map_embed'))) {
        $csp[] = "frame-src 'self' https://yandex.ru https://*.yandex.ru";
    }
    header('Content-Security-Policy: ' . implode('; ', $csp));

    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }

    capture_utm();
}
