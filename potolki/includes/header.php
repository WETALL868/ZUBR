<?php
/**
 * Шапка сайта.
 *
 * В основной навигации семь пунктов — больше в шапке не помещается
 * без потери смысла. Всё остальное (услуги, помещения, география,
 * материалы) доступно через хабы, подвал и карту сайта.
 */

$phone = config('contacts.phone');
$current = request_path();

$nav = [
    ['url' => '/ceilings/',  'title' => 'Виды потолков'],
    ['url' => '/systems/',   'title' => 'Решения'],
    ['url' => '/lighting/',  'title' => 'Освещение'],
    ['url' => '/prices/',    'title' => 'Цены'],
    ['url' => '/portfolio/', 'title' => 'Работы'],
    ['url' => '/about/',     'title' => 'О компании'],
    ['url' => '/contacts/',  'title' => 'Контакты'],
];

/** Первые несколько элементов набора — для мобильного меню. */
$menuItems = static function (string $set, string $dir, int $limit = 8): array {
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
<div class="topbar">
    <div class="container topbar__inner">
        <span class="topbar__geo">
            <?= e((string) config('geo.base_city')) ?> и <?= e((string) config('geo.region')) ?>
            · <a href="<?= e(site_url('/geography/')) ?>">где работаем</a>
        </span>
        <span>
            <?= e((string) config('contacts.work_hours')) ?>
            <?php if (filled(config('contacts.email'))): ?>
                · <a href="mailto:<?= e((string) config('contacts.email')) ?>"><?= e((string) config('contacts.email')) ?></a>
            <?php endif; ?>
        </span>
    </div>
</div>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="logo" href="<?= e(site_url('/')) ?>" aria-label="<?= e((string) config('brand.name')) ?> — на главную">
            <span class="logo__mark" aria-hidden="true"></span>
            <span>
                <?= e((string) config('brand.name')) ?>
                <span class="logo__sub">натяжные потолки</span>
            </span>
        </a>

        <nav class="nav" aria-label="Основные разделы">
            <ul class="nav__list">
                <?php foreach ($nav as $item): ?>
                    <li>
                        <a class="nav__link"
                           href="<?= e(site_url($item['url'])) ?>"
                           <?= str_starts_with($current, $item['url']) && $item['url'] !== '/' ? 'aria-current="page"' : '' ?>>
                            <?= e($item['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if (filled($phone['raw']) ): ?>
                <a class="header-phone" href="tel:<?= e((string) $phone['raw']) ?>"><?= e((string) $phone['display']) ?></a>
            <?php endif; ?>
            <a class="btn btn--accent btn--small" href="<?= e(site_url('/calculator/')) ?>">Рассчитать стоимость</a>
            <button class="burger" type="button" data-menu-open aria-label="Открыть меню" aria-controls="mobile-menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Меню сайта">
    <div class="mobile-menu__head">
        <a class="logo" href="<?= e(site_url('/')) ?>">
            <span class="logo__mark" aria-hidden="true"></span>
            <span><?= e((string) config('brand.name')) ?></span>
        </a>
        <button class="mobile-menu__close" type="button" data-menu-close aria-label="Закрыть меню">×</button>
    </div>

    <div class="mobile-menu__body">
        <a class="mobile-menu__link" href="<?= e(site_url('/calculator/')) ?>">Рассчитать стоимость</a>
        <a class="mobile-menu__link" href="<?= e(site_url('/measurement/')) ?>">Вызвать замерщика</a>

        <?php
        $groups = [
            'Виды потолков' => $menuItems('ceilings', '/ceilings/'),
            'Решения и конструкции' => $menuItems('systems', '/systems/'),
            'Освещение' => $menuItems('lighting', '/lighting/'),
            'По помещениям' => $menuItems('rooms', '/rooms/'),
            'Услуги' => $menuItems('services', '/services/'),
        ];
        ?>

        <?php foreach ($groups as $title => $items): ?>
            <?php if ($items === []) { continue; } ?>
            <details>
                <summary><?= e($title) ?></summary>
                <ul class="mobile-menu__sub">
                    <?php foreach ($items as $item): ?>
                        <li><a href="<?= e(site_url($item['url'])) ?>"><?= e($item['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>

        <details>
            <summary>Где работаем</summary>
            <ul class="mobile-menu__sub">
                <li><a href="<?= e(site_url('/moskva/')) ?>">Москва</a></li>
                <li><a href="<?= e(site_url('/moskva/okruga/')) ?>">Округа Москвы</a></li>
                <li><a href="<?= e(site_url('/moskovskaya-oblast/')) ?>">Московская область</a></li>
                <li><a href="<?= e(site_url('/moskovskaya-oblast/goroda/')) ?>">Города Подмосковья</a></li>
            </ul>
        </details>

        <?php foreach ($nav as $item): ?>
            <a class="mobile-menu__link" href="<?= e(site_url($item['url'])) ?>"><?= e($item['title']) ?></a>
        <?php endforeach; ?>

        <a class="mobile-menu__link" href="<?= e(site_url('/blog/')) ?>">Полезные материалы</a>

        <?php if (filled($phone['raw'])): ?>
            <p style="margin-top:1.5rem">
                <a class="btn btn--accent btn--block" href="tel:<?= e((string) $phone['raw']) ?>">Позвонить <?= e((string) $phone['display']) ?></a>
            </p>
        <?php endif; ?>
    </div>
</div>
