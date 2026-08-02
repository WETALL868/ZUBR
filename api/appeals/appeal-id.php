<?php
/**
 * Короткий номер обращения: одна заглавная латинская буква и пять цифр.
 *
 * Номер выдаётся посетителю вместо длинного внутреннего идентификатора,
 * поэтому он должен быть коротким, читаемым вслух по телефону
 * и при этом гарантированно неповторяющимся.
 */
declare(strict_types=1);

/**
 * Буквы без I и O: в коротком номере их путают с единицей и нулём.
 */
const APPEAL_ID_LETTERS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

/**
 * Формат номера, выдаваемого сейчас: N48271.
 */
const APPEAL_ID_PATTERN = '/^[A-Z]\d{5}$/';

/**
 * Формат номеров, выдававшихся прежними версиями сайта: НИ-20260802-A1B2C3D4.
 * Такие обращения уже сохранены на хостинге, и они должны продолжать
 * открываться в административном разделе.
 */
const APPEAL_ID_LEGACY_PATTERN = '/^НИ-\d{8}-[A-F0-9]{8}$/u';

/**
 * Проверяет, что строка — номер обращения (текущий или прежний формат).
 */
function is_appeal_id(string $value): bool
{
    return preg_match(APPEAL_ID_PATTERN, $value) === 1
        || preg_match(APPEAL_ID_LEGACY_PATTERN, $value) === 1;
}

/**
 * Собирает один случайный номер. Отдельная функция — чтобы её можно
 * было проверить тестом независимо от файловой системы.
 */
function build_appeal_id(): string
{
    $letter = APPEAL_ID_LETTERS[random_int(0, strlen(APPEAL_ID_LETTERS) - 1)];
    $digits = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);

    return $letter . $digits;
}

/**
 * Подбирает свободный номер и сразу занимает его файлом записи.
 *
 * Проверка уникальности и резервирование выполняются одной операцией:
 * fopen с режимом 'x' создаёт файл только если его ещё нет. Поэтому два
 * одновременных обращения не могут получить одинаковый номер — второму
 * вернётся false, и он продолжит подбор.
 *
 * @return array{0: string, 1: resource, 2: string}|null
 *         Номер, открытый дескриптор файла записи и путь к нему.
 */
function reserve_appeal_id(string $storageDir, int $attempts = 40): ?array
{
    for ($attempt = 0; $attempt < $attempts; $attempt++) {
        $id = build_appeal_id();
        $path = $storageDir . DIRECTORY_SEPARATOR . $id . '.record.php';

        $handle = @fopen($path, 'xb');
        if ($handle !== false) {
            return [$id, $handle, $path];
        }
        // Номер занят — пробуем следующий.
    }

    return null;
}
