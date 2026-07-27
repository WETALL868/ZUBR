<?php

return [
    // SMTP is used for sending order emails. IMAP is only for reading inbox mail.
    'smtp_host' => 'smtp.mail.ru',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl', // ssl for port 465, tls for port 587, none without encryption
    'smtp_auth' => 'auto', // auto tries AUTH PLAIN first and AUTH LOGIN second

    // Replace these values on your hosting with the real mailbox credentials.
    'smtp_user' => 'info@comp-uter.ru',
    'smtp_password' => 'LNGj2rxs6RcDAZ9rmpe6',

    // The sender must usually match smtp_user.
    'mail_from' => 'info@comp-uter.ru',
    'mail_from_name' => 'Comp-Uter',

    // Where new order notifications are sent.
    'mail_to' => 'info@comp-uter.ru',
    'mail_to_name' => 'Comp-Uter',

    'site_name' => 'Comp-Uter',
	'mail_test_token' => 'xeon-test-2026',

    // Set any private value here and use it in /api/mail-test.php?token=YOUR_VALUE.
    // Do not leave CHANGE_ME_TEST_TOKEN on a public site.
    'mail_test_token' => 'mikhailovskiy-mail-test-789',

    // If SMTP temporarily fails, the order is still accepted and saved on hosting.
    'accept_without_smtp' => true,
    'orders_dir' => __DIR__ . '/orders',

    // If SMTP rejects authorization, try the hosting's built-in mail() function.
    'php_mail_fallback' => true,
    
    	// Вот сюда добавьте Telegram:
    'telegram_enabled' => false,
    'telegram_bot_token' => '8902281455:AAG_QVn7TxMbTT3wooPbvJwUPEblRq3qZqU',
    'telegram_chat_id' => '161290058',
    'telegram_connect_timeout' => 2,
    'telegram_timeout' => 4,
    'telegram_test_key' => 'test123',
];