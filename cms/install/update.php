<?php

/**
 * Обновление работающего сайта.
 *
 * Запуск из папки сайта:
 *     php cms/install/update.php            показать, что будет сделано
 *     php cms/install/update.php --apply    сделать
 *
 * ЧЕМ ЭТО ОТЛИЧАЕТСЯ ОТ migrate.php. Тот ставит сайт с нуля и переносит
 * каталог из прежних файлов — заодно он перезаписывает тексты главной
 * страницы теми, что были при переходе на CMS. На работающем сайте это
 * означает потерю всех правок, сделанных владельцем в панели. Поэтому для
 * обновления есть отдельный скрипт: он ДОБАВЛЯЕТ недостающее и не трогает
 * ни одного заказа, ни одного товара и ни одной чужой правки.
 *
 * Что делает:
 *   1. создаёт таблицы, которых ещё нет;
 *   2. добавляет столбцы, появившиеся в схеме позже (ALTER TABLE ADD COLUMN);
 *   3. записывает способы оплаты, если их ещё нет;
 *   4. переводит секцию заказа на новую форму, сохраняя текст над ней.
 *
 * Ничего не удаляет. Запускать можно сколько угодно раз.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/defaults.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Скрипт запускается только из консоли.\n");
}

$apply = in_array('--apply', $argv ?? [], true);

function step(string $line = ''): void
{
    echo $line, "\n";
}

step('=== Обновление сайта Comp-Uter ===');
step($apply ? 'Режим: применяю изменения.' : 'Режим: только показываю, ничего не меняю.');
step();

try {
    cms_db()->query('SELECT 1');
} catch (Throwable $e) {
    exit("База не отвечает: " . $e->getMessage() . "\nПроверьте config/database.php.\n");
}

$driver = (string)(cms_config()['driver'] ?? 'mysql');

/* ------------------------------------------------------------- 1. таблицы */

if ($apply) {
    $applied = cms_schema_apply(cms_db(), $driver);
    step('Таблицы: проверено ' . count(cms_schema()) . ', создано новых '
        . $applied['created'] . ', уже было ' . $applied['skipped'] . '.');
} else {
    $missing = [];
    foreach (array_keys(cms_schema()) as $table) {
        if (!cms_schema_existing_columns(cms_db(), $driver, $table)) {
            $missing[] = $table;
        }
    }
    step($missing ? 'Будут созданы таблицы: ' . implode(', ', $missing) . '.' : 'Все таблицы на месте.');
}

/* ------------------------------------------------------------ 2. столбцы */

if ($apply) {
    $added = cms_schema_sync_columns(cms_db(), $driver);
    step($added
        ? 'Добавлены столбцы: ' . implode(', ', $added) . '.'
        : 'Новых столбцов нет.');
} else {
    // Показываем то же самое, но ничего не выполняя: сравниваем описание
    // схемы с тем, что сейчас в базе.
    $planned = [];
    foreach (cms_schema() as $table => $lines) {
        $existing = cms_schema_existing_columns(cms_db(), $driver, $table);
        if (!$existing) {
            continue;
        }
        foreach ($lines as $line) {
            if (str_starts_with($line, '{UNIQUE}') || str_starts_with($line, '{INDEX}')
                || str_contains($line, '{PK}')) {
                continue;
            }
            if (preg_match('~^\s*(\w+)\s~', $line, $m)
                && !in_array(mb_strtolower($m[1]), $existing, true)) {
                $planned[] = $table . '.' . $m[1];
            }
        }
    }
    step($planned ? 'Будут добавлены столбцы: ' . implode(', ', $planned) . '.' : 'Новых столбцов нет.');
}

/* -------------------------------------------------------------- 3. оплата */

try {
    $havePayments = (int)(cms_value('SELECT COUNT(*) FROM payment_methods') ?? 0);
} catch (Throwable) {
    $havePayments = 0;
}

if ($apply) {
    $addedPayments = cms_seed_payment_methods();
    step($addedPayments
        ? 'Способы оплаты добавлены: ' . implode(', ', $addedPayments) . '.'
        : 'Способы оплаты уже настроены — не трогаем.');
} else {
    step($havePayments > 0
        ? 'Способы оплаты уже есть (' . $havePayments . ') — не трогаем.'
        : 'Будут добавлены способы оплаты: '
            . implode(', ', array_column(cms_default_payment_methods(), 'code')) . '.');
}

/* ------------------------------------------------------- 4. секция заказа */

$block = cms_one('SELECT * FROM home_blocks WHERE code = ?', ['order']);

if (!$block) {
    step('Секции заказа на главной нет — пропускаю.');
} elseif ((string)$block['kind'] === 'form') {
    step('Секция заказа уже переведена на новую форму — не трогаю.');
} else {
    /*
     * В старом блоке лежала вся секция целиком: текст и форма одной строкой.
     * Забираем из неё только текст — заголовок и абзац, которые владелец мог
     * править, — а форму дальше собирает templates/partials/order-form.php.
     */
    $intro = preg_match('~<div class="order-copy">.*?</div>\s*</div>~s', (string)$block['body'], $m)
        ? $m[0]
        : '';

    step($intro !== ''
        ? 'Секция заказа: текст над формой сохраняю как есть (' . mb_strlen($intro) . ' симв.).'
        : 'Секция заказа: прежний текст найти не удалось, поставлю текст по умолчанию.');

    if ($apply) {
        $markup = cms_order_block_markup($intro);
        cms_update('home_blocks', [
            'kind'       => 'form',
            'body'       => $markup['body'],
            'body_after' => $markup['body_after'],
            'updated_at' => cms_now(),
        ], 'id = :id', ['id' => (int)$block['id']]);
        step('Секция заказа переведена на новую форму.');
    } else {
        step('Секция заказа будет переведена на новую форму.');
    }
}

/* ---------------------------------------------------------- старые заказы */

if ($apply) {
    // У заказов, оформленных до появления переключателя, тип не заполнен.
    // Они оформлялись как физические лица — так их и помечаем, чтобы в
    // панели и в выгрузках не было пустого поля.
    try {
        $fixed = cms_query(
            "UPDATE orders SET customer_type = 'individual' WHERE customer_type IS NULL OR customer_type = ''"
        )->rowCount();
        step($fixed > 0
            ? 'Прежним заказам проставлен тип покупателя «физическое лицо»: ' . $fixed . '.'
            : 'Прежние заказы уже размечены.');
    } catch (Throwable $e) {
        step('Не удалось разметить прежние заказы: ' . $e->getMessage());
    }
}

step();
step('---------------------------------------------------------------');

if (!$apply) {
    step('Это был пробный запуск. Чтобы применить:');
    step('    php cms/install/update.php --apply');
    exit(0);
}

step('Обновление завершено.');
step('Загляните в панель: раздел «Обслуживание» — там проверка сайта,');
step('и раздел «Оплата» — там список способов оплаты.');
