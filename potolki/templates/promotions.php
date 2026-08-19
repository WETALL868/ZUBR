<?php
/**
 * Акции.
 *
 * Модуль выключен по умолчанию. Включайте только при реально действующих
 * условиях: у каждой акции должны быть срок, условия и ограничения.
 * «Скидка 50 % всегда» — это не акция, а завышенная базовая цена.
 */

seo([
    'title'       => 'Акции и специальные условия — ' . config('brand.name'),
    'description' => 'Действующие акции с условиями и сроками.',
    'h1'          => 'Акции',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Акции', 'url' => '/promotions/'],
    ],
]);

$promotions = content('promotions');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Условия</div>
                <h1>Акции</h1>
            </div>
            <p class="lead">У каждой акции указан срок и условия. Если условий нет — это не акция, а реклама.</p>
        </div>

        <?php if ($promotions === []): ?>
            <div class="notice notice--empty">
                <strong>Сейчас акций нет.</strong>
                Мы не держим «вечную скидку 50 %»: чтобы её дать, пришлось бы сначала завысить цену.
                Актуальные цены — в <a href="<?= e(site_url('/prices/')) ?>">прайсе</a>, он всегда открыт целиком.
            </div>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach ($promotions as $promo): ?>
                    <div class="card">
                        <span class="card__title"><?= e((string) $promo['title']) ?></span>
                        <p class="card__text"><?= e((string) $promo['text']) ?></p>
                        <span class="card__meta">
                            <span>До <?= e(date_ru($promo['until'] ?? null)) ?></span>
                            <span><?= e((string) ($promo['terms'] ?? '')) ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
