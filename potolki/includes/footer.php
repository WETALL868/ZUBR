<?php
/**
 * Подвал.
 *
 * В подвале только основные направления и переход на географический хаб.
 * Сотни ссылок на районы и города здесь не выводятся — для этого есть
 * хабы и карта сайта.
 */

$phone = config('contacts.phone');
$consent = config('consent');

$footerLinks = static function (string $set, string $dir, int $limit = 6): array {
    $items = [];
    foreach (content($set) as $slug => $item) {
        if (($item['enabled'] ?? true) === false) {
            continue;
        }
        $items[] = ['url' => $dir . $slug . '/', 'title' => $item['menu_title'] ?? $item['title']];
        if (count($items) >= $limit) {
            break;
        }
    }
    return $items;
};
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col footer-brand">
                <span class="logo">
                    <span class="logo__mark" aria-hidden="true"></span>
                    <span>
                        <?= e((string) config('brand.name')) ?>
                        <span class="logo__sub">натяжные потолки</span>
                    </span>
                </span>

                <div class="footer-contacts">
                    <?php if (filled($phone['raw'])): ?>
                        <strong><a href="tel:<?= e((string) $phone['raw']) ?>"><?= e((string) $phone['display']) ?></a></strong>
                    <?php endif; ?>
                    <?php if (filled(config('contacts.email'))): ?>
                        <a href="mailto:<?= e((string) config('contacts.email')) ?>"><?= e((string) config('contacts.email')) ?></a>
                    <?php endif; ?>
                    <span><?= e((string) config('contacts.work_hours')) ?></span>
                    <?php if (filled(config('contacts.office_address'))): ?>
                        <span><?= e((string) config('contacts.office_address')) ?></span>
                    <?php endif; ?>
                </div>

                <p style="margin-top:1.25rem">
                    <a class="btn btn--outline-light btn--small" href="<?= e(site_url('/measurement/')) ?>">Вызвать замерщика</a>
                </p>
            </div>

            <div class="footer-col">
                <h3>Потолки</h3>
                <ul>
                    <?php foreach ($footerLinks('ceilings', '/ceilings/') as $item): ?>
                        <li><a href="<?= e(site_url($item['url'])) ?>"><?= e($item['title']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= e(site_url('/ceilings/')) ?>">Все виды полотен</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Решения</h3>
                <ul>
                    <?php foreach ($footerLinks('systems', '/systems/', 5) as $item): ?>
                        <li><a href="<?= e(site_url($item['url'])) ?>"><?= e($item['title']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= e(site_url('/lighting/')) ?>">Освещение</a></li>
                    <li><a href="<?= e(site_url('/rooms/')) ?>">По помещениям</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Компания</h3>
                <ul>
                    <li><a href="<?= e(site_url('/about/')) ?>">О компании</a></li>
                    <li><a href="<?= e(site_url('/prices/')) ?>">Цены</a></li>
                    <li><a href="<?= e(site_url('/guarantees/')) ?>">Гарантии и документы</a></li>
                    <li><a href="<?= e(site_url('/payment/')) ?>">Оплата</a></li>
                    <li><a href="<?= e(site_url('/blog/')) ?>">Полезные материалы</a></li>
                    <li><a href="<?= e(site_url('/faq/')) ?>">Вопросы и ответы</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Где работаем</h3>
                <ul>
                    <li><a href="<?= e(site_url('/moskva/')) ?>">Москва</a></li>
                    <li><a href="<?= e(site_url('/moskovskaya-oblast/')) ?>">Московская область</a></li>
                    <li><a href="<?= e(site_url('/geography/')) ?>">Все районы и города</a></li>
                    <li><a href="<?= e(site_url('/sitemap/')) ?>">Карта сайта</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                <?php if (filled(config('legal.operator_name'))): ?>
                    <?= e((string) config('legal.operator_name')) ?><?php if (filled(config('legal.inn'))): ?>, ИНН <?= e((string) config('legal.inn')) ?><?php endif; ?>
                <?php else: ?>
                    <?= e((string) config('brand.name')) ?>
                <?php endif; ?>
                · © <?= date('Y') ?>
                <br>
                Цены на сайте не являются публичной офертой: окончательная стоимость определяется после замера.
            </div>

            <div class="footer-legal">
                <a href="<?= e(site_url((string) $consent['privacy_policy']['url'])) ?>">Политика обработки данных</a>
                <a href="<?= e(site_url((string) $consent['personal_data']['url'])) ?>">Согласие на обработку</a>
                <a href="<?= e(site_url((string) $consent['cookie_policy']['url'])) ?>">Cookies</a>
                <a href="<?= e(site_url((string) $consent['user_agreement']['url'])) ?>">Соглашение</a>
                <a href="<?= e(site_url('/legal/requisites/')) ?>">Реквизиты</a>
                <a href="#" data-cookie-reopen>Настройки cookie</a>
            </div>
        </div>
    </div>
</footer>

<!-- Нижняя панель действий: только на маленьких экранах -->
<div class="action-bar" role="navigation" aria-label="Быстрые действия">
    <?php if (filled($phone['raw'])): ?>
        <a href="tel:<?= e((string) $phone['raw']) ?>">Позвонить</a>
    <?php endif; ?>
    <a class="is-primary" href="<?= e(site_url('/calculator/')) ?>">Рассчитать</a>
</div>

<dialog id="callback-dialog" class="cookie" style="position:fixed;inset:auto;max-width:32rem;">
    <button class="mobile-menu__close" type="button" data-dialog-close aria-label="Закрыть" style="float:right">×</button>
    <?php render_lead_form([
        'form'   => 'callback',
        'id'     => 'callback-form',
        'title'  => 'Заказать звонок',
        'text'   => 'Перезвоним в рабочее время, ответим на вопросы и поможем с расчётом.',
        'fields' => ['name', 'phone'],
        'button' => 'Жду звонка',
    ]); ?>
</dialog>

<?php if (config('features.cookie_banner')): $banner = $consent['cookie_banner']; ?>
<div class="cookie" id="cookie-banner" data-version="<?= e((string) $banner['version']) ?>" hidden>
    <h2>Cookies и статистика</h2>
    <p>
        Мы используем технические cookies, чтобы сайт работал: они запоминают защиту форм и ваш выбор.
        Аналитические и маркетинговые скрипты не запускаются, пока вы не разрешите — отказ ничего не ломает.
        Подробнее: <a href="<?= e(site_url((string) $consent['cookie_policy']['url'])) ?>">политика cookies</a>.
    </p>

    <div class="cookie__actions">
        <button class="btn btn--accent" type="button" data-cookie-all>Принять все</button>
        <button class="btn btn--ghost" type="button" data-cookie-necessary>Только необходимые</button>
        <button class="btn btn--ghost" type="button" data-cookie-settings aria-expanded="false">Настроить</button>
    </div>

    <div class="cookie__settings" data-cookie-settings-box hidden>
        <?php foreach ($banner['categories'] as $key => $category): ?>
            <label class="consent">
                <input type="checkbox" name="<?= e($key) ?>"
                       <?= $category['required'] ? 'checked disabled' : '' ?>>
                <span>
                    <strong><?= e($category['title']) ?></strong><?= $category['required'] ? ' — всегда включены' : '' ?>
                    <span class="consent__version"><?= e($category['text']) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
        <button class="btn btn--small" type="button" data-cookie-save>Сохранить выбор</button>
    </div>
</div>
<?php endif; ?>
