SMTP setup for the order form
=============================

Edit this file on hosting:

  api/mail-config.php

Important fields:

  smtp_host      SMTP server, for example smtp.yandex.ru
  smtp_port      465 for SSL, 587 for TLS
  smtp_secure    ssl or tls
  smtp_user      mailbox login
  smtp_password  mailbox app password
  mail_from      sender email, usually the same as smtp_user
  mail_to        email that receives new order notifications

IMAP is not needed for this form. SMTP sends mail, IMAP reads incoming mail.

The site sends two emails:

  1. Order notification to mail_to.
  2. Order copy to the customer email from the form.

The hosting must support PHP with OpenSSL sockets.

Telegram notifications
======================

After uploading the Telegram-enabled api/order.php, add these fields to
api/mail-config.php:

  telegram_enabled   true or false
  telegram_bot_token token from @BotFather
  telegram_chat_id   chat id where order notifications should be sent

Example:

  'telegram_enabled' => true,
  'telegram_bot_token' => '1234567890:AAxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  'telegram_chat_id' => '123456789',
  'telegram_connect_timeout' => 2,
  'telegram_timeout' => 4,

For a group chat, add the bot to the group, send any message to the group, then
use getUpdates to find the group chat_id. Group chat_id is usually negative.

Telegram test
=============

For a one-time hosting-side test, add:

  'telegram_test_key' => 'any-secret-word',

Then open:

  https://your-domain.ru/api/telegram-test.php?key=any-secret-word

If the test works, delete api/telegram-test.php from hosting or remove
telegram_test_key from api/mail-config.php.
