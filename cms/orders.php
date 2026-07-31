<?php

/**
 * Заказы: проверка состава и запись в базу.
 *
 * Ничего денежного из браузера не принимается на веру. Покупатель присылает
 * список товаров и количество — цену, стоимость доставки и итог считает
 * сервер по базе. Иначе достаточно открыть инструменты разработчика и
 * оформить процессор за рубль, а магазин узнает об этом из письма, где
 * подделанная сумма выглядит как настоящая.
 *
 * Заказ хранит собственную копию цен: через полгода товар может подорожать,
 * а заказ обязан показывать то, о чём договорились.
 */

declare(strict_types=1);

require_once __DIR__ . '/repo.php';

const ORDER_STATUSES = [
    'new'       => 'Новый',
    'confirmed' => 'Подтверждён',
    'paid'      => 'Оплачен',
    'shipped'   => 'Отправлен',
    'done'      => 'Выполнен',
    'cancelled' => 'Отменён',
];

const ORDER_CUSTOMER_TYPES = [
    'individual' => 'Физическое лицо',
    'legal'      => 'Юридическое лицо',
];

/**
 * Правила проверки полей покупателя — единственный экземпляр на весь проект.
 *
 * Отсюда их берёт и форма (браузер подсказывает ошибку сразу), и приёмник
 * заказа (браузеру верить нельзя). Раньше подобные правила приходилось
 * держать в двух местах, и они неизбежно расходились: сайт принимал то, что
 * сервер потом отвергал, и покупатель видел непонятный отказ.
 *
 * Ключ — имя поля в форме. Ключи полей юрлица начинаются с legal_.
 *
 * @return array<string,array{label:string,pattern:?string,message:string,required:bool,types:string[]}>
 */
function orders_field_rules(): array
{
    $digits = static fn(string $count, string $what): array => [
        'pattern' => '^\d{' . $count . '}$',
        'message' => $what,
    ];

    return [
        'first_name' => [
            'label' => 'Имя', 'required' => true, 'types' => ['individual'],
            'pattern' => null, 'message' => 'Укажите имя.',
        ],
        'last_name' => [
            'label' => 'Фамилия', 'required' => false, 'types' => ['individual'],
            'pattern' => null, 'message' => '',
        ],
        'legal_inn' => [
            'label' => 'ИНН', 'required' => true, 'types' => ['legal'],
            'pattern' => '^(\d{10}|\d{12})$',
            'message' => 'ИНН состоит из 10 цифр у организации или 12 у предпринимателя.',
        ],
        'legal_kpp' => [
            'label' => 'КПП', 'required' => false, 'types' => ['legal'],
        ] + $digits('9', 'КПП состоит из 9 цифр.'),
        'legal_company' => [
            'label' => 'Наименование компании', 'required' => false, 'types' => ['legal'],
            'pattern' => null, 'message' => '',
        ],
        'legal_address' => [
            'label' => 'Юридический адрес', 'required' => false, 'types' => ['legal'],
            'pattern' => null, 'message' => '',
        ],
        'legal_ogrn' => [
            'label' => 'ОГРН', 'required' => false, 'types' => ['legal'],
            'pattern' => '^(\d{13}|\d{15})$',
            'message' => 'ОГРН состоит из 13 цифр у организации или 15 у предпринимателя.',
        ],
        'legal_account' => [
            'label' => 'Расчётный счёт', 'required' => false, 'types' => ['legal'],
        ] + $digits('20', 'Расчётный счёт состоит из 20 цифр.'),
        'legal_bik' => [
            'label' => 'БИК', 'required' => true, 'types' => ['legal'],
        ] + $digits('9', 'БИК состоит из 9 цифр.'),
        'legal_corr_account' => [
            'label' => 'Корреспондентский счёт', 'required' => false, 'types' => ['legal'],
        ] + $digits('20', 'Корреспондентский счёт состоит из 20 цифр.'),
        'legal_bank' => [
            'label' => 'Наименование банка', 'required' => false, 'types' => ['legal'],
            'pattern' => null, 'message' => '',
        ],
        /*
         * Телефон и почта есть у обоих типов покупателя, но полями они
         * остаются разными. Два поля с одним именем в одной форме — это
         * список, а не значение: браузер вернул бы оба, и было бы неясно,
         * какое из них заполнил покупатель. Поэтому у юрлица свои имена, а
         * правило проверки одно и то же.
         */
        'phone' => [
            'label' => 'Телефон', 'required' => true, 'types' => ['individual'],
            'pattern' => '^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$',
            'message' => 'Телефон в формате +7 (999) 123-45-67.',
        ],
        'email' => [
            'label' => 'Электронная почта', 'required' => false, 'types' => ['individual'],
            'pattern' => '^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$',
            'message' => 'Электронная почта в формате you@example.com.',
        ],
        'legal_phone' => [
            'label' => 'Телефон', 'required' => true, 'types' => ['legal'],
            'pattern' => '^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$',
            'message' => 'Телефон в формате +7 (999) 123-45-67.',
        ],
        'legal_email' => [
            'label' => 'Электронная почта', 'required' => false, 'types' => ['legal'],
            'pattern' => '^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$',
            'message' => 'Электронная почта в формате you@example.com.',
        ],
    ];
}

/**
 * Сводит поля формы к единому виду заказа, независимо от типа покупателя.
 *
 * Дальше — и в базе, и в письмах, и в панели — заказ выглядит одинаково:
 * есть имя, телефон, почта и, если покупатель юридическое лицо, реквизиты.
 *
 * @param array<string,string> $values значения полей формы
 * @return array<string,string>
 */
function orders_customer_from_form(string $customerType, array $values): array
{
    $get = static fn(string $key): string => trim((string)($values[$key] ?? ''));
    $type = isset(ORDER_CUSTOMER_TYPES[$customerType]) ? $customerType : 'individual';

    if ($type === 'legal') {
        $company = $get('legal_company');

        return [
            'customer_type' => 'legal',
            // Менеджер зовёт юридическое лицо по названию; если названия нет,
            // хотя бы по ИНН — пустая строка в письме бесполезна.
            'name'          => $company !== '' ? $company : ('ИНН ' . $get('legal_inn')),
            'first_name'    => '',
            'last_name'     => '',
            'phone'         => $get('legal_phone'),
            'email'         => $get('legal_email'),
            'company'       => $company,
            'inn'           => $get('legal_inn'),
            'kpp'           => $get('legal_kpp'),
            'ogrn'          => $get('legal_ogrn'),
            'legal_address' => $get('legal_address'),
            'bank_name'     => $get('legal_bank'),
            'bik'           => $get('legal_bik'),
            'bank_account'  => $get('legal_account'),
            'corr_account'  => $get('legal_corr_account'),
        ];
    }

    $first = $get('first_name');
    $last  = $get('last_name');

    return [
        'customer_type' => 'individual',
        'name'          => trim($first . ' ' . $last),
        'first_name'    => $first,
        'last_name'     => $last,
        'phone'         => $get('phone'),
        'email'         => $get('email'),
        'company'       => '',
        'inn'           => '',
        'kpp'           => '',
        'ogrn'          => '',
        'legal_address' => '',
        'bank_name'     => '',
        'bik'           => '',
        'bank_account'  => '',
        'corr_account'  => '',
    ];
}

/**
 * Реквизиты юридического лица списком «подпись => значение».
 *
 * Одна функция на письма, панель и печать: иначе в одном месте забыли бы
 * КПП, в другом — корреспондентский счёт.
 *
 * @return array<string,string> только заполненные поля
 */
function orders_legal_details(array $order): array
{
    $map = [
        'Наименование компании'  => $order['company'] ?? '',
        'ИНН'                    => $order['inn'] ?? '',
        'КПП'                    => $order['kpp'] ?? '',
        'ОГРН'                   => $order['ogrn'] ?? '',
        'Юридический адрес'      => $order['legal_address'] ?? '',
        'Наименование банка'     => $order['bank_name'] ?? '',
        'БИК'                    => $order['bik'] ?? '',
        'Расчётный счёт'         => $order['bank_account'] ?? '',
        'Корреспондентский счёт' => $order['corr_account'] ?? '',
    ];

    return array_filter(array_map(static fn($v) => trim((string)$v), $map), static fn($v) => $v !== '');
}

/**
 * Проверяет поля покупателя по правилам выше.
 *
 * Поля чужого типа покупателя не проверяются вовсе: скрытая форма юрлица не
 * должна мешать физлицу оформить заказ.
 *
 * @return array<string,string> поле => сообщение об ошибке
 */
function orders_validate_customer(string $customerType, array $values): array
{
    $type = isset(ORDER_CUSTOMER_TYPES[$customerType]) ? $customerType : 'individual';
    $errors = [];

    foreach (orders_field_rules() as $field => $rule) {
        if (!in_array($type, $rule['types'], true)) {
            continue;
        }

        $value = trim((string)($values[$field] ?? ''));

        if ($value === '') {
            if (!empty($rule['required'])) {
                $errors[$field] = 'Заполните поле «' . $rule['label'] . '».';
            }
            continue;   // необязательное пустое поле проверять не по чему
        }

        if (!empty($rule['pattern']) && !preg_match('~' . $rule['pattern'] . '~u', $value)) {
            $errors[$field] = $rule['message'];
        }
    }

    return $errors;
}

/** Активные способы оплаты; $audience — individual, legal или null для всех. */
function orders_payment_methods(?string $audience = null): array
{
    $methods = cms_all('SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order, id');

    if ($audience === null) {
        return $methods;
    }

    return array_values(array_filter($methods, static function (array $method) use ($audience): bool {
        return $method['audience'] === 'both' || $method['audience'] === $audience;
    }));
}

/** Способ оплаты по коду — или null, если такого нет или он выключен. */
function orders_payment_method(string $code): ?array
{
    $code = trim($code);
    if ($code === '') {
        return null;
    }

    foreach (orders_payment_methods() as $method) {
        if ((string)$method['code'] === $code) {
            return $method;
        }
    }

    return null;
}

/**
 * Пересчитывает корзину по базе.
 *
 * @param array $rawItems то, что прислал браузер: id (адрес товара) и qty
 * @return array{items:array,total:float,problems:string[]}
 */
function orders_price_cart(array $rawItems): array
{
    $items = [];
    $problems = [];
    $total = 0.0;
    $discount = 0.0;

    foreach ($rawItems as $raw) {
        if (!is_array($raw)) {
            continue;
        }

        $slug = preg_replace('~[^a-zA-Z0-9_-]~', '', trim((string)($raw['id'] ?? '')));
        if ($slug === '') {
            continue;
        }

        $qty = (int)($raw['qty'] ?? 1);
        // Верхняя граница нужна не от жадности, а от опечатки и от подбора:
        // 1 000 000 штук в заказе — это не заказ.
        $qty = max(1, min(999, $qty));

        $product = repo_product($slug);

        if (!$product || (int)$product['is_published'] !== 1 || $product['deleted_at'] !== null) {
            $problems[] = 'Товар «' . $slug . '» больше не продаётся — уберите его из корзины.';
            continue;
        }

        $price = repo_price($product);
        if ($price === null) {
            $problems[] = 'У товара «' . ($product['short_name'] ?: $product['name']) . '» сейчас цена по запросу.';
            continue;
        }

        if ($product['availability'] === 'out_of_stock') {
            $problems[] = 'Товара «' . ($product['short_name'] ?: $product['name']) . '» нет в наличии.';
            continue;
        }

        // Остаток учитывается, только если магазин его ведёт: пустое поле
        // означает «не считаем», а не «ноль штук».
        //
        // Количество урезается не молча: покупатель должен увидеть, сколько
        // именно осталось, и подтвердить новую сумму сам — иначе он ждёт
        // пятьдесят штук, а приедет три.
        if ($product['stock_qty'] !== null && (int)$product['stock_qty'] > 0 && $qty > (int)$product['stock_qty']) {
            $problems[] = 'Товара «' . ($product['short_name'] ?: $product['name'])
                . '» осталось ' . (int)$product['stock_qty'] . ' шт. — уменьшите количество в корзине.';
            continue;
        }

        $lineTotal = round($price * $qty, 2);
        $total += $lineTotal;

        // Выгода считается по базе, а не по тому, что прислал браузер:
        // старая цена — такой же товарный реквизит, как и текущая.
        $old = repo_old_price($product);
        if ($old !== null) {
            $discount += round(($old - $price) * $qty, 2);
        }

        $items[] = [
            'product_id' => (int)$product['id'],
            'slug'       => (string)$product['slug'],
            'sku'        => (string)$product['sku'],
            'title'      => (string)($product['short_name'] ?: $product['name']),
            'price'      => $price,
            'old_price'  => $old,
            'qty'        => $qty,
            'total'      => $lineTotal,
        ];
    }

    return [
        'items'    => $items,
        'total'    => round($total, 2),
        'discount' => round($discount, 2),
        'problems' => $problems,
    ];
}

/**
 * Стоимость доставки по базе.
 *
 * Возвращает найденный способ и цену; null в цене означает «по тарифам
 * службы доставки» — это не ноль и не бесплатно.
 *
 * @return array{method:?array,price:?float,title:string}
 */
function orders_delivery(string $title, float $itemsTotal): array
{
    $methods = repo_delivery_methods();
    $found = null;

    foreach ($methods as $method) {
        // Браузер присылает название способа так, как оно написано в корзине.
        if (mb_strtolower(trim($title)) === mb_strtolower((string)$method['title'])
            || str_starts_with(mb_strtolower(trim($title)), mb_strtolower((string)$method['title']))) {
            $found = $method;
            break;
        }
    }

    if (!$found) {
        return ['method' => null, 'price' => null, 'title' => trim($title), 'term' => ''];
    }

    $price = $found['price'] === null ? null : (float)$found['price'];

    // Бесплатно от суммы — правило магазина, а не пожелание покупателя,
    // поэтому применяется здесь, а не в корзине.
    if ($price !== null && $found['free_from'] !== null && $itemsTotal >= (float)$found['free_from']) {
        $price = 0.0;
    }

    return [
        'method' => $found,
        'price'  => $price,
        'title'  => (string)$found['title'],
        'term'   => trim((string)($found['term'] ?? '')),
    ];
}

/** Номер заказа: читается вслух по телефону и не повторяется. */
function orders_next_number(): string
{
    return 'XS' . gmdate('Ymd-Hi') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Записывает заказ в базу.
 *
 * @return int|null id заказа или null, если база недоступна: недоступная
 *                  база не должна мешать заказу уйти письмом
 */
function orders_store(array $order, array $items, string $mailStatus = ''): ?int
{
    try {
        $text = static fn(string $key): ?string => (string)($order[$key] ?? '') !== ''
            ? (string)$order[$key]
            : null;

        $orderId = cms_insert('orders', [
            'number'         => (string)$order['number'],
            'status'         => 'new',
            'customer_type'  => isset(ORDER_CUSTOMER_TYPES[(string)($order['customer_type'] ?? '')])
                                ? (string)$order['customer_type']
                                : 'individual',
            'customer_name'  => (string)$order['name'],
            'first_name'     => $text('first_name'),
            'last_name'      => $text('last_name'),
            'phone'          => (string)$order['phone'],
            'email'          => (string)$order['email'],
            'company'        => $text('company'),
            'inn'            => $text('inn'),
            'kpp'            => $text('kpp'),
            'ogrn'           => $text('ogrn'),
            'legal_address'  => $text('legal_address'),
            'bank_name'      => $text('bank_name'),
            'bik'            => $text('bik'),
            'bank_account'   => $text('bank_account'),
            'corr_account'   => $text('corr_account'),
            'region'         => $text('region'),
            'city'           => $text('city'),
            'address'        => $text('address'),
            'delivery_code'  => $order['delivery_code'] ?? null,
            'delivery_title' => $text('delivery_title'),
            'delivery_price' => $order['delivery_price'],
            'delivery_term'  => $text('delivery_term'),
            'payment'        => $text('payment'),
            'payment_code'   => $text('payment_code'),
            'goal'           => $text('goal'),
            'comment'        => $text('comment'),
            'items_total'    => $order['items_total'],
            'discount'       => ($order['discount'] ?? 0) > 0 ? $order['discount'] : null,
            'total'          => $order['total'],
            'source'         => (string)($order['source'] ?? 'site'),
            'user_agent'     => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
            'ip'             => mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            'mail_status'    => $mailStatus ?: null,
            'created_at'     => cms_now(),
        ]);

        foreach ($items as $item) {
            cms_insert('order_items', [
                'order_id'   => $orderId,
                'product_id' => $item['product_id'] ?: null,
                'sku'        => $item['sku'] ?: null,
                'title'      => $item['title'],
                'price'      => $item['price'],
                'qty'        => $item['qty'],
                'total'      => $item['total'],
            ]);
        }

        cms_insert('order_status_log', [
            'order_id'   => $orderId,
            'status'     => 'new',
            'note'       => 'Заказ поступил с сайта',
            'created_at' => cms_now(),
        ]);

        return $orderId;
    } catch (Throwable $e) {
        // Заказ уже принят покупателем; если база отказала, письмо всё равно
        // должно уйти, а ошибка — попасть в журнал сервера.
        error_log('CMS_ORDER_STORE_FAILED ' . $e->getMessage());
        return null;
    }
}

/** Отмечает, чем закончилась отправка письма. */
function orders_set_mail_status(?int $orderId, string $status): void
{
    if (!$orderId) {
        return;
    }

    try {
        cms_update('orders', ['mail_status' => $status, 'updated_at' => cms_now()], 'id = :id', ['id' => $orderId]);
    } catch (Throwable) {
        // Не критично: заказ уже сохранён.
    }
}
