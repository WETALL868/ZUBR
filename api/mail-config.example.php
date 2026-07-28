<?php

/**
 * Copy this file to api/mail-config.php on the hosting and fill in the values.
 *
 *     cp api/mail-config.example.php api/mail-config.php
 *
 * api/mail-config.php holds credentials and is intentionally NOT part of the
 * deployment archive, so redeploying the site never overwrites it. The root
 * .htaccess also denies direct HTTP access to it.
 *
 * Without this file the order form answers 500 and no lead is delivered —
 * check it first if orders stop arriving.
 */

return [
    // --------------------------------------------------------------- SMTP
    'smtp_host'     => 'smtp.yandex.ru',
    'smtp_port'     => 465,          // 465 = ssl, 587 = tls
    'smtp_secure'   => 'ssl',        // 'ssl' | 'tls'
    'smtp_user'     => 'info@comp-uter.ru',
    'smtp_password' => '',           // mailbox app password
    'smtp_auth'     => 'auto',       // 'auto' | 'login' | 'plain'

    // ------------------------------------------------------------ Senders
    'mail_from'      => 'info@comp-uter.ru',
    'mail_from_name' => 'Comp-Uter',
    'mail_to'        => 'info@comp-uter.ru',   // who receives new orders
    'mail_to_name'   => 'Отдел продаж Comp-Uter',
    'site_name'      => 'Comp-Uter',

    // ----------------------------------------------------------- Fallback
    // If SMTP fails, try the hosting's mail() function, and still accept the
    // order (it is written to api/orders/*.jsonl) rather than losing the lead.
    'php_mail_fallback'   => true,
    'accept_without_smtp' => true,
    'orders_dir'          => __DIR__ . '/orders',

    // --------------------------------------------------------- Anti-spam
    // Reject submissions that arrive faster than a human could fill the form,
    // and cap how many orders one IP can send per hour. Set either to 0 to
    // disable that particular check.
    'min_fill_seconds'    => 3,
    'rate_limit_per_hour' => 8,

    // --------------------------------------------------------- Telegram
    'telegram_enabled'   => false,
    'telegram_bot_token' => '',
    'telegram_chat_id'   => '',

    // ------------------------------------------- Telegram (необязательно)
    'telegram_connect_timeout' => 5,   // секунд на установку соединения
    'telegram_timeout'         => 10,  // секунд на ответ Telegram

    // ------------------------------------------------ Проверочные ключи
    // Нужны только для ручной проверки отправки писем. Оставьте пустыми,
    // если не пользуетесь: непустое значение включает проверочный режим.
    'mail_test_token'  => '',
    'telegram_test_key' => '',
];
