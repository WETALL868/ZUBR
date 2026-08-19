<?php
/**
 * Техническая ошибка.
 *
 * Посетитель не должен видеть ни трассировки, ни текста исключения:
 * подробности уходят в storage/logs, здесь — только понятное сообщение
 * и способ связаться.
 */

$phone = config('contacts.phone');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Сервис временно недоступен</title>
    <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
</head>
<body>
<main class="section">
    <div class="container container--narrow">
        <div class="eyebrow">Ошибка сервера</div>
        <h1>Сайт временно недоступен</h1>
        <p class="lead" style="margin-top:1.5rem">
            Мы уже знаем о проблеме и разбираемся. Заявку можно оставить по телефону —
            менеджер примет её вручную.
        </p>

        <div class="btn-row" style="margin-top:2rem">
            <?php if (filled($phone['raw'])): ?>
                <a class="btn btn--accent" href="tel:<?= e((string) $phone['raw']) ?>"><?= e((string) $phone['display']) ?></a>
            <?php endif; ?>
            <?php if (filled(config('contacts.email'))): ?>
                <a class="btn btn--ghost" href="mailto:<?= e((string) config('contacts.email')) ?>">Написать на почту</a>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
