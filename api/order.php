<?php

declare(strict_types=1);

// Каталог, цены, доставка и хранение заказов — из базы CMS. Файл цен
// data/prices.json больше не читается: он был единственным источником до
// перехода на базу, и держать два источника значит однажды их рассогласовать.
require_once dirname(__DIR__) . '/cms/orders.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function clean_value($value): string
{
    return trim((string)($value ?? ''));
}

function clean_smtp_password($value): string
{
    return preg_replace('/[\s-]+/', '', (string)($value ?? ''));
}

function create_order_id(): string
{
    return 'XS' . gmdate('Ymd-Hi') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function bool_field($value): bool
{
    return $value === true || $value === 'on' || $value === '1' || $value === 1;
}

/**
 * Денежное значение из формы. Дробные цены поддерживаются: раньше здесь
 * вырезались все нецифровые символы, и «2800.50» превращалось в 280050.
 */
function money_value($value): float
{
    if (is_int($value) || is_float($value)) {
        return max(0.0, round((float)$value, 2));
    }

    $text = preg_replace('~[\s\x{00A0}\x{202F}]+~u', '', (string)($value ?? ''));
    $text = str_replace(',', '.', (string)$text);
    if (!preg_match('~\d+(\.\d+)?~', $text, $match)) {
        return 0.0;
    }

    return max(0.0, round((float)$match[0], 2));
}

/** Один и тот же формат цены, что и на сайте: «15 500 ₽», «15 500,50 ₽». */
function format_money_value(float $value): string
{
    return cms_money($value);
}

/**
 * Состав корзины, пересчитанный по базе.
 *
 * Из присланного берутся только адрес товара и количество. Название, цена и
 * сумма строки приходят из каталога: подделать их из браузера невозможно.
 * Строка, которую нельзя купить (товар снят, нет цены, нет в наличии), в
 * заказ не попадает, а причина возвращается покупателю.
 *
 * @return array{0:array,1:string[]} позиции и замечания
 */
function normalize_cart_items($value): array
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        $value = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($value)) {
        return [[], [], 0.0];
    }

    $priced = orders_price_cart($value);

    return [$priced['items'], $priced['problems'], $priced['discount']];
}

function cart_summary_text(array $items): string
{
    if (!$items) {
        return 'не указано';
    }

    $lines = [];
    foreach ($items as $index => $item) {
        $lines[] = ($index + 1) . '. ' . $item['title'] . ' — ' . $item['qty'] . ' шт. × ' . format_money_value($item['price']) . ' = ' . format_money_value($item['total']);
    }

    return implode("\n", $lines);
}

/**
 * Spam gate. Three cheap, captcha-free signals:
 *   1. honeypot field that no human ever sees or fills in;
 *   2. submissions that arrive faster than a human could type the form;
 *   3. a per-IP rate limit so a single source cannot flood the inbox.
 * Returns a reason string when the request should be dropped, '' otherwise.
 */
function spam_reason(array $raw, array $config): string
{
    if (clean_value($raw['company_website'] ?? '') !== '') {
        return 'honeypot';
    }

    $min_seconds = max(0, (int)($config['min_fill_seconds'] ?? 3));
    $rendered_at = (int)($raw['form_rendered_at'] ?? 0);
    if ($min_seconds > 0 && $rendered_at > 0) {
        $elapsed = (int)floor((microtime(true) * 1000 - $rendered_at) / 1000);
        // Negative elapsed means a skewed client clock — not evidence of a bot.
        if ($elapsed >= 0 && $elapsed < $min_seconds) {
            return 'too_fast';
        }
    }

    return rate_limit_reason($config);
}

function rate_limit_reason(array $config): string
{
    $max = max(0, (int)($config['rate_limit_per_hour'] ?? 8));
    if ($max === 0) {
        return '';
    }

    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') {
        return '';
    }

    $dir = sys_get_temp_dir() . '/comp-uter-rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return '';
    }

    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    $now = time();
    $window = 3600;
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter(
                $decoded,
                static fn($ts) => is_int($ts) && ($now - $ts) < $window
            ));
        }
    }

    if (count($hits) >= $max) {
        return 'rate_limited';
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return '';
}

function normalize_order(array $raw): array
{
    [$cart_items, $cart_problems, $cart_discount] = normalize_cart_items($raw['cart_items'] ?? []);
    $order_total_raw = clean_value($raw['order_total'] ?? '');

    /*
     * Тип покупателя и его поля.
     *
     * Форма присылает два разных набора: у физического лица имя и фамилия,
     * у юридического — реквизиты организации. Скрытый набор браузер не
     * отправляет вовсе (он отключён), но полагаться на это нельзя: запрос
     * может прийти откуда угодно. Поэтому берём поля строго того типа,
     * который указан, а чужие игнорируем.
     */
    $customer_type = clean_value($raw['customer_type'] ?? 'individual');
    $customer_type = isset(ORDER_CUSTOMER_TYPES[$customer_type]) ? $customer_type : 'individual';

    $customer_input = [];
    foreach (array_keys(orders_field_rules()) as $field) {
        $customer_input[$field] = clean_value($raw[$field] ?? '');
    }
    $customer_errors = orders_validate_customer($customer_type, $customer_input);
    $customer = orders_customer_from_form($customer_type, $customer_input);

    $order = array_merge($customer, [
        'id' => create_order_id(),
        'created_at' => gmdate('c'),
        'email' => strtolower($customer['email']),
        'quantity' => clean_value($raw['quantity'] ?? ''),
        'payment_code' => clean_value($raw['payment_code'] ?? ''),
        'payment' => clean_value($raw['payment'] ?? ''),
        'goal' => clean_value($raw['goal'] ?? ''),
        'category' => clean_value($raw['category'] ?? ''),
        'message' => clean_value($raw['message'] ?? ''),
        'privacy' => bool_field($raw['privacy'] ?? false),
        'terms' => bool_field($raw['terms'] ?? false),
        'cart_items' => $cart_items,
        // Сумма пересчитывается по проверенным ценам, а не берётся из формы.
        'cart_total' => $cart_items
            ? round(array_sum(array_column($cart_items, 'total')), 2)
            : money_value($raw['cart_total'] ?? 0),
        // Выгода — тоже с сервера: браузер прислать её не может.
        'discount' => $cart_items ? $cart_discount : 0.0,
        'delivery_method' => clean_value($raw['delivery_method'] ?? ''),
        'delivery_code' => null,
        'delivery_price' => null,
        'delivery_term' => '',
        'delivery_region' => clean_value($raw['delivery_region'] ?? $raw['region'] ?? ''),
        'delivery_city' => clean_value($raw['delivery_city'] ?? ''),
        'delivery_address' => clean_value($raw['delivery_address'] ?? ''),
        'delivery_comment' => clean_value($raw['delivery_comment'] ?? ''),
        'order_total' => $order_total_raw === '' ? null : money_value($order_total_raw),
    ]);

    /*
     * Способ оплаты сверяется со списком в базе.
     *
     * Название приходит из формы только ради письма; решает код. Если кода
     * нет или он выключен, название из запроса не принимается — иначе в
     * заказе оказалось бы что угодно, вплоть до «Оплачено».
     */
    $payment_method = orders_payment_method($order['payment_code']);
    if ($payment_method) {
        $order['payment'] = (string)$payment_method['title'];
        $order['payment_code'] = (string)$payment_method['code'];
    } else {
        $order['payment_code'] = '';
    }

    $delivery_method_row = null;

    // Доставка тоже берётся из базы, а не из формы: способ определяется по
    // названию, а цену, скидку «бесплатно от суммы» и «по тарифам службы»
    // решает магазин.
    if ($order['cart_items']) {
        $delivery = orders_delivery($order['delivery_method'], $order['cart_total']);
        $delivery_method_row = $delivery['method'] ?? null;
        $order['delivery_method'] = $delivery['title'];
        $order['delivery_code'] = $delivery['method']['code'] ?? null;
        $order['delivery_price'] = $delivery['price'];
        $order['delivery_term'] = $delivery['term'];

        $order['order_total'] = $order['delivery_price'] === null
            ? null
            : round($order['cart_total'] + $order['delivery_price'], 2);
    }

    /*
     * Поля покупателя проверяются теми же правилами, что показала форма, —
     * orders_field_rules(). Правил ровно один экземпляр, поэтому сайт не
     * может принять то, что сервер потом отвергнет, и наоборот.
     */
    $errors = array_merge($cart_problems, array_values($customer_errors));

    // Способ оплаты обязателен, только если магазин их вообще настроил.
    if ($order['payment_code'] === '' && orders_payment_methods($customer_type)) {
        $errors[] = 'Выберите способ оплаты.';
    }

    // Заявка без корзины обязана сказать, о чём она; заказ из корзины
    // говорит сам за себя своим составом.
    if (!$order['cart_items'] && $order['goal'] === '') {
        $errors[] = 'Выберите модель или задачу.';
    }

    if ($order['cart_items']) {
        if ($order['delivery_method'] === '') {
            $errors[] = 'Выберите способ получения заказа.';
        }

        /*
         * Нужен ли адрес, решает сам способ доставки в базе, а не поиск
         * слова «Самовывоз» в его названии. Стоило владельцу переименовать
         * способ в «Забрать в магазине» — и адрес переставал требоваться у
         * курьера тоже.
         */
        $needs_address = ($delivery_method_row['needs_address'] ?? 0) == 1;

        if ($needs_address && $order['delivery_city'] === '') {
            $errors[] = 'Укажите город доставки.';
        }
        if ($needs_address && $order['delivery_address'] === '') {
            $errors[] = 'Укажите улицу, дом и квартиру.';
        }
    }

    if (!$order['privacy']) {
        $errors[] = 'Подтвердите согласие с политикой обработки персональных данных.';
    }
    if (!$order['terms']) {
        $errors[] = 'Подтвердите согласие с пользовательским соглашением.';
    }

    return [$order, $errors];
}

function order_rows(array $order): array
{
    $type = $order['customer_type'] ?? 'individual';

    $rows = [
        ['Номер заявки', $order['id']],
        ['Дата', $order['created_at']],
        // Тип покупателя стоит первой строкой не случайно: от него зависит,
        // как менеджер отвечает — счётом или разговором.
        ['Тип покупателя', ORDER_CUSTOMER_TYPES[$type] ?? 'Физическое лицо'],
    ];

    if ($type === 'legal') {
        foreach (orders_legal_details($order) as $label => $value) {
            $rows[] = [$label, $value];
        }
    } else {
        $rows[] = ['Имя', $order['name'] !== '' ? $order['name'] : 'не указано'];
    }

    $rows[] = ['Телефон', $order['phone']];
    $rows[] = ['Электронная почта', $order['email'] !== '' ? $order['email'] : 'не указана'];
    $rows[] = ['Оплата', $order['payment'] !== '' ? $order['payment'] : 'не выбрана'];

    if (!$order['cart_items']) {
        $rows[] = ['Что подобрать', $order['category'] !== '' ? $order['category'] : 'не указано'];
        $rows[] = ['Модель или задача', $order['goal']];
        $rows[] = ['Количество', $order['quantity'] !== '' ? $order['quantity'] : 'не указано'];
    }

    $rows[] = ['Комментарий', $order['message'] !== '' ? $order['message'] : 'без комментария'];

    if ($order['cart_items']) {
        $delivery_price = $order['delivery_price'] === null ? 'по тарифам службы доставки' : format_money_value($order['delivery_price']);
        $order_total = $order['order_total'] === null ? format_money_value($order['cart_total']) . ' + доставка' : format_money_value($order['order_total']);

        $rows[] = ['Состав корзины', cart_summary_text($order['cart_items'])];
        $rows[] = ['Сумма товаров', format_money_value($order['cart_total'])];
        if (($order['discount'] ?? 0) > 0) {
            $rows[] = ['Скидка', format_money_value($order['discount'])];
        }
        $rows[] = ['Способ получения', $order['delivery_method']];
        $rows[] = ['Стоимость доставки', $delivery_price];
        if (($order['delivery_term'] ?? '') !== '') {
            $rows[] = ['Срок доставки', $order['delivery_term']];
        }
        if (($order['delivery_region'] ?? '') !== '') {
            $rows[] = ['Регион', $order['delivery_region']];
        }
        $rows[] = ['Город доставки', $order['delivery_city'] !== '' ? $order['delivery_city'] : 'самовывоз'];
        $rows[] = ['Адрес или пункт выдачи', $order['delivery_address'] !== '' ? $order['delivery_address'] : 'самовывоз'];
        $rows[] = ['Пожелание по доставке', $order['delivery_comment'] !== '' ? $order['delivery_comment'] : 'без пожеланий'];
        $rows[] = ['Итого по заказу', $order_total];
    }

    return $rows;
}

function build_text(array $order): string
{
    $lines = [];
    foreach (order_rows($order) as $row) {
        $lines[] = $row[0] . ': ' . $row[1];
    }
    return implode("\n", $lines);
}

function protect_orders_dir(string $dir): void
{
    $htaccess = rtrim($dir, '/\\') . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }

    $index = rtrim($dir, '/\\') . '/index.html';
    if (!is_file($index)) {
        @file_put_contents($index, '');
    }
}

function save_order_to_file(array $order, array $config, string $status, string $smtp_error = ''): bool
{
    $dir = clean_value($config['orders_dir'] ?? (__DIR__ . '/orders'));
    if ($dir === '') {
        $dir = __DIR__ . '/orders';
    }

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    protect_orders_dir($dir);

    $record = [
        'saved_at' => gmdate('c'),
        'status' => $status,
        'smtp_error' => $smtp_error,
        'order' => $order,
    ];
    $file = rtrim($dir, '/\\') . '/orders-' . gmdate('Y-m') . '.jsonl';
    return @file_put_contents($file, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX) !== false;
}

function save_order_to_error_log(array $order, string $status, string $smtp_error = ''): void
{
    $record = [
        'saved_at' => gmdate('c'),
        'status' => $status,
        'smtp_error' => $smtp_error,
        'order' => $order,
    ];
    error_log('XEON_ORDER ' . json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function build_html(array $order, string $intro): string
{
    $rows = '';
    foreach (order_rows($order) as $row) {
        $rows .= '<tr><td style="padding:8px 12px;border:1px solid #d8ded9;font-weight:700">' . e($row[0]) . '</td><td style="padding:8px 12px;border:1px solid #d8ded9">' . nl2br(e($row[1])) . '</td></tr>';
    }

    return '<div style="font-family:Arial,sans-serif;color:#111">'
        . '<h1>' . e($intro) . '</h1>'
        . '<p>Номер заявки: <strong>' . e($order['id']) . '</strong></p>'
        . '<table style="border-collapse:collapse">' . $rows . '</table>'
        . '</div>';
}

function encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function format_mailbox(string $email, string $name = ''): string
{
    return $name !== '' ? encode_header($name) . ' <' . $email . '>' : '<' . $email . '>';
}

function smtp_read($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    return $response;
}

function smtp_command($socket, ?string $command, array $expected_codes): string
{
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }

    $response = smtp_read($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expected_codes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }

    return $response;
}

function smtp_auth_login($socket, string $user, string $password): void
{
    smtp_command($socket, 'AUTH LOGIN', [334]);
    smtp_command($socket, base64_encode($user), [334]);
    smtp_command($socket, base64_encode($password), [235]);
}

function smtp_auth_plain($socket, string $user, string $password): void
{
    smtp_command($socket, 'AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $password), [235]);
}

function smtp_authenticate($socket, string $user, string $password, string $method): void
{
    $method = strtolower(trim($method));

    if ($method === 'login') {
        smtp_auth_login($socket, $user, $password);
        return;
    }

    if ($method === 'plain') {
        smtp_auth_plain($socket, $user, $password);
        return;
    }

    $plain_error = null;
    try {
        smtp_auth_plain($socket, $user, $password);
        return;
    } catch (Throwable $error) {
        $plain_error = $error;
    }

    try {
        smtp_auth_login($socket, $user, $password);
        return;
    } catch (Throwable $login_error) {
        throw new RuntimeException($login_error->getMessage() . ' AUTH PLAIN also failed: ' . $plain_error->getMessage());
    }
}

function smtp_message(array $config, string $to_email, string $to_name, string $reply_to, string $subject, string $text, string $html): string
{
    $from_email = clean_value($config['mail_from'] ?? $config['smtp_user'] ?? '');
    $from_name = clean_value($config['mail_from_name'] ?? '');
    $boundary = 'xeon-' . bin2hex(random_bytes(12));

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . format_mailbox($from_email, $from_name),
        'To: ' . format_mailbox($to_email, $to_name),
        'Subject: ' . encode_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    if ($reply_to !== '' && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . format_mailbox($reply_to);
    }

    $body = implode("\r\n", $headers) . "\r\n\r\n"
        . '--' . $boundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text)) . "\r\n\r\n"
        . '--' . $boundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . str_replace("\n", "\r\n", str_replace("\r\n", "\n", $html)) . "\r\n\r\n"
        . '--' . $boundary . "--\r\n";

    return preg_replace('/^\./m', '..', $body);
}

function smtp_send(array $config, string $to_email, string $to_name, string $reply_to, string $subject, string $text, string $html): void
{
    $host = clean_value($config['smtp_host'] ?? '');
    $port = (int)($config['smtp_port'] ?? 465);
    $secure = clean_value($config['smtp_secure'] ?? 'ssl');
    $user = clean_value($config['smtp_user'] ?? '');
    $password = clean_smtp_password($config['smtp_password'] ?? '');
    $auth_method = clean_value($config['smtp_auth'] ?? 'auto');
    $from_email = clean_value($config['mail_from'] ?? $user);

    if ($host === '' || $user === '' || $password === '' || $from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP-настройки не заполнены.');
    }

    /*
     * Сколько ждать почтовый сервер.
     *
     * Пока идёт отправка, покупатель смотрит на крутящийся индикатор. При
     * недоступном SMTP прежние двадцать секунд ожидания выглядели как
     * зависший сайт: человек жал «Отправить» второй раз и получался
     * задвоенный заказ. Заказ к этому моменту уже записан в базу, поэтому
     * ждать долго незачем — потерять его нельзя.
     *
     * Если почта у вас отвечает медленно, поднимите значения в
     * api/mail-config.php.
     */
    $connect_timeout = max(2, min(30, (int)($config['smtp_connect_timeout'] ?? 8)));
    $read_timeout = max(2, min(30, (int)($config['smtp_timeout'] ?? 10)));

    $target = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errno, $errstr, $connect_timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException('Не удалось подключиться к SMTP: ' . $errstr);
    }

    stream_set_timeout($socket, $read_timeout);
    smtp_command($socket, null, [220]);
    smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

    if ($secure === 'tls') {
        smtp_command($socket, 'STARTTLS', [220]);
        $crypto_method = defined('STREAM_CRYPTO_METHOD_TLS_CLIENT') ? STREAM_CRYPTO_METHOD_TLS_CLIENT : STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
        if (!stream_socket_enable_crypto($socket, true, $crypto_method)) {
            throw new RuntimeException('Не удалось включить TLS для SMTP.');
        }
        smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
    }

    smtp_authenticate($socket, $user, $password, $auth_method);
    smtp_command($socket, 'MAIL FROM:<' . $from_email . '>', [250]);
    smtp_command($socket, 'RCPT TO:<' . $to_email . '>', [250, 251]);
    smtp_command($socket, 'DATA', [354]);

    $message = smtp_message($config, $to_email, $to_name, $reply_to, $subject, $text, $html);
    fwrite($socket, $message . "\r\n.");
    smtp_command($socket, '', [250]);
    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);
}

function php_mail_send(array $config, string $to_email, string $to_name, string $reply_to, string $subject, string $text): void
{
    $from_email = clean_value($config['mail_from'] ?? $config['smtp_user'] ?? '');
    $from_name = clean_value($config['mail_from_name'] ?? '');

    if (!filter_var($from_email, FILTER_VALIDATE_EMAIL) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Некорректный отправитель или получатель для mail().');
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . format_mailbox($from_email, $from_name),
    ];

    if ($reply_to !== '' && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . format_mailbox($reply_to);
    }

    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $body = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text));
    $ok = @mail(format_mailbox($to_email, $to_name), encode_header($subject), $body, implode("\r\n", $headers));

    if (!$ok) {
        throw new RuntimeException('Функция mail() на хостинге вернула ошибку.');
    }
}

function telegram_order_text(array $order, string $site_name): string
{
    $type = $order['customer_type'] ?? 'individual';

    $lines = [
        'Новый заказ ' . $site_name,
        'Номер: ' . $order['id'],
        'Тип покупателя: ' . (ORDER_CUSTOMER_TYPES[$type] ?? 'Физическое лицо'),
        'Клиент: ' . ($order['name'] !== '' ? $order['name'] : 'не указан'),
        'Телефон: ' . $order['phone'],
        'Email: ' . ($order['email'] !== '' ? $order['email'] : 'не указана'),
        'Оплата: ' . ($order['payment'] !== '' ? $order['payment'] : 'не выбрана'),
    ];

    if ($type === 'legal') {
        $lines[] = '';
        $lines[] = 'Реквизиты:';
        foreach (orders_legal_details($order) as $label => $value) {
            $lines[] = '- ' . $label . ': ' . $value;
        }
        $lines[] = '';
    }

    if (!$order['cart_items']) {
        $lines[] = 'Модель/задача: ' . $order['goal'];
        if ($order['category'] !== '') {
            $lines[] = 'Что подобрать: ' . $order['category'];
        }
    }

    if ($order['cart_items']) {
        $lines[] = '';
        $lines[] = 'Корзина:';
        foreach ($order['cart_items'] as $item) {
            $lines[] = '- ' . $item['title'] . ': ' . $item['qty'] . ' шт. x ' . format_money_value($item['price']) . ' = ' . format_money_value($item['total']);
        }
        $lines[] = 'Товары: ' . format_money_value($order['cart_total']);
        if (($order['discount'] ?? 0) > 0) {
            $lines[] = 'Скидка: ' . format_money_value($order['discount']);
        }
        $lines[] = 'Доставка: ' . $order['delivery_method'];
        $lines[] = 'Стоимость доставки: ' . ($order['delivery_price'] === null ? 'по тарифам службы доставки' : format_money_value($order['delivery_price']));
        if (($order['delivery_term'] ?? '') !== '') {
            $lines[] = 'Срок: ' . $order['delivery_term'];
        }
        if (($order['delivery_region'] ?? '') !== '') {
            $lines[] = 'Регион: ' . $order['delivery_region'];
        }
        $lines[] = 'Город: ' . ($order['delivery_city'] !== '' ? $order['delivery_city'] : 'самовывоз');
        $lines[] = 'Адрес/ПВЗ: ' . ($order['delivery_address'] !== '' ? $order['delivery_address'] : 'самовывоз');
        $lines[] = 'Итого: ' . ($order['order_total'] === null ? format_money_value($order['cart_total']) . ' + доставка' : format_money_value($order['order_total']));
    } elseif ($order['quantity'] !== '') {
        $lines[] = 'Количество: ' . $order['quantity'];
    }

    if ($order['message'] !== '') {
        $lines[] = '';
        $lines[] = 'Комментарий: ' . $order['message'];
    }

    $text = implode("\n", $lines);
    $text_length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($text_length > 3900) {
        $text = function_exists('mb_substr') ? mb_substr($text, 0, 3890, 'UTF-8') . "\n..." : substr($text, 0, 3890) . "\n...";
    }

    return $text;
}

function telegram_send(array $config, string $text): void
{
    if (($config['telegram_enabled'] ?? false) !== true) {
        return;
    }

    $token = clean_value($config['telegram_bot_token'] ?? '');
    $chat_id = clean_value($config['telegram_chat_id'] ?? '');
    if ($token === '' || $chat_id === '') {
        throw new RuntimeException('Telegram-настройки не заполнены.');
    }
    if (!preg_match('/^[0-9]+:[A-Za-z0-9_-]+$/', $token)) {
        throw new RuntimeException('Некорректный telegram_bot_token.');
    }

    $payload = http_build_query([
        'chat_id' => $chat_id,
        'text' => $text,
        'disable_web_page_preview' => 'true',
    ], '', '&');
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $connect_timeout = max(1, min(10, (int)($config['telegram_connect_timeout'] ?? 2)));
    $timeout = max($connect_timeout, min(15, (int)($config['telegram_timeout'] ?? 4)));

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        $curl_options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connect_timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curl_options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($curl, $curl_options);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Telegram API error: ' . ($error !== '' ? $error : (string)$response));
        }
        telegram_assert_ok((string)$response);
        return;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $last_error = error_get_last();
        throw new RuntimeException('Telegram API request failed: ' . ($last_error['message'] ?? 'unknown error'));
    }
    telegram_assert_ok((string)$response);
}

function telegram_assert_ok(string $response): void
{
    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Telegram API returned invalid JSON: ' . substr($response, 0, 200));
    }
    if (($data['ok'] ?? false) !== true) {
        throw new RuntimeException('Telegram API error: ' . clean_value($data['description'] ?? $response));
    }
}

function telegram_notify_safely(array $config, array $order, string $site_name): string
{
    if (($config['telegram_enabled'] ?? false) !== true) {
        return '';
    }

    try {
        telegram_send($config, telegram_order_text($order, $site_name));
        return '';
    } catch (Throwable $error) {
        $message = $error->getMessage();
        error_log('COMP_UTER_TELEGRAM ' . $order['id'] . ' ' . $message);
        return $message;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Метод не поддерживается.'], 405);
}

/*
 * Настройки почты необязательны.
 *
 * Раньше без файла api/mail-config.php форма отвечала ошибкой 500 — то есть
 * сразу после установки, пока владелец не завёл почту, каждый заказ
 * пропадал. Теперь заказ всё равно сохраняется в базу и виден в панели, а
 * ненастроенная почта отмечается как «письмо не ушло».
 */
$config_path = __DIR__ . '/mail-config.php';
$config = is_file($config_path) ? require $config_path : null;
$mail_configured = is_array($config);

if (!$mail_configured) {
    $config = [];
}

// Cap the request body: the order payload is a few KB at most, so anything
// larger is either a bug or an attempt to make the server chew through junk.
$input = file_get_contents('php://input', false, null, 0, 256 * 1024);
$raw = json_decode((string)$input, true);
if (!is_array($raw)) {
    json_response(['ok' => false, 'message' => 'Некорректный формат заявки.'], 400);
}

$spam = spam_reason($raw, $config);
if ($spam !== '') {
    error_log('COMP_UTER_SPAM ' . $spam . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    if ($spam === 'rate_limited') {
        json_response([
            'ok' => false,
            'message' => 'Слишком много заявок с этого адреса. Попробуйте позже или позвоните нам.',
        ], 429);
    }
    // Honeypot / too-fast: answer like a success so bots do not learn the
    // rule and start retrying, but never deliver or store the message.
    json_response(['ok' => true, 'orderId' => create_order_id()]);
}

[$order, $errors] = normalize_order($raw);
if ($errors) {
    json_response(['ok' => false, 'message' => implode(' ', $errors)], 400);
}

/*
 * Заказ записывается в базу ДО отправки писем.
 *
 * Порядок именно такой: почтовый сервер может не ответить, а заказ терять
 * нельзя. Раньше единственным следом был файл orders-YYYY-MM.jsonl, который
 * никто не открывал, — теперь заказ виден в панели сразу.
 */
$db_order_id = orders_store([
    'number'         => $order['id'],
    'customer_type'  => $order['customer_type'],
    'name'           => $order['name'],
    'first_name'     => $order['first_name'],
    'last_name'      => $order['last_name'],
    'phone'          => $order['phone'],
    'email'          => $order['email'],
    'company'        => $order['company'],
    'inn'            => $order['inn'],
    'kpp'            => $order['kpp'],
    'ogrn'           => $order['ogrn'],
    'legal_address'  => $order['legal_address'],
    'bank_name'      => $order['bank_name'],
    'bik'            => $order['bik'],
    'bank_account'   => $order['bank_account'],
    'corr_account'   => $order['corr_account'],
    'region'         => $order['delivery_region'],
    'city'           => $order['delivery_city'],
    'address'        => trim($order['delivery_address'] . "\n" . $order['delivery_comment']),
    'delivery_code'  => $order['delivery_code'],
    'delivery_title' => $order['delivery_method'],
    'delivery_price' => $order['delivery_price'],
    'delivery_term'  => $order['delivery_term'],
    'payment'        => $order['payment'],
    'payment_code'   => $order['payment_code'],
    'goal'           => $order['goal'],
    'comment'        => $order['message'],
    'items_total'    => $order['cart_total'],
    'discount'       => $order['discount'],
    'total'          => $order['order_total'] ?? $order['cart_total'],
    'source'         => $order['cart_items'] ? 'cart' : 'form',
], $order['cart_items']);

$to_email = clean_value($config['mail_to'] ?? '');
$to_name = clean_value($config['mail_to_name'] ?? '');

// Заказ уже в базе. Если отправлять некуда, это повод пометить заказ, а не
// отказать покупателю, который со своей стороны всё сделал правильно.
if (!$mail_configured || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    $why = $mail_configured ? 'mail_recipient_invalid' : 'mail_not_configured';
    orders_set_mail_status($db_order_id, $why);
    error_log('COMP_UTER_ORDER ' . $order['id'] . ' ' . $why);

    json_response($db_order_id
        ? ['ok' => true, 'orderId' => $order['id'], 'mailStatus' => $why]
        : ['ok' => false, 'message' => 'Заявку не удалось сохранить. Позвоните нам, пожалуйста.'],
        $db_order_id ? 200 : 500);
}

try {
    $site_name = clean_value($config['site_name'] ?? 'Comp-Uter');
    $owner_text = "Новая заявка с сайта {$site_name}.\n\n" . build_text($order);
    $client_text = "Ваша заявка принята. Номер заявки: {$order['id']}.\n\nМы свяжемся с вами в ближайшее время.\n\n" . build_text($order);

    smtp_send(
        $config,
        $to_email,
        $to_name,
        $order['email'],
        'Новая заявка ' . $order['id'] . ' — ' . $site_name,
        $owner_text,
        build_html($order, 'Новая заявка с сайта ' . $site_name)
    );

    /*
     * Письмо покупателю — только если он оставил почту.
     *
     * Почта теперь необязательна: обязателен телефон, по нему менеджер и
     * перезвонит. Отправлять письмо в пустоту нельзя — SMTP ответит отказом,
     * и заказ, который на самом деле принят, был бы помечен как неудачный.
     */
    if (filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
        smtp_send(
            $config,
            $order['email'],
            $order['name'],
            $to_email,
            'Ваша заявка ' . $order['id'] . ' принята — ' . $site_name,
            $client_text,
            build_html($order, 'ЗАЯВКА ПРИНЯТА')
        );
    }

    $telegram_error = telegram_notify_safely($config, $order, $site_name);
    $mail_status = $telegram_error === '' ? 'mail_sent' : 'mail_sent_telegram_failed';
    orders_set_mail_status($db_order_id, $mail_status);
    save_order_to_file($order, $config, $mail_status, $telegram_error);
} catch (Throwable $error) {
    $smtp_error = $error->getMessage();
    $fallback_error = '';

    if (($config['php_mail_fallback'] ?? true) === true) {
        try {
            php_mail_send(
                $config,
                $to_email,
                $to_name,
                $order['email'],
                'Новая заявка ' . $order['id'] . ' — ' . $site_name,
                $owner_text
            );
            if (filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
                php_mail_send(
                    $config,
                    $order['email'],
                    $order['name'],
                    $to_email,
                    'Ваша заявка ' . $order['id'] . ' принята — ' . $site_name,
                    $client_text
                );
            }
            $telegram_error = telegram_notify_safely($config, $order, $site_name);
            $mail_status = $telegram_error === '' ? 'mail_sent_via_php_mail' : 'mail_sent_via_php_mail_telegram_failed';
            orders_set_mail_status($db_order_id, $mail_status);
            save_order_to_file($order, $config, $mail_status, trim($smtp_error . ' ' . $telegram_error));
            json_response(['ok' => true, 'orderId' => $order['id'], 'mailStatus' => 'php_mail_fallback']);
        } catch (Throwable $fallback) {
            $fallback_error = $fallback->getMessage();
        }
    }

    $telegram_error = telegram_notify_safely($config, $order, $site_name);
    $mail_status = $telegram_error === '' ? 'smtp_failed' : 'smtp_failed_telegram_failed';
    orders_set_mail_status($db_order_id, $mail_status);
    $saved = save_order_to_file($order, $config, $mail_status, trim($smtp_error . ' ' . $fallback_error . ' ' . $telegram_error));
    if (!$saved) {
        save_order_to_error_log($order, 'smtp_failed_not_saved', trim($smtp_error . ' ' . $fallback_error));
    }

    if (($config['accept_without_smtp'] ?? true) === true) {
        json_response([
            'ok' => true,
            'orderId' => $order['id'],
            'mailStatus' => $saved ? 'smtp_failed_saved' : 'smtp_failed_logged',
        ]);
    }

    json_response(['ok' => false, 'message' => 'Не получилось отправить письмо через SMTP: ' . $smtp_error], 500);
}

json_response(['ok' => true, 'orderId' => $order['id']]);
