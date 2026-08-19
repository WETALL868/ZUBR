<?php
/** Работы. Пока кейсов нет — показываем честную заглушку, а не чужие фото. */

seo([
    'title'       => 'Выполненные работы — натяжные потолки | ' . config('brand.name'),
    'description' => 'Реальные объекты с площадью, составом работ, сроками и стоимостью. Раздел пополняется по мере фотосъёмки выполненных заказов.',
    'h1'          => 'Выполненные работы',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Работы', 'url' => '/portfolio/'],
    ],
]);

$cases = array_filter(content('portfolio'), static fn (array $c): bool => ($c['status'] ?? 'published') === 'published');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Объекты</div>
                <h1>Выполненные работы</h1>
            </div>
            <p class="lead">
                Каждый кейс — с площадью, составом работ, сроком и фактической суммой договора.
                Без этих данных «портфолио» превращается в набор картинок из интернета.
            </p>
        </div>

        <?php if ($cases === []): ?>
            <div class="notice notice--empty">
                <strong>Раздел наполняется реальными объектами.</strong>
                Мы не публикуем чужие фотографии и не пишем «более 5000 выполненных заказов» без подтверждения:
                проверить такие цифры невозможно, а значит, они ничего не значат.
                Как только появятся снятые объекты, здесь будут работы с площадью, составом и сроками.
            </div>

            <div class="grid grid--3" style="margin-top:2.5rem">
                <a class="card" href="<?= e(site_url('/calculator/')) ?>">
                    <span class="card__title">Посмотреть, как считается смета</span>
                    <p class="card__text">Калькулятор показывает каждую строку: полотно, профиль, свет, работы.</p>
                </a>
                <a class="card" href="<?= e(site_url('/prices/')) ?>">
                    <span class="card__title">Открыть прайс целиком</span>
                    <p class="card__text">Полный список позиций с ценами за м², погонный метр и штуку.</p>
                </a>
                <a class="card" href="<?= e(site_url('/systems/teneviye/')) ?>">
                    <span class="card__title">Разобраться в решениях</span>
                    <p class="card__text">Чем теневой профиль отличается от бесщелевого и когда он оправдан.</p>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($cases as $slug => $case): ?>
                    <a class="card" href="<?= e(site_url('/portfolio/' . $slug . '/')) ?>">
                        <?php if (!empty($case['images'][0]['src'])): ?>
                            <span class="card__media">
                                <img src="<?= e((string) $case['images'][0]['src']) ?>" alt="<?= e((string) $case['images'][0]['alt']) ?>" loading="lazy" width="600" height="450">
                            </span>
                        <?php endif; ?>
                        <span class="card__title"><?= e($case['title']) ?></span>
                        <p class="card__text"><?= e((string) ($case['lead'] ?? '')) ?></p>
                        <span class="card__meta">
                            <span><?= e((string) $case['city']) ?></span>
                            <span><?= e((string) $case['area']) ?> м²</span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Обсудить ваш объект';
$ctaText  = 'Опишите помещение и задачу — предложим решение и посчитаем смету по вашим размерам.';
include APP_ROOT . '/templates/partials/cta.php';
?>
