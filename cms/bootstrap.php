<?php

/**
 * Точка входа CMS: конфигурация, подключение к базе, общие помощники.
 *
 * Подключается и публичной частью сайта, и административной панелью.
 * Работает и с MySQL/MariaDB, и с SQLite — драйвер задаётся в
 * config/database.php (пример: config/database.example.php).
 */

declare(strict_types=1);

define('CMS_ROOT', dirname(__DIR__));

require_once __DIR__ . '/schema.php';

/** Настройки из config/database.php. Без файла CMS не запускается. */
function cms_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $file = CMS_ROOT . '/config/database.php';
    if (!is_file($file)) {
        throw new RuntimeException(
            'Нет файла config/database.php. Скопируйте config/database.example.php '
            . 'в config/database.php и впишите доступы к базе.'
        );
    }

    $config = require $file;
    if (!is_array($config)) {
        throw new RuntimeException('config/database.php должен возвращать массив настроек.');
    }

    return $config;
}

/** Соединение с базой. Одно на запрос. */
function cms_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = cms_config();
    $driver = $c['driver'] ?? 'mysql';

    if ($driver === 'sqlite') {
        $path = $c['sqlite_path'] ?? (CMS_ROOT . '/storage/database.sqlite');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Внешние ключи и разумное поведение при параллельных запросах.
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['host'] ?? 'localhost',
        (int)($c['port'] ?? 3306),
        $c['database'] ?? '',
        $c['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, (string)($c['username'] ?? ''), (string)($c['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Настоящие подготовленные запросы вместо эмуляции: так СУБД сама
        // отделяет данные от кода, и SQL-инъекция через параметр невозможна.
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

/** Установлена ли CMS: есть конфиг и в базе есть таблица товаров. */
function cms_is_installed(): bool
{
    static $installed = null;
    if ($installed !== null) {
        return $installed;
    }

    if (!is_file(CMS_ROOT . '/config/database.php')) {
        return $installed = false;
    }

    try {
        cms_db()->query('SELECT 1 FROM products LIMIT 1');
        return $installed = true;
    } catch (Throwable) {
        return $installed = false;
    }
}

/* -------------------------------------------------------------- запросы */

function cms_query(string $sql, array $params = []): PDOStatement
{
    $stmt = cms_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function cms_all(string $sql, array $params = []): array
{
    return cms_query($sql, $params)->fetchAll();
}

function cms_one(string $sql, array $params = []): ?array
{
    $row = cms_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function cms_value(string $sql, array $params = [])
{
    $value = cms_query($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

function cms_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
        . implode(', ', array_map(static fn($c) => ':' . $c, $cols)) . ')';
    cms_query($sql, $data);
    return (int)cms_db()->lastInsertId();
}

function cms_update(string $table, array $data, string $where, array $params = []): int
{
    $set = implode(', ', array_map(static fn($c) => $c . ' = :' . $c, array_keys($data)));
    $stmt = cms_query('UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where, $data + $params);
    return $stmt->rowCount();
}

function cms_now(): string
{
    return date('Y-m-d H:i:s');
}

/* ------------------------------------------------------------- настройки */

/** Значение настройки: cms_setting('contacts', 'phone', '+7...'). */
function cms_setting(string $group, string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (cms_all('SELECT group_code, key_code, value FROM settings') as $row) {
                $cache[$row['group_code'] . '.' . $row['key_code']] = $row['value'];
            }
        } catch (Throwable) {
            $cache = [];
        }
    }

    return $cache[$group . '.' . $key] ?? $default;
}

function cms_setting_save(string $group, string $key, $value): void
{
    $exists = cms_value(
        'SELECT id FROM settings WHERE group_code = ? AND key_code = ?',
        [$group, $key]
    );

    if ($exists) {
        cms_update('settings', ['value' => (string)$value, 'updated_at' => cms_now()], 'id = :id', ['id' => $exists]);
        return;
    }

    cms_insert('settings', [
        'group_code' => $group,
        'key_code'   => $key,
        'value'      => (string)$value,
        'updated_at' => cms_now(),
    ]);
}

/* --------------------------------------------------------------- вывод */

/** Экранирование для HTML. Короткое имя, потому что встречается в шаблонах всюду. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Цена в формате сайта: «15 500 ₽», «15 500,50 ₽». Разделители неразрывные. */
function cms_money(?float $value): string
{
    if ($value === null) {
        return 'Цена по запросу';
    }

    $whole = abs($value - round($value)) < 0.005;
    $number = $whole
        ? number_format(round($value), 0, ',', "\u{00A0}")
        : number_format($value, 2, ',', "\u{00A0}");

    return $number . "\u{00A0}₽";
}

/** Число для микроразметки и фида: «15500» или «15500.50». */
function cms_money_machine(?float $value): string
{
    if ($value === null) {
        return '';
    }

    return abs($value - round($value)) < 0.005
        ? (string)(int)round($value)
        : number_format($value, 2, '.', '');
}

function cms_base_url(): string
{
    return rtrim((string)cms_setting('site', 'base_url', 'https://comp-uter.ru'), '/');
}

function cms_absolute_url(string $path): string
{
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }

    return cms_base_url() . '/' . ltrim($path, '/');
}

/** Запись в журнал изменений. */
function cms_audit(string $action, ?string $entity = null, ?int $entityId = null, ?string $summary = null): void
{
    try {
        cms_insert('audit_log', [
            'admin_id'    => $_SESSION['admin']['id'] ?? null,
            'admin_login' => $_SESSION['admin']['login'] ?? null,
            'action'      => $action,
            'entity'      => $entity,
            'entity_id'   => $entityId,
            'summary'     => $summary,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at'  => cms_now(),
        ]);
    } catch (Throwable) {
        // Журнал не должен ронять основную операцию.
    }
}
