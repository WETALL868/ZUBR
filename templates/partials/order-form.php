<?php
/**
 * Форма оформления заказа.
 *
 * Раньше эта разметка лежала в базе, внутри блока главной страницы, и
 * правилась в текстовом поле панели. Для абзаца это удобно, для формы с
 * проверками — нет: одна случайная правка ломала оформление заказа, а
 * починить его можно было только через панель. Теперь в базе остался текст
 * вокруг формы, а сама форма собирается здесь.
 *
 * Что важно знать про устройство:
 *
 *   • Поля покупателя разделены на два набора — физическое и юридическое
 *     лицо. Скрытый набор помечается disabled, а не просто прячется:
 *     отключённые поля браузер не отправляет и не проверяет, поэтому
 *     незаполненные реквизиты юрлица не мешают физлицу оформить заказ.
 *     Значения при этом остаются на месте — переключился туда-обратно и
 *     ничего не потерял.
 *
 *   • Правила проверки берутся из orders_field_rules(). Те же самые правила
 *     применяет приёмник заказа. Список правил уезжает в браузер один раз,
 *     отдельным <script type="application/json">, поэтому подсказка у поля
 *     и ответ сервера не могут разойтись.
 *
 *   • Способы доставки и оплаты приходят из базы. Ни одной цены и ни одного
 *     срока в разметке нет: всё считает сервер, а показывает браузер.
 */

require_once __DIR__ . '/../../cms/orders.php';

$deliveryMethods = repo_delivery_methods();
$paymentMethods  = orders_payment_methods();
$fieldRules      = orders_field_rules();

/** Подпись и звёздочка у обязательного поля. */
$label = static function (string $field) use ($fieldRules): string {
    $rule = $fieldRules[$field] ?? null;
    $text = e($rule['label'] ?? $field);

    return $rule && !empty($rule['required'])
        ? $text . ' <span class="co-req" aria-hidden="true">*</span>'
        : $text;
};

/** Атрибуты поля: имя, обязательность и правило формата — одной строкой. */
$attrs = static function (string $field) use ($fieldRules): string {
    $rule = $fieldRules[$field] ?? null;
    $out  = ' name="' . e($field) . '" id="co-' . e($field) . '" data-co-field="' . e($field) . '"';

    if ($rule && !empty($rule['required'])) {
        $out .= ' data-co-required="1" aria-required="true"';
    }

    return $out;
};
?>
        <form class="lead-form checkout-form" id="email" novalidate>

          <?php /* Состав заказа — заполняется из корзины, см. src/main.js */ ?>
          <div class="order-cart-summary" data-order-cart-summary hidden>
            <strong>Состав заказа из корзины</strong>
            <div data-order-cart-lines></div>
          </div>

          <input type="hidden" name="cart_items" data-cart-items />
          <input type="hidden" name="cart_total" data-cart-total />
          <input type="hidden" name="cart_discount" data-cart-discount />
          <input type="hidden" name="delivery_method" data-delivery-method />
          <input type="hidden" name="delivery_price" data-delivery-price />
          <input type="hidden" name="delivery_term" data-delivery-term-hidden />
          <input type="hidden" name="delivery_region" data-delivery-region-hidden />
          <input type="hidden" name="delivery_city" data-delivery-city-hidden />
          <input type="hidden" name="delivery_address" data-delivery-address-hidden />
          <input type="hidden" name="delivery_comment" data-delivery-comment-hidden />
          <input type="hidden" name="order_total" data-order-total />
          <input type="hidden" name="quantity" data-order-quantity />

          <!-- ------------------------------------------------- Покупатель -->
          <section class="co-section" aria-labelledby="co-customer-title">
            <h3 class="co-title" id="co-customer-title">Покупатель</h3>

            <p class="co-field-label" id="co-type-label">Тип покупателя</p>
            <div class="co-switch" role="radiogroup" aria-labelledby="co-type-label">
              <?php foreach (ORDER_CUSTOMER_TYPES as $code => $title): ?>
              <label class="co-switch-option">
                <input
                  type="radio"
                  name="customer_type"
                  value="<?= e($code) ?>"
                  data-customer-type
                  <?= $code === 'individual' ? 'checked' : '' ?>
                />
                <span><?= e($code === 'individual' ? 'Физ. лицо' : 'Юр. лицо') ?></span>
              </label>
              <?php endforeach; ?>
            </div>

            <?php /* ------------------------------------------ физическое лицо */ ?>
            <fieldset class="co-fields" data-customer-fields="individual">
              <legend class="visually-hidden">Данные покупателя</legend>
              <div class="co-grid">
                <div class="co-field">
                  <label for="co-first_name"><?= $label('first_name') ?></label>
                  <input type="text" autocomplete="given-name"<?= $attrs('first_name') ?> />
                  <p class="co-error" data-co-error-for="first_name" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-last_name"><?= $label('last_name') ?></label>
                  <input type="text" autocomplete="family-name"<?= $attrs('last_name') ?> />
                  <p class="co-error" data-co-error-for="last_name" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-phone"><?= $label('phone') ?></label>
                  <input type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" data-phone-mask<?= $attrs('phone') ?> />
                  <p class="co-error" data-co-error-for="phone" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-email"><?= $label('email') ?></label>
                  <input type="email" autocomplete="email" placeholder="you@example.com"<?= $attrs('email') ?> />
                  <p class="co-error" data-co-error-for="email" role="alert"></p>
                </div>
              </div>
            </fieldset>

            <?php /* ------------------------------------------ юридическое лицо */ ?>
            <fieldset class="co-fields" data-customer-fields="legal" hidden disabled>
              <legend class="visually-hidden">Реквизиты организации</legend>
              <div class="co-grid">
                <div class="co-field">
                  <label for="co-legal_inn"><?= $label('legal_inn') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_inn') ?> />
                  <p class="co-error" data-co-error-for="legal_inn" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_kpp"><?= $label('legal_kpp') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_kpp') ?> />
                  <p class="co-error" data-co-error-for="legal_kpp" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_company"><?= $label('legal_company') ?></label>
                  <input type="text" autocomplete="organization"<?= $attrs('legal_company') ?> />
                  <p class="co-error" data-co-error-for="legal_company" role="alert"></p>
                </div>
                <div class="co-field co-field-full">
                  <label for="co-legal_address"><?= $label('legal_address') ?></label>
                  <textarea rows="2" autocomplete="off"<?= $attrs('legal_address') ?>></textarea>
                  <p class="co-error" data-co-error-for="legal_address" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_ogrn"><?= $label('legal_ogrn') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_ogrn') ?> />
                  <p class="co-error" data-co-error-for="legal_ogrn" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_account"><?= $label('legal_account') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_account') ?> />
                  <p class="co-error" data-co-error-for="legal_account" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_bik"><?= $label('legal_bik') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_bik') ?> />
                  <p class="co-error" data-co-error-for="legal_bik" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_corr_account"><?= $label('legal_corr_account') ?></label>
                  <input type="text" inputmode="numeric" autocomplete="off"<?= $attrs('legal_corr_account') ?> />
                  <p class="co-error" data-co-error-for="legal_corr_account" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_bank"><?= $label('legal_bank') ?></label>
                  <input type="text" autocomplete="off"<?= $attrs('legal_bank') ?> />
                  <p class="co-error" data-co-error-for="legal_bank" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_phone"><?= $label('legal_phone') ?></label>
                  <input type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" data-phone-mask<?= $attrs('legal_phone') ?> />
                  <p class="co-error" data-co-error-for="legal_phone" role="alert"></p>
                </div>
                <div class="co-field">
                  <label for="co-legal_email"><?= $label('legal_email') ?></label>
                  <input type="email" autocomplete="email" placeholder="you@example.com"<?= $attrs('legal_email') ?> />
                  <p class="co-error" data-co-error-for="legal_email" role="alert"></p>
                </div>
              </div>
            </fieldset>
          </section>

          <!-- --------------------------------------------------- Доставка -->
          <section class="co-section" aria-labelledby="co-delivery-title">
            <h3 class="co-title" id="co-delivery-title">Доставка</h3>

            <div class="co-grid">
              <div class="co-field">
                <label for="co-region">Регион</label>
                <input type="text" id="co-region" name="region" data-delivery-region autocomplete="address-level1" placeholder="Например, Москва" />
                <p class="co-error" data-co-error-for="region" role="alert"></p>
              </div>
              <div class="co-field">
                <label for="co-city">Город <span class="co-req" data-city-req hidden aria-hidden="true">*</span></label>
                <input type="text" id="co-city" data-delivery-city autocomplete="address-level2" placeholder="Например, Москва, Подольск, Казань" />
                <p class="co-error" data-co-error-for="city" role="alert"></p>
              </div>
            </div>

            <p class="co-field-label" id="co-delivery-types">Способ доставки</p>
            <div class="co-cards" role="radiogroup" aria-labelledby="co-delivery-types" data-delivery-cards>
              <?php foreach ($deliveryMethods as $i => $method):
                  $byTariff = $method['price'] === null;
                  $isFree   = !$byTariff && (float)$method['price'] <= 0;
                  $term     = trim((string)($method['term'] ?? ''));
              ?>
              <label class="co-card">
                <input
                  type="radio"
                  name="cartDelivery"
                  value="<?= e($method['title']) ?>"
                  data-delivery-price="<?= $byTariff ? '' : ($isFree ? '0' : e(cms_money_machine((float)$method['price']))) ?>"
                  data-delivery-title="<?= e((string)($method['option_title'] ?: $method['title'])) ?>"
                  data-delivery-free-from="<?= $method['free_from'] === null ? '' : e(cms_money_machine((float)$method['free_from'])) ?>"
                  data-delivery-term="<?= e($term) ?>"
                  <?= (int)$method['needs_address'] === 1 ? ' data-delivery-details="true"' : '' ?>
                  <?= $i === 0 ? ' checked' : '' ?>
                />
                <span class="co-card-body">
                  <strong><?= e($method['title']) ?></strong>
                  <em data-delivery-card-price><?= $byTariff
                      ? 'по тарифам службы'
                      : ($isFree ? 'бесплатно' : e(cms_money((float)$method['price']))) ?></em>
                  <?php if ($term !== ''): ?><em class="co-card-term"><?= e($term) ?></em><?php endif; ?>
                </span>
              </label>
              <?php endforeach; ?>
            </div>

            <div class="co-delivery-detail" data-delivery-option hidden>
              <p class="co-field-label">Вариант доставки</p>
              <p class="co-delivery-option-title" data-delivery-option-title></p>
              <p class="co-delivery-note" data-delivery-note></p>
            </div>

            <dl class="co-delivery-summary">
              <div>
                <dt>Стоимость доставки</dt>
                <dd data-delivery-summary-price>не выбрано</dd>
              </div>
              <div data-delivery-summary-term-row hidden>
                <dt>Срок доставки</dt>
                <dd data-delivery-summary-term></dd>
              </div>
            </dl>

            <div class="co-field co-field-full" data-delivery-address-field hidden>
              <label for="co-address">Улица, дом, квартира <span class="co-req" aria-hidden="true">*</span></label>
              <textarea id="co-address" rows="2" data-delivery-address autocomplete="street-address" placeholder="Адрес доставки или пункт выдачи"></textarea>
              <p class="co-error" data-co-error-for="address" role="alert"></p>
            </div>

            <div class="co-field co-field-full">
              <label for="co-delivery-comment">Пожелание по доставке</label>
              <input type="text" id="co-delivery-comment" data-delivery-comment placeholder="Например: СДЭК до ПВЗ, Яндекс Маркет, Ozon, ТК Деловые линии" />
            </div>
          </section>

          <!-- ----------------------------------------------------- Оплата -->
          <?php if ($paymentMethods): ?>
          <section class="co-section" aria-labelledby="co-payment-title">
            <h3 class="co-title" id="co-payment-title">Оплата</h3>
            <div class="co-cards co-cards-wide" role="radiogroup" aria-labelledby="co-payment-title" data-payment-cards>
              <?php foreach ($paymentMethods as $method): ?>
              <label
                class="co-card co-card-payment"
                data-payment-audience="<?= e((string)$method['audience']) ?>"
                <?= (int)$method['is_default'] === 1 ? ' data-payment-default-individual="1"' : '' ?>
                <?= (int)$method['is_default_legal'] === 1 ? ' data-payment-default-legal="1"' : '' ?>
              >
                <input type="radio" name="payment_code" value="<?= e((string)$method['code']) ?>" data-payment-title="<?= e((string)$method['title']) ?>" />
                <span class="co-card-body">
                  <strong><?= e((string)$method['title']) ?></strong>
                  <?php if (trim((string)$method['description']) !== ''): ?>
                  <em><?= e(trim((string)$method['description'])) ?></em>
                  <?php endif; ?>
                </span>
              </label>
              <?php endforeach; ?>
            </div>
            <p class="co-error" data-co-error-for="payment_code" role="alert"></p>
          </section>
          <?php endif; ?>

          <!-- ------------------------- что подобрать: только заявка без корзины -->
          <section class="co-section" data-lead-only hidden>
            <h3 class="co-title">Что вам нужно</h3>
            <div class="co-grid">
              <div class="co-field">
                <label for="co-category">Что необходимо подобрать</label>
                <select id="co-category" name="category" data-order-category>
                  <option value="">Не важно / уже определился</option>
                  <option>Серверный процессор</option>
                  <option>Диск или накопитель</option>
                  <option>Процессор и накопитель</option>
                  <option>Нужна консультация</option>
                </select>
              </div>
              <div class="co-field">
                <label for="co-goal">Модель или задача <span class="co-req" aria-hidden="true">*</span></label>
                <select id="co-goal" name="goal" data-order-goal>
                  <option value="">Выберите вариант</option>
                  <?php foreach (repo_products(['limit' => 100]) as $goalProduct): ?>
                  <option><?= e((string)($goalProduct['short_name'] ?: $goalProduct['name'])) ?></option>
                  <?php endforeach; ?>
                  <option>Заказ из корзины</option>
                  <option>Подобрать сборку</option>
                  <option>Подобрать серверный процессор</option>
                  <option>Подобрать диск или накопитель</option>
                  <option>Заказать партию для продажи</option>
                  <option>Закупить для организации</option>
                </select>
                <p class="co-error" data-co-error-for="goal" role="alert"></p>
              </div>
            </div>
          </section>

          <!-- --------------------------------------------------- Согласия -->
          <section class="co-section co-consents">
            <label class="consent-label">
              <input name="terms" type="checkbox" data-co-consent value="1" />
              <span>
                Я прочитал и соглашаюсь с правилами:
                <a href="/polzovatelskoe-soglashenie/" target="_blank" rel="noopener">Пользовательское соглашение</a>.
              </span>
            </label>
            <label class="consent-label">
              <input name="privacy" type="checkbox" data-co-consent value="1" />
              <span>
                Оформляя заказ, я принимаю условия
                <a href="/privacy_policy/" target="_blank" rel="noopener">политики обработки персональных данных</a>.
              </span>
            </label>
            <p class="co-error" data-co-error-for="consents" role="alert"></p>
          </section>

          <!-- ------------------------------------------------------- Итог -->
          <section class="co-section co-summary" aria-labelledby="co-summary-title">
            <h3 class="visually-hidden" id="co-summary-title">Итог заказа</h3>

            <button class="co-comment-toggle" type="button" data-comment-toggle aria-expanded="false" aria-controls="co-comment-box">
              Комментарий к заказу
              <span class="co-comment-caret" aria-hidden="true"></span>
            </button>
            <div class="co-comment-box" id="co-comment-box" hidden>
              <label class="visually-hidden" for="co-message">Комментарий к заказу</label>
              <textarea id="co-message" name="message" rows="3" placeholder="Модель сервера, плата или NAS, нужный объём, сроки или задача"></textarea>
            </div>

            <dl class="co-totals">
              <div>
                <dt>Стоимость товаров</dt>
                <dd data-cart-subtotal>0 ₽</dd>
              </div>
              <div>
                <dt>Стоимость доставки</dt>
                <dd data-cart-delivery-total>не выбрано</dd>
              </div>
              <div data-discount-row hidden>
                <dt>Скидка</dt>
                <dd data-cart-discount-total>0 ₽</dd>
              </div>
              <div class="co-grand">
                <dt data-total-label>Итого</dt>
                <dd data-cart-grand-total>0 ₽</dd>
              </div>
            </dl>

            <div class="form-trap" aria-hidden="true">
              <label>
                Не заполняйте это поле
                <input name="company_website" type="text" tabindex="-1" autocomplete="off" />
              </label>
            </div>
            <input type="hidden" name="form_rendered_at" data-form-rendered-at />

            <button class="button primary co-submit" type="submit">Подтвердить заказ</button>
            <p class="form-note">Для юрлиц подготовим счет, резерв, спецификацию и закрывающие документы.</p>
            <p class="form-status" role="status" aria-live="polite"></p>
          </section>

          <?php /*
            Правила проверки уезжают в браузер как данные, а не как код: так
            подсказка у поля берётся из того же места, что и ответ сервера.
            JSON_HEX_TAG обязателен — иначе «</script>» в подписи поля закрыл
            бы тег раньше времени.
          */ ?>
          <script type="application/json" data-co-rules><?= json_encode(
              array_map(static fn(array $rule): array => [
                  'label'    => $rule['label'],
                  'pattern'  => $rule['pattern'] ?? null,
                  'message'  => $rule['message'] ?? '',
                  'required' => (bool)($rule['required'] ?? false),
                  'types'    => $rule['types'],
              ], $fieldRules),
              JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
          ) ?></script>
        </form>
