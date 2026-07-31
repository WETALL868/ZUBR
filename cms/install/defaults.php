<?php

/**
 * Значения, которые магазин получает при установке и при обновлении.
 *
 * Файл нужен обоим установщикам сразу: migrate.php ставит сайт с нуля,
 * update.php обновляет уже работающий. Раньше такой список пришлось бы
 * скопировать в оба, и они бы разошлись — обновлённый сайт получил бы не то
 * же самое, что новый.
 *
 * Все функции здесь ДОБАВЛЯЮТ недостающее и не трогают то, что владелец уже
 * поправил в панели. Запускать их можно сколько угодно раз.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/orders.php';

/**
 * Способы оплаты по умолчанию.
 *
 * Это не платёжные шлюзы: на сайте деньги не списываются. Это то, о чём
 * покупатель договаривается с магазином, и то, что менеджер видит в заказе.
 * Список повторяет варианты, которые стояли в прежней форме заказа, — чтобы
 * после обновления менеджер видел в заказах привычные слова, а не новые.
 *
 * Владелец правит список в панели: Оплата. Если когда-нибудь подключат приём
 * карт, способ добавится туда же.
 *
 * @return array<int,array<string,mixed>>
 */
function cms_default_payment_methods(): array
{
    return [
        [
            'code'        => 'cash',
            'title'       => 'Наличные',
            'description' => 'Оплата наличными при получении заказа.',
            'audience'    => 'both',
            'is_default'  => 1,
            'is_default_legal' => 0,
            'sort_order'  => 10,
        ],
        [
            'code'        => 'invoice',
            'title'       => 'Безналичный расчет для организации',
            'description' => 'Выставим счёт, подготовим спецификацию и закрывающие документы.',
            'audience'    => 'both',
            'is_default'  => 0,
            'is_default_legal' => 1,
            'sort_order'  => 20,
        ],
        [
            'code'        => 'individual',
            'title'       => 'Оплата как физлицо',
            'description' => 'Перевод по реквизитам или на карту — менеджер пришлёт данные.',
            'audience'    => 'individual',
            'is_default'  => 0,
            'is_default_legal' => 0,
            'sort_order'  => 30,
        ],
        [
            'code'        => 'consult',
            'title'       => 'Нужна консультация',
            'description' => 'Обсудим способ оплаты по телефону.',
            'audience'    => 'both',
            'is_default'  => 0,
            'is_default_legal' => 0,
            'sort_order'  => 40,
        ],
    ];
}

/**
 * Записывает недостающие способы оплаты.
 *
 * Существующие не перезаписываются: если владелец переименовал «Наличные»
 * или выключил их, обновление не должно возвращать всё назад.
 *
 * @return string[] коды добавленного
 */
function cms_seed_payment_methods(): array
{
    $added = [];

    foreach (cms_default_payment_methods() as $method) {
        $exists = cms_value('SELECT id FROM payment_methods WHERE code = ?', [$method['code']]);
        if ($exists) {
            continue;
        }

        $method['is_active'] = 1;
        cms_insert('payment_methods', $method);
        $added[] = (string)$method['code'];
    }

    return $added;
}

/**
 * Разметка секции заказа на главной.
 *
 * Форма собирается кодом (templates/partials/order-form.php), а в базе лежит
 * только текст вокруг неё — заголовок и абзац, которые владелец правит в
 * панели. Так изменение формы приезжает обновлением файлов, а не требует
 * править разметку в текстовом поле административной панели, где ошибиться
 * ничего не стоит.
 *
 * @return array{body:string,body_after:string} до формы и после неё
 */
function cms_order_block_markup(string $intro = ''): array
{
    if (trim($intro) === '') {
        $intro = <<<'HTML'
        <div class="order-copy">
          <p class="section-label">Форма заказа</p>
          <h2 id="order-title">Оставьте заявку на оборудование или партию</h2>
          <p>
            Напишите, что собираете или какое оборудование нужно заменить, и как планируете оплату. Ответим с наличием,
            рекомендацией по совместимости и условиями корпоративной премии для безналичного расчета.
          </p>
          <div class="contact-methods">
            <a href="tel:+74993221311">+7 (499) 322-13-11</a>
            <a href="mailto:info@comp-uter.ru">info@comp-uter.ru</a>
          </div>
        </div>
HTML;
    }

    return [
        'body' => "      <section class=\"section order-section\" id=\"order\" aria-labelledby=\"order-title\">\n"
            . rtrim($intro) . "\n",
        'body_after' => "      </section>\n",
    ];
}
