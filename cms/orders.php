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

        $items[] = [
            'product_id' => (int)$product['id'],
            'slug'       => (string)$product['slug'],
            'sku'        => (string)$product['sku'],
            'title'      => (string)($product['short_name'] ?: $product['name']),
            'price'      => $price,
            'qty'        => $qty,
            'total'      => $lineTotal,
        ];
    }

    return ['items' => $items, 'total' => round($total, 2), 'problems' => $problems];
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
        return ['method' => null, 'price' => null, 'title' => trim($title)];
    }

    $price = $found['price'] === null ? null : (float)$found['price'];

    // Бесплатно от суммы — правило магазина, а не пожелание покупателя,
    // поэтому применяется здесь, а не в корзине.
    if ($price !== null && $found['free_from'] !== null && $itemsTotal >= (float)$found['free_from']) {
        $price = 0.0;
    }

    return ['method' => $found, 'price' => $price, 'title' => (string)$found['title']];
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
        $orderId = cms_insert('orders', [
            'number'         => (string)$order['number'],
            'status'         => 'new',
            'customer_name'  => (string)$order['name'],
            'phone'          => (string)$order['phone'],
            'email'          => (string)$order['email'],
            'company'        => (string)($order['company'] ?? '') ?: null,
            'city'           => (string)($order['city'] ?? '') ?: null,
            'address'        => (string)($order['address'] ?? '') ?: null,
            'delivery_code'  => $order['delivery_code'] ?? null,
            'delivery_title' => (string)($order['delivery_title'] ?? '') ?: null,
            'delivery_price' => $order['delivery_price'],
            'payment'        => (string)($order['payment'] ?? '') ?: null,
            'goal'           => (string)($order['goal'] ?? '') ?: null,
            'comment'        => (string)($order['comment'] ?? '') ?: null,
            'items_total'    => $order['items_total'],
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
