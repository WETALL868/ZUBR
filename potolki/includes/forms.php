<?php

use Potolki\Forms\Lead;
use Potolki\Security\Csrf;

/**
 * Формы заявок.
 *
 * Одна функция на все формы сайта: так согласия, защита и разметка
 * не разъезжаются между страницами.
 *
 * Форма работает без JavaScript: это обычный POST на /api/submit-lead.php
 * с последующим redirect. С JavaScript та же форма отправляется фоном.
 */
function render_lead_form(array $options = []): void
{
    $defaults = [
        'form'     => 'callback',
        'id'       => null,
        'title'    => null,
        'text'     => null,
        'button'   => 'Отправить заявку',
        'fields'   => ['name', 'phone', 'comment'],
        'city'     => null,     // подставляется на географических страницах
        'with_calc'=> false,
        'class'    => '',
    ];

    $o = array_merge($defaults, $options);
    $id = $o['id'] ?? ('form-' . $o['form'] . '-' . substr(md5(uniqid('', true)), 0, 6));

    $consent = config('consent');

    // Данные предыдущей неудачной отправки (режим без JavaScript).
    session_start_safe();
    $errors = $_SESSION['lead_errors'] ?? [];
    $old = $_SESSION['lead_old'] ?? [];
    unset($_SESSION['lead_errors'], $_SESSION['lead_old']);

    $fieldError = static function (string $field) use ($errors): string {
        return isset($errors[$field]) ? (string) $errors[$field] : '';
    };
    ?>
    <form class="form <?= e($o['class']) ?>"
          id="<?= e($id) ?>"
          action="<?= e(site_url('/api/submit-lead.php')) ?>"
          method="post"
          data-ajax
          data-form="<?= e($o['form']) ?>"
          <?= $o['with_calc'] ? 'data-with-calc="1"' : '' ?>
          novalidate>

        <?php if (filled($o['title'])): ?>
            <h3><?= e((string) $o['title']) ?></h3>
        <?php endif; ?>

        <?php if (filled($o['text'])): ?>
            <p class="lead"><?= e((string) $o['text']) ?></p>
        <?php endif; ?>

        <?= Csrf::field() ?>
        <input type="hidden" name="form" value="<?= e($o['form']) ?>">
        <input type="hidden" name="form_time" value="<?= e(Lead::timeField()) ?>">
        <input type="hidden" name="page" value="<?= e(request_path()) ?>">
        <?php if (filled($o['city'])): ?>
            <input type="hidden" name="city" value="<?= e((string) $o['city']) ?>">
        <?php endif; ?>
        <?php if ($o['with_calc']): ?>
            <input type="hidden" name="calc_json" value="">
        <?php endif; ?>

        <?php /* Поле-ловушка для ботов: человек его не видит и не заполняет. */ ?>
        <div class="hp-field" aria-hidden="true">
            <label for="<?= e($id) ?>-company">Организация</label>
            <input type="text" id="<?= e($id) ?>-company" name="company" tabindex="-1" autocomplete="off">
        </div>

        <div class="form__row">
            <?php if (in_array('name', $o['fields'], true)): ?>
                <div class="field">
                    <label for="<?= e($id) ?>-name">Как к вам обращаться</label>
                    <input type="text" id="<?= e($id) ?>-name" name="name" autocomplete="name"
                           maxlength="80" placeholder="Имя"
                           value="<?= e((string) ($old['name'] ?? '')) ?>"
                           <?= $fieldError('name') !== '' ? 'aria-invalid="true"' : '' ?>>
                    <span class="field__error" data-error-for="name"><?= e($fieldError('name')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (in_array('phone', $o['fields'], true)): ?>
                <div class="field">
                    <label for="<?= e($id) ?>-phone">Телефон <span aria-hidden="true">*</span><span class="visually-hidden">обязательное поле</span></label>
                    <input type="tel" id="<?= e($id) ?>-phone" name="phone" required autocomplete="tel"
                           inputmode="tel" maxlength="25" placeholder="+7 (___) ___-__-__"
                           value="<?= e((string) ($old['phone'] ?? '')) ?>"
                           <?= $fieldError('phone') !== '' ? 'aria-invalid="true"' : '' ?>>
                    <span class="field__error" data-error-for="phone"><?= e($fieldError('phone')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (in_array('email', $o['fields'], true)): ?>
                <div class="field">
                    <label for="<?= e($id) ?>-email">Email<?= in_array('email_required', $o['fields'], true) ? ' *' : ' (если нужен расчёт письмом)' ?></label>
                    <input type="email" id="<?= e($id) ?>-email" name="email" autocomplete="email"
                           maxlength="190" placeholder="mail@example.ru"
                           value="<?= e((string) ($old['email'] ?? '')) ?>"
                           <?= $fieldError('email') !== '' ? 'aria-invalid="true"' : '' ?>>
                    <span class="field__error" data-error-for="email"><?= e($fieldError('email')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (in_array('contact_way', $o['fields'], true)): ?>
                <div class="field">
                    <label for="<?= e($id) ?>-way">Как удобнее связаться</label>
                    <select id="<?= e($id) ?>-way" name="contact_way">
                        <option value="phone">Позвонить</option>
                        <?php if (filled(config('contacts.whatsapp'))): ?><option value="whatsapp">WhatsApp</option><?php endif; ?>
                        <?php if (filled(config('contacts.telegram'))): ?><option value="telegram">Telegram</option><?php endif; ?>
                        <option value="email">Написать на почту</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <?php if (in_array('comment', $o['fields'], true)): ?>
            <div class="field">
                <label for="<?= e($id) ?>-comment">Комментарий</label>
                <textarea id="<?= e($id) ?>-comment" name="comment" maxlength="1500"
                          placeholder="Площадь, тип помещения, пожелания по свету"><?= e((string) ($old['comment'] ?? '')) ?></textarea>
                <span class="field__error" data-error-for="comment"></span>
            </div>
        <?php endif; ?>

        <?php
        /*
         * Согласия оформлены отдельными чекбоксами и не объединены
         * в общую фразу вида «отправляя форму, вы соглашаетесь».
         * Обязательное согласие — только на обработку данных;
         * реклама — отдельным необязательным чекбоксом.
         */
        ?>
        <div class="consent consent--required">
            <input type="checkbox" id="<?= e($id) ?>-consent" name="consent_personal" value="1" required>
            <label for="<?= e($id) ?>-consent">
                <?= e((string) $consent['personal_data']['checkbox']) ?> —
                <a href="<?= e(site_url((string) $consent['personal_data']['url'])) ?>" target="_blank" rel="noopener">текст согласия</a>,
                <a href="<?= e(site_url((string) $consent['privacy_policy']['url'])) ?>" target="_blank" rel="noopener">политика обработки</a>.
                <span class="consent__version">Версия <?= e((string) $consent['personal_data']['version']) ?> от <?= e(date('d.m.Y', strtotime((string) $consent['personal_data']['date']))) ?>. Согласие можно отозвать в любой момент.</span>
            </label>
        </div>
        <span class="field__error" data-error-for="consent_personal"><?= e($fieldError('consent_personal')) ?></span>

        <div class="consent">
            <input type="checkbox" id="<?= e($id) ?>-ads" name="consent_ads" value="1">
            <label for="<?= e($id) ?>-ads">
                <?= e((string) $consent['advertising']['checkbox']) ?> —
                <a href="<?= e(site_url((string) $consent['advertising']['url'])) ?>" target="_blank" rel="noopener">условия</a>.
                <span class="consent__version">Не обязательно: без этого согласия заявку мы всё равно примем, просто не будем присылать рекламу.</span>
            </label>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn--accent"><?= e((string) $o['button']) ?></button>
        </div>

        <p class="form__status" data-form-status role="status" aria-live="polite" hidden></p>

        <?php if (isset($errors['form'])): ?>
            <p class="form__status form__status--error" role="alert"><?= e((string) $errors['form']) ?></p>
        <?php endif; ?>

        <p class="form__note">
            Заявка ни к чему не обязывает. Мы перезвоним в рабочее время, уточним задачу и согласуем удобное время замера.
        </p>
    </form>
    <?php
}
