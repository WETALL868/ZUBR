<?php

/**
 * Резервная копия базы.
 *
 * Копия делается средствами PHP, без mysqldump: на большинстве недорогих
 * хостингов запуск внешних программ запрещён, и «сделайте дамп в консоли»
 * там означает «резервной копии не будет».
 *
 * Получается обычный .sql, который можно залить обратно через phpMyAdmin
 * или командой mysql. Для SQLite копируется сам файл базы.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/** Куда складываются копии. Папка закрыта от загрузки по HTTP. */
function backup_dir(): string
{
    $dir = CMS_ROOT . '/storage/backups';

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $guard = $dir . '/.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "# В копии лежат заказы и контакты покупателей.\n"
            . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }

    return $dir;
}

/** Экранирование значения для SQL-дампа. */
function backup_quote(PDO $pdo, $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    return $pdo->quote((string)$value);
}

/**
 * Собирает дамп всех таблиц CMS.
 *
 * Пароли администраторов в дампе есть, но только хешами: восстановить из
 * них исходный пароль нельзя. Пароля базы и почты здесь нет вовсе — они
 * живут в файлах, а не в таблицах, именно ради этого.
 */
function backup_sql_dump(): string
{
    $pdo = cms_db();
    $driver = cms_config()['driver'] ?? 'mysql';

    $out = "-- Резервная копия Comp-Uter CMS\n"
        . '-- ' . date('d.m.Y H:i') . "\n"
        . "-- Восстановление: залейте этот файл в пустую базу\n"
        . "--   через phpMyAdmin (вкладка «Импорт») или командой\n"
        . "--   mysql -u ПОЛЬЗОВАТЕЛЬ -p БАЗА < этот-файл.sql\n\n";

    if ($driver !== 'sqlite') {
        $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
    }

    foreach (array_keys(cms_schema()) as $table) {
        try {
            $rows = cms_all('SELECT * FROM ' . $table);
        } catch (Throwable) {
            continue;   // таблицы может не быть в старой базе
        }

        $out .= "-- ------------------------------------------------- $table\n";
        $out .= 'DELETE FROM ' . $table . ";\n";

        if (!$rows) {
            $out .= "\n";
            continue;
        }

        $columns = array_keys($rows[0]);
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = backup_quote($pdo, $row[$column]);
            }
            $out .= 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES ('
                . implode(', ', $values) . ");\n";
        }
        $out .= "\n";
    }

    if ($driver !== 'sqlite') {
        $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    return $out;
}

/**
 * Записывает копию на диск.
 *
 * @return array{ok:bool,file?:string,size?:int,message?:string}
 */
function backup_create(): array
{
    $dir = backup_dir();

    if (!is_dir($dir) || !is_writable($dir)) {
        return ['ok' => false, 'message' => 'Папка storage/backups недоступна для записи.'];
    }

    // Схема нужна вместе с данными: без неё дамп не во что заливать.
    $schema = '';
    foreach (cms_schema_sql(cms_config()['driver'] ?? 'mysql') as $statement) {
        $schema .= $statement . ";\n\n";
    }

    $sql = $schema . backup_sql_dump();

    $name = 'backup-' . date('Y-m-d-His') . '.sql';
    if (@file_put_contents($dir . '/' . $name, $sql, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Не удалось записать файл копии.'];
    }

    backup_prune($dir);

    return ['ok' => true, 'file' => $name, 'size' => (int)filesize($dir . '/' . $name)];
}

/**
 * Оставляет десять последних копий.
 *
 * Без этого папка растёт, пока хостинг не упрётся в квоту, и тогда перестаёт
 * работать не только резервное копирование, но и загрузка фотографий.
 */
function backup_prune(string $dir, int $keep = 10): void
{
    $files = glob($dir . '/backup-*.sql') ?: [];
    if (count($files) <= $keep) {
        return;
    }

    usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, $keep) as $old) {
        @unlink($old);
    }
}

/** Список готовых копий, новые сверху. */
function backup_list(): array
{
    $files = glob(backup_dir() . '/backup-*.sql') ?: [];
    usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));

    return array_map(static fn($path) => [
        'name' => basename($path),
        'size' => (int)filesize($path),
        'time' => (int)filemtime($path),
    ], $files);
}
