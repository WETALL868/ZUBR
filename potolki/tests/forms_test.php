<?php

declare(strict_types=1);

use Potolki\Forms\Lead;
use Potolki\Security\Csrf;
use Potolki\Security\RateLimit;
use Potolki\Security\Sanitize;

/**
 * Формы: нормализация ввода, защита и согласия.
 */

/**
 * Ограничение частоты рассчитано на живых посетителей (несколько заявок
 * за десять минут), поэтому перед каждой проверкой приёма заявки счётчик
 * сбрасывается — иначе тесты мешали бы сами себе.
 */
function test_reset_rate_limit(): void
{
    foreach (glob(APP_ROOT . '/storage/rate-limit/*/*.json') ?: [] as $file) {
        @unlink($file);
    }
}

test_reset_rate_limit();

T::suite('Формы: нормализация ввода');

T::eq(Sanitize::phone('8 (916) 123-45-67'), '+79161234567', 'телефон с восьмёркой приводится к +7');
T::eq(Sanitize::phone('+7 916 123 45 67'), '+79161234567', 'пробелы и плюс обрабатываются');
T::eq(Sanitize::phone('9161234567'), '+79161234567', 'десятизначный номер дополняется кодом страны');
T::eq(Sanitize::phone('123'), null, 'слишком короткий номер отклоняется');
T::eq(Sanitize::phone('не телефон'), null, 'текст вместо номера отклоняется');

T::eq(Sanitize::email('  Test@Example.RU '), 'Test@Example.RU', 'email очищается от пробелов');
T::eq(Sanitize::email("mail@example.ru\nBcc: hacker@evil.com"), null, 'перевод строки в email отклоняется');
T::eq(Sanitize::email('без-собаки'), null, 'некорректный email отклоняется');

T::eq(Sanitize::line("Иван\r\nBcc: spam@evil.com"), 'Иван Bcc: spam@evil.com', 'переводы строк в однострочном поле убираются');
T::ok(!str_contains(Sanitize::line('<script>alert(1)</script>Иван'), '<script'), 'HTML вырезается из имени');
T::ok(!str_contains(Sanitize::text('<b>Текст</b>'), '<b>'), 'HTML вырезается из комментария');
T::eq(mb_strlen(Sanitize::line(str_repeat('а', 500), 80)), 80, 'длина однострочного поля ограничивается');
T::eq(Sanitize::header("Тема\r\nX-Fake: 1"), 'ТемаX-Fake: 1', 'заголовок письма очищается от переводов строк');
T::eq(Sanitize::path('https://evil.com/path?x=1'), '/path', 'страница отправки приводится к внутреннему пути');
T::eq(Sanitize::phoneDisplay('+79161234567'), '+7 (916) 123-45-67', 'номер форматируется для показа');

T::suite('Формы: защита');

$token = Csrf::token();
T::ok(strlen($token) === 64, 'CSRF-токен нужной длины');
T::ok(Csrf::check($token), 'корректный токен принимается');
T::ok(!Csrf::check('подделка'), 'чужой токен отклоняется');
T::ok(!Csrf::check(null), 'отсутствие токена отклоняется');
T::ok(Csrf::token() === $token, 'токен стабилен в пределах сессии');

$limiter = new RateLimit(APP_ROOT . '/storage/rate-limit/test', 3, 600);
$identity = 'test-' . bin2hex(random_bytes(4));

T::ok($limiter->hit($identity), 'первая попытка разрешена');
T::ok($limiter->hit($identity), 'вторая попытка разрешена');
T::ok($limiter->hit($identity), 'третья попытка разрешена');
T::ok(!$limiter->hit($identity), 'четвёртая попытка отклонена лимитом');
T::ok($limiter->retryAfter($identity) > 0, 'известно, через сколько можно повторить');

T::suite('Формы: приём заявки');

/** Валидный набор полей формы. */
function test_lead_post(array $overrides = []): array
{
    return array_merge([
        'csrf_token'       => Csrf::token(),
        'form_time'        => (string) (time() - 10) . '.' . hash_hmac('sha256', (string) (time() - 10), Csrf::token()),
        'form'             => 'callback',
        'name'             => 'Иван',
        'phone'            => '+7 916 123-45-67',
        'page'             => '/contacts/',
        'consent_personal' => '1',
        'company'          => '',
    ], $overrides);
}

test_reset_rate_limit();
$result = (new Lead())->handle(test_lead_post());
T::ok($result['ok'], 'корректная заявка принимается', json_encode($result['errors'] ?? [], JSON_UNESCAPED_UNICODE));
T::ok(preg_match('/^\d{6}-[0-9A-F]{4}$/', (string) $result['lead_id']) === 1, 'заявке присвоен читаемый номер');

test_reset_rate_limit();
$noConsent = (new Lead())->handle(test_lead_post(['consent_personal' => null]));
T::ok(!$noConsent['ok'], 'без согласия на обработку данных заявка не принимается');
T::ok(isset($noConsent['errors']['consent_personal']), 'ошибка указывает на чекбокс согласия');

test_reset_rate_limit();
$noAds = (new Lead())->handle(test_lead_post(['consent_ads' => null]));
T::ok($noAds['ok'], 'отказ от рекламы НЕ мешает отправить заявку');

test_reset_rate_limit();
$noPhone = (new Lead())->handle(test_lead_post(['phone' => '']));
T::ok(!$noPhone['ok'], 'заявка без телефона отклоняется');
T::ok(isset($noPhone['errors']['phone']), 'ошибка привязана к полю телефона');

test_reset_rate_limit();
$badPhone = (new Lead())->handle(test_lead_post(['phone' => '12']));
T::ok(!$badPhone['ok'], 'некорректный телефон отклоняется');

test_reset_rate_limit();
$badCsrf = (new Lead())->handle(test_lead_post(['csrf_token' => str_repeat('a', 64)]));
T::ok(!$badCsrf['ok'], 'заявка с чужим CSRF-токеном отклоняется');
T::eq($badCsrf['status'], 419, 'на устаревшую форму отвечаем понятным статусом');

// Ловушки для ботов: отвечаем «успешно», но заявку не создаём.
test_reset_rate_limit();
$honeypot = (new Lead())->handle(test_lead_post(['company' => 'ООО Спам']));
T::ok($honeypot['ok'], 'бот получает нейтральный ответ');
T::eq($honeypot['lead_id'], date('ymd') . '-000000', 'заявка от бота не регистрируется');

test_reset_rate_limit();
$instant = (new Lead())->handle(test_lead_post([
    'form_time' => (string) time() . '.' . hash_hmac('sha256', (string) time(), Csrf::token()),
]));
T::eq($instant['lead_id'], date('ymd') . '-000000', 'мгновенная отправка формы считается ботом');

test_reset_rate_limit();
$forgedTime = (new Lead())->handle(test_lead_post(['form_time' => (string) (time() - 100) . '.подделка']));
T::eq($forgedTime['lead_id'], date('ymd') . '-000000', 'подделанная метка времени не проходит');

T::suite('Формы: фиксация согласий');

test_reset_rate_limit();
$withAds = (new Lead())->handle(test_lead_post(['consent_ads' => '1', 'email' => 'client@example.ru']));
T::ok($withAds['ok'], 'заявка с рекламным согласием принимается');

$consentConfig = config('consent');
T::ok(filled($consentConfig['personal_data']['version']), 'у согласия на обработку есть версия');
T::ok(filled($consentConfig['advertising']['version']), 'у рекламного согласия есть отдельная версия');
T::ok(
    $consentConfig['personal_data']['url'] !== $consentConfig['advertising']['url'],
    'согласия оформлены отдельными документами, а не одним'
);
T::ok($consentConfig['personal_data']['required'] === true, 'согласие на обработку обязательно');
T::ok($consentConfig['advertising']['required'] === false, 'рекламное согласие необязательно');

T::suite('Формы: разметка');

ob_start();
render_lead_form(['form' => 'measure', 'id' => 'test-form']);
$html = (string) ob_get_clean();

T::ok(str_contains($html, 'name="csrf_token"'), 'в форме есть CSRF-токен');
T::ok(str_contains($html, 'name="form_time"'), 'в форме есть метка времени');
T::ok(str_contains($html, 'class="hp-field"'), 'в форме есть ловушка для ботов');
T::ok(str_contains($html, 'name="consent_personal"'), 'есть чекбокс согласия на обработку');
T::ok(str_contains($html, 'name="consent_ads"'), 'есть отдельный чекбокс рекламного согласия');
T::ok(!str_contains($html, 'consent_personal" value="1" checked'), 'обязательное согласие не отмечено заранее');
T::ok(!str_contains($html, 'consent_ads" value="1" checked'), 'рекламное согласие не отмечено заранее');
T::ok(str_contains($html, 'нажимая') === false, 'согласие не подменяется формулировкой «нажимая кнопку»');
T::ok(str_contains($html, 'method="post"'), 'форма работает обычным POST без JavaScript');
T::ok(str_contains($html, 'aria-live="polite"'), 'статус отправки озвучивается ассистивными технологиями');
