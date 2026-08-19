<?php
/**
 * Страница после отправки формы без JavaScript.
 * Не индексируется: это служебный экран, а не посадочная страница.
 */

session_start_safe();

$flash = $_SESSION['lead_flash'] ?? null;
unset($_SESSION['lead_flash']);

seo([
    'title'       => 'Заявка принята — ' . config('brand.name'),
    'description' => 'Заявка принята, мы свяжемся с вами в рабочее время.',
    'h1'          => 'Заявка принята',
    'robots'      => 'noindex, nofollow',
    'breadcrumbs' => [],
]);
?>

<section class="section">
    <div class="container container--narrow">
        <?php if ($flash !== null): ?>
            <div class="eyebrow">Готово</div>
            <h1>Заявка принята</h1>
            <p class="lead" style="margin-top:1.5rem">
                Номер заявки — <strong><?= e((string) $flash['id']) ?></strong>.
                Мы свяжемся с вами в рабочее время: <?= e((string) config('contacts.work_hours')) ?>.
                <?php if (!empty($flash['email'])): ?>
                    Копия расчёта отправлена вам на почту.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="eyebrow">Формы</div>
            <h1>Заявка не найдена</h1>
            <p class="lead" style="margin-top:1.5rem">
                Похоже, вы открыли эту страницу напрямую. Оставить заявку можно с любой страницы сайта
                или по телефону.
            </p>
        <?php endif; ?>

        <div class="btn-row" style="margin-top:2rem">
            <a class="btn btn--accent" href="<?= e(site_url('/')) ?>">На главную</a>
            <a class="btn btn--ghost" href="<?= e(site_url('/calculator/')) ?>">Посчитать смету</a>
            <?php if (filled(config('contacts.phone.raw'))): ?>
                <a class="btn btn--ghost" href="tel:<?= e((string) config('contacts.phone.raw')) ?>">Позвонить</a>
            <?php endif; ?>
        </div>

        <div class="notice" style="margin-top:2.5rem">
            <strong>Что дальше.</strong>
            Менеджер уточнит задачу и предложит время замера. Замер бесплатный в зоне выезда
            и ни к чему не обязывает. Отозвать согласие на обработку данных можно в любой момент —
            см. <a href="<?= e(site_url('/legal/privacy-policy/')) ?>">политику</a>.
        </div>
    </div>
</section>
