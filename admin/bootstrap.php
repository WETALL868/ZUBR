<?php

/**
 * Ядро административной панели: сессия, вход, защита форм.
 *
 * Подключается каждым экраном панели. Публичная часть сайта этот файл не
 * трогает — она умеет только читать каталог.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/cms/repo.php';

/** Сколько минут бездействия до автоматического выхода. */
const ADMIN_IDLE_MINUTES = 120;

/** Сколько неудачных попыток входа разрешено и за какое время. */
const ADMIN_MAX_ATTEMPTS = 10;
const ADMIN_ATTEMPT_WINDOW_MINUTES = 15;

/* --------------------------------------------------------------- сессия */

function admin_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,          // куку не достать из JavaScript
        'secure'   => $https,        // по HTTP кука не уйдёт, если сайт на HTTPS
        'samesite' => 'Lax',         // куку не пришлют со стороннего сайта
    ]);
    session_name('computer_admin');
    session_start();
}

/* ------------------------------------------------------------ CSRF-токен */

function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function admin_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(admin_csrf_token()) . '" />';
}

/**
 * Проверка токена у POST-запроса.
 *
 * Без неё чужая страница могла бы отправить форму от имени вошедшего
 * администратора — например, удалить товар, пока он читает почту.
 */
function admin_check_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $sent = (string)($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(admin_csrf_token(), $sent)) {
        http_response_code(419);
        exit('Форма устарела. Обновите страницу и повторите.');
    }
}

/* ------------------------------------------------------------ сообщения */

function admin_flash(string $text, string $kind = 'ok'): void
{
    $_SESSION['flash'][] = ['text' => $text, 'kind' => $kind];
}

function admin_take_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/* ---------------------------------------------------------------- вход */

function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function admin_is_owner(): bool
{
    return (admin_user()['role'] ?? '') === 'owner';
}

/**
 * Требует вход. Всё, что нельзя показывать посторонним, начинается с этой
 * строки.
 */
function admin_require_login(): array
{
    admin_start_session();

    $user = admin_user();
    if (!$user) {
        admin_redirect_to_login();
    }

    // Бездействие: сессия не живёт вечно, даже если браузер не закрывали.
    $seen = (int)($_SESSION['seen_at'] ?? 0);
    if ($seen > 0 && time() - $seen > ADMIN_IDLE_MINUTES * 60) {
        admin_logout();
        admin_start_session();
        admin_flash('Вы вышли автоматически: страница была открыта слишком долго.', 'warn');
        admin_redirect_to_login();
    }
    $_SESSION['seen_at'] = time();

    // «Выйти со всех устройств»: у пользователя в базе растёт счётчик, и все
    // сессии со старым номером перестают действовать.
    $epoch = cms_value('SELECT session_epoch FROM admin_users WHERE id = ? AND is_active = 1', [(int)$user['id']]);
    if ($epoch === null || (int)$epoch !== (int)($user['epoch'] ?? -1)) {
        admin_logout();
        admin_start_session();
        admin_flash('Сессия завершена. Войдите заново.', 'warn');
        admin_redirect_to_login();
    }

    return $user;
}

function admin_require_owner(): void
{
    if (!admin_is_owner()) {
        http_response_code(403);
        exit('Недостаточно прав: раздел доступен только владельцу сайта.');
    }
}

function admin_redirect_to_login(): void
{
    $back = (string)($_SERVER['REQUEST_URI'] ?? '/admin/');
    header('Location: /admin/?p=login&back=' . rawurlencode($back));
    exit;
}

/** Сколько неудачных попыток входа было недавно с этого адреса или логина. */
function admin_recent_failures(string $login, string $ip): int
{
    $since = date('Y-m-d H:i:s', time() - ADMIN_ATTEMPT_WINDOW_MINUTES * 60);

    return (int)cms_value(
        'SELECT COUNT(*) FROM admin_login_attempts
          WHERE success = 0 AND created_at > ? AND (ip = ? OR login = ?)',
        [$since, $ip, $login]
    );
}

function admin_record_attempt(string $login, string $ip, bool $success): void
{
    cms_insert('admin_login_attempts', [
        'login'      => mb_substr($login, 0, 120),
        'ip'         => mb_substr($ip, 0, 45),
        'success'    => $success ? 1 : 0,
        'created_at' => cms_now(),
    ]);

    // Старые записи не нужны: журнал попыток не должен расти бесконечно.
    cms_query('DELETE FROM admin_login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400 * 7)]);
}

/**
 * Проверяет логин и пароль.
 *
 * @return array{ok:bool,message:string}
 */
function admin_attempt_login(string $login, string $password): array
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    if (admin_recent_failures($login, $ip) >= ADMIN_MAX_ATTEMPTS) {
        return ['ok' => false, 'message' => 'Слишком много попыток. Подождите ' . ADMIN_ATTEMPT_WINDOW_MINUTES . ' минут.'];
    }

    $user = cms_one('SELECT * FROM admin_users WHERE login = ? AND is_active = 1', [$login]);

    // password_verify сравнивает за постоянное время, а хеш-заглушка нужна,
    // чтобы несуществующий логин отвечал столько же, сколько существующий:
    // иначе по времени ответа можно перебрать логины.
    $hash = $user['password_hash'] ?? '$2y$12$0000000000000000000000000000000000000000000000000000';
    $ok = password_verify($password, $hash) && $user !== null;

    admin_record_attempt($login, $ip, $ok);

    if (!$ok) {
        return ['ok' => false, 'message' => 'Неверный логин или пароль.'];
    }

    // Пароль верный, но хеш устарел (сменился алгоритм или стоимость) —
    // пересчитываем прозрачно для владельца.
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        cms_update('admin_users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)],
            'id = :id', ['id' => (int)$user['id']]);
    }

    // Новый идентификатор сессии: старый, который мог подсмотреть кто-то до
    // входа, перестаёт что-либо значить.
    session_regenerate_id(true);

    $_SESSION['admin'] = [
        'id'    => (int)$user['id'],
        'login' => $user['login'],
        'name'  => $user['name'],
        'role'  => $user['role'],
        'epoch' => (int)$user['session_epoch'],
    ];
    $_SESSION['seen_at'] = time();

    cms_update('admin_users', [
        'last_login_at' => cms_now(),
        'last_login_ip' => mb_substr($ip, 0, 45),
    ], 'id = :id', ['id' => (int)$user['id']]);

    cms_audit('login', 'admin_user', (int)$user['id'], 'Вход в панель');

    return ['ok' => true, 'message' => ''];
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ------------------------------------------------------------- значения */

function admin_post(string $key, $default = null)
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

/** Число или NULL: пустое поле цены означает «цена по запросу», а не ноль. */
function admin_post_decimal(string $key): ?float
{
    $raw = (string)admin_post($key, '');
    $raw = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $raw);

    if ($raw === '') {
        return null;
    }

    return is_numeric($raw) ? (float)$raw : null;
}

function admin_post_int(string $key, int $default = 0): int
{
    $value = admin_post($key, '');

    return is_numeric($value) ? (int)$value : $default;
}

function admin_post_bool(string $key): int
{
    return !empty($_POST[$key]) ? 1 : 0;
}

/** Адрес страницы панели. */
function admin_url(string $page, array $params = []): string
{
    return '/admin/?' . http_build_query(['p' => $page] + $params);
}

function admin_redirect(string $page, array $params = []): void
{
    header('Location: ' . admin_url($page, $params));
    exit;
}

/**
 * Приводит текст к адресу: «Xeon E5-2690 V4» -> «xeon-e5-2690-v4».
 * Кириллица транслитерируется, чтобы адрес оставался читаемым.
 */
function admin_slugify(string $text): string
{
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
        'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
        'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];

    $text = mb_strtolower(trim($text));
    $text = strtr($text, $map);
    $text = preg_replace('~[^a-z0-9]+~u', '-', $text) ?? '';

    return trim($text, '-');
}

/** Уникальный адрес: если такой уже занят, добавляем -2, -3 и т.д. */
function admin_unique_slug(string $table, string $slug, ?int $exceptId = null): string
{
    $slug = $slug !== '' ? $slug : 'item';
    $base = $slug;
    $n = 1;

    while (true) {
        $sql = 'SELECT id FROM ' . $table . ' WHERE slug = ?';
        $params = [$slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        if (cms_value($sql, $params) === null) {
            return $slug;
        }
        $slug = $base . '-' . (++$n);
    }
}
