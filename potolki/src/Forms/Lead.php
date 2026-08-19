<?php

declare(strict_types=1);

namespace Potolki\Forms;

use Potolki\Calculator\Calculator;
use Potolki\Mail\Mailer;
use Potolki\Mail\Templates;
use Potolki\Security\Csrf;
use Potolki\Security\RateLimit;
use Potolki\Security\Sanitize;

/**
 * Приём заявки: проверка, сборка данных, отправка писем.
 *
 * Порядок проверок специально такой: сначала дешёвые (метод запроса,
 * ловушки для ботов), потом CSRF, потом частота, и только в конце —
 * работа с данными и почтой.
 */
final class Lead
{
    /** Типы форм: ключ => [название для письма, какие поля обязательны] */
    public const FORMS = [
        'measure'  => ['title' => 'Вызов замерщика',        'require' => ['phone']],
        'callback' => ['title' => 'Обратный звонок',        'require' => ['phone']],
        'calc'     => ['title' => 'Расчёт стоимости',       'require' => ['phone']],
        'consult'  => ['title' => 'Консультация',           'require' => ['phone']],
        'question' => ['title' => 'Вопрос специалисту',     'require' => ['phone']],
        'case'     => ['title' => 'Заявка по работе',       'require' => ['phone']],
        'estimate' => ['title' => 'Смета на почту',         'require' => ['email']],
    ];

    /** Минимальное время заполнения формы, секунд (защита от ботов). */
    private const MIN_FILL_SECONDS = 3;

    /** Максимальный возраст открытой формы, секунд. */
    private const MAX_FORM_AGE = 86400;

    private array $errors = [];

    public function handle(array $post): array
    {
        // ── 1. Ловушки для ботов ──────────────────────────────────────────
        // Скрытое поле, которое человек не видит и не заполняет.
        if (trim((string) ($post['company'] ?? '')) !== '') {
            app_log('info', 'lead.honeypot');
            // Боту отвечаем «успешно», чтобы он не подбирал обход.
            return $this->fakeSuccess();
        }

        if (!$this->checkTimeTrap($post['form_time'] ?? '')) {
            app_log('info', 'lead.timetrap');
            return $this->fakeSuccess();
        }

        // ── 2. CSRF ───────────────────────────────────────────────────────
        if (!Csrf::check(is_string($post['csrf_token'] ?? null) ? $post['csrf_token'] : null)) {
            return $this->failure(['form' => 'Форма устарела. Обновите страницу и попробуйте ещё раз.'], 419);
        }

        // ── 3. Частота отправки ───────────────────────────────────────────
        $limiter = RateLimit::default('lead');
        $identity = RateLimit::identity();

        if (!$limiter->hit($identity)) {
            $minutes = (int) ceil($limiter->retryAfter($identity) / 60);
            return $this->failure([
                'form' => sprintf(
                    'Слишком много заявок подряд. Попробуйте через %d %s или позвоните нам.',
                    max(1, $minutes),
                    plural(max(1, $minutes), 'минуту', 'минуты', 'минут')
                ),
            ], 429);
        }

        // ── 4. Тип формы ──────────────────────────────────────────────────
        $formKey = Sanitize::choice($post['form'] ?? null, array_keys(self::FORMS), 'callback');
        $form = self::FORMS[$formKey];

        // ── 5. Данные ─────────────────────────────────────────────────────
        $name    = Sanitize::line($post['name'] ?? '', 80);
        $phoneRaw= $post['phone'] ?? '';
        $phone   = Sanitize::phone($phoneRaw);
        $email   = Sanitize::email($post['email'] ?? null);
        $comment = Sanitize::text($post['comment'] ?? '', 1500);
        $city    = Sanitize::line($post['city'] ?? '', 80);
        $contactWay = Sanitize::choice($post['contact_way'] ?? null, ['phone', 'whatsapp', 'telegram', 'email'], 'phone');
        $page    = Sanitize::path($post['page'] ?? ($_SERVER['HTTP_REFERER'] ?? '/'));

        if (in_array('phone', $form['require'], true) && $phone === null) {
            $this->errors['phone'] = trim((string) $phoneRaw) === ''
                ? 'Укажите телефон — по нему мы свяжемся с вами.'
                : 'Проверьте номер телефона: нужно 10 цифр после кода страны.';
        }

        if (in_array('email', $form['require'], true) && $email === null) {
            $this->errors['email'] = 'Укажите email, чтобы мы отправили расчёт.';
        }

        if ($name !== '' && mb_strlen($name) < 2) {
            $this->errors['name'] = 'Слишком короткое имя.';
        }

        // ── 6. Согласия ───────────────────────────────────────────────────
        $consents = $this->consents($post);

        if (!$consents['personal_data']['given']) {
            $this->errors['consent_personal'] = 'Без согласия на обработку персональных данных мы не сможем принять заявку.';
        }

        if ($this->errors !== []) {
            return $this->failure($this->errors, 422);
        }

        // ── 7. Расчёт (пересчитывается на сервере) ────────────────────────
        $estimate = null;
        if (!empty($post['calc']) && is_array($post['calc'])) {
            $estimate = Calculator::fromConfig()->calculate($post['calc']);
            if ($estimate['ok'] === false) {
                // Ошибочный расчёт не должен блокировать заявку: менеджер посчитает вручную.
                $estimate = null;
            }
        }

        // ── 8. Сборка заявки ──────────────────────────────────────────────
        $lead = [
            'id'         => $this->generateId(),
            'created_at' => date('d.m.Y H:i'),
            'form_key'   => $formKey,
            'form_title' => $form['title'],
            'name'       => $name !== '' ? $name : null,
            'phone'      => $phone,
            'phone_display' => $phone !== null ? Sanitize::phoneDisplay($phone) : null,
            'email'      => $email,
            'comment'    => $comment !== '' ? $comment : null,
            'city'       => $city !== '' ? $city : null,
            'contact_way'=> $contactWay,
            'page'       => $page,
            'utm'        => $_SESSION['utm'] ?? [],
            'referrer'   => $_SESSION['referrer'] ?? null,
            'consents'   => $consents,
            'estimate'   => $estimate,
            'price_version' => (string) config('prices.version'),
        ];

        // ── 9. Письма ─────────────────────────────────────────────────────
        $mailer = Mailer::fromConfig();
        $templates = new Templates();

        $ownerResult = $mailer->send([
            'to'       => (array) config('mail.to', []),
            'bcc'      => (array) config('mail.bcc', []),
            'subject'  => strtr((string) config('mail.subjects.owner'), ['{id}' => $lead['id'], '{form}' => $form['title']]),
            'html'     => $templates->ownerHtml($lead),
            'text'     => $templates->ownerText($lead),
            'reply_to' => $email,
            'context'  => ['lead_id' => $lead['id']],
        ]);

        $clientResult = null;
        if ($email !== null && config('mail.client_copy')) {
            $clientResult = $mailer->send([
                'to'      => [$email],
                'subject' => strtr((string) config('mail.subjects.client'), ['{id}' => $lead['id']]),
                'html'    => $templates->clientHtml($lead),
                'text'    => $templates->clientText($lead),
                'context' => ['lead_id' => $lead['id']],
            ]);
        }

        app_log('info', 'lead.received', [
            'id'        => $lead['id'],
            'form'      => $formKey,
            'page'      => $page,
            'owner_sent'=> $ownerResult['sent'],
            'queued'    => $ownerResult['queued'],
            'client_sent' => $clientResult['sent'] ?? null,
            'ads_consent' => $consents['advertising']['given'],
        ]);

        // Заявка принята, даже если письмо ушло в очередь: клиент не виноват
        // в проблемах почтового сервера, а данные не потеряны.
        $accepted = $ownerResult['sent'] || $ownerResult['queued'];

        if (!$accepted) {
            return $this->failure([
                'form' => 'Не удалось отправить заявку. Пожалуйста, позвоните нам — мы примем её по телефону.',
            ], 500);
        }

        return [
            'ok'      => true,
            'status'  => 200,
            'lead_id' => $lead['id'],
            'message' => 'Заявка принята. Мы свяжемся с вами в рабочее время.',
            'client_email_sent' => (bool) ($clientResult['sent'] ?? false),
        ];
    }

    // ── Вспомогательное ───────────────────────────────────────────────────

    /**
     * Фиксация согласий: что именно, какой версии и когда получено.
     * Эти данные нужны, чтобы позже подтвердить правомерность обработки.
     */
    private function consents(array $post): array
    {
        $config = config('consent');
        $now = date('c');

        $personalGiven = in_array($post['consent_personal'] ?? null, ['1', 'on', 'true', 1, true], true);
        $adsGiven = in_array($post['consent_ads'] ?? null, ['1', 'on', 'true', 1, true], true);

        return [
            'personal_data' => [
                'given'   => $personalGiven,
                'version' => (string) $config['personal_data']['version'],
                'date'    => (string) $config['personal_data']['date'],
                'text'    => (string) $config['personal_data']['checkbox'],
                'given_at'=> $personalGiven ? $now : null,
            ],
            'advertising' => [
                'given'   => $adsGiven,
                'version' => (string) $config['advertising']['version'],
                'date'    => (string) $config['advertising']['date'],
                'text'    => (string) $config['advertising']['checkbox'],
                'given_at'=> $adsGiven ? $now : null,
            ],
            'technical' => [
                // Раскрыто в политике: что именно мы фиксируем вместе с согласием.
                'submitted_at' => $now,
                'page'         => Sanitize::path($post['page'] ?? '/'),
                'form'         => Sanitize::line($post['form'] ?? '', 30),
                'user_agent'   => Sanitize::line($_SERVER['HTTP_USER_AGENT'] ?? '', 200),
                'ip_hash'      => substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . config('site.domain')), 0, 32),
            ],
        ];
    }

    /**
     * Подписанная метка времени открытия формы.
     * Бот, отправляющий форму мгновенно, отсеивается.
     */
    private function checkTimeTrap(mixed $value): bool
    {
        if (!is_string($value) || !str_contains($value, '.')) {
            return false;
        }

        [$timestamp, $signature] = explode('.', $value, 2);

        if (!ctype_digit($timestamp)) {
            return false;
        }

        if (!hash_equals(self::signTime((int) $timestamp), $signature)) {
            return false;
        }

        $age = time() - (int) $timestamp;

        return $age >= self::MIN_FILL_SECONDS && $age <= self::MAX_FORM_AGE;
    }

    public static function timeField(): string
    {
        $timestamp = time();
        return $timestamp . '.' . self::signTime($timestamp);
    }

    private static function signTime(int $timestamp): string
    {
        return hash_hmac('sha256', (string) $timestamp, Csrf::token());
    }

    /** Идентификатор заявки: понятен человеку и не раскрывает счётчик продаж. */
    private function generateId(): string
    {
        return date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }

    private function failure(array $errors, int $status): array
    {
        return ['ok' => false, 'status' => $status, 'errors' => $errors];
    }

    /** Ответ для бота: выглядит как успех, но ничего не отправляет. */
    private function fakeSuccess(): array
    {
        return [
            'ok'      => true,
            'status'  => 200,
            'lead_id' => date('ymd') . '-000000',
            'message' => 'Заявка принята. Мы свяжемся с вами в рабочее время.',
            'client_email_sent' => false,
        ];
    }
}
