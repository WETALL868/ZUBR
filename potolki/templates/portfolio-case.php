<?php
/** Страница выполненного объекта. */

$case = content_item('portfolio', $slug);

if ($case === null || ($case['status'] ?? 'published') !== 'published') {
    render_404();
}

seo([
    'title'       => $case['title'] . ' — выполненная работа | ' . config('brand.name'),
    'description' => mb_substr((string) ($case['lead'] ?? $case['title']), 0, 300),
    'h1'          => $case['title'],
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Работы', 'url' => '/portfolio/'],
        ['title' => $case['title'], 'url' => '/portfolio/' . $slug . '/'],
    ],
]);

$location = filled($case['location'] ?? null) ? location((string) $case['location']) : null;
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow"><?= e((string) $case['city']) ?></div>
                <h1><?= e($case['title']) ?></h1>
            </div>
            <p class="lead"><?= e((string) ($case['lead'] ?? '')) ?></p>
        </div>

        <dl class="hero__facts">
            <div class="hero__fact"><dt>Площадь потолков</dt><dd><?= e((string) $case['area']) ?> м²</dd></div>
            <?php if (filled($case['rooms'] ?? null)): ?>
                <div class="hero__fact"><dt>Помещений</dt><dd><?= e((string) $case['rooms']) ?></dd></div>
            <?php endif; ?>
            <div class="hero__fact"><dt>Срок</dt><dd><?= e((string) $case['term']) ?></dd></div>
            <?php if (filled($case['sum'] ?? null)): ?>
                <div class="hero__fact">
                    <dt>Сумма договора</dt>
                    <dd><?= e(money((float) $case['sum'])) ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if (filled($case['sum_note'] ?? null)): ?>
            <p class="meta-line" style="margin-top:1rem"><span><?= e((string) $case['sum_note']) ?></span></p>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($case['images'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="grid grid--2">
            <?php foreach ($case['images'] as $image): ?>
                <figure style="margin:0">
                    <img src="<?= e((string) $image['src']) ?>" alt="<?= e((string) $image['alt']) ?>" loading="lazy" width="1200" height="900" style="width:100%;height:auto">
                    <?php if (filled($image['caption'] ?? null)): ?>
                        <figcaption class="meta-line" style="margin-top:.5rem"><span><?= e((string) $image['caption']) ?></span></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="grid grid--2">
            <div>
                <h2>Состав работ</h2>
                <ul style="margin-top:1rem">
                    <?php foreach ((array) ($case['works'] ?? []) as $work): ?><li><?= e($work) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h2>Решение</h2>
                <dl class="speclist" style="margin-top:1rem">
                    <?php if (filled($case['canvas'] ?? null)): ?>
                        <div><dt>Полотно</dt><dd><?= e((string) $case['canvas']) ?></dd></div>
                    <?php endif; ?>
                    <?php if (filled($case['mounting'] ?? null)): ?>
                        <div><dt>Примыкание</dt><dd><?= e((string) $case['mounting']) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($location !== null): ?>
                        <div>
                            <dt>Территория</dt>
                            <dd><a href="<?= e(site_url((string) location_url((string) $case['location']))) ?>"><?= e($location['name']) ?></a></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if (filled($case['story'] ?? null)): ?>
            <div class="prose" style="margin-top:2.5rem">
                <p><?= e((string) $case['story']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$related = related_links($case['related'] ?? []);
$relatedTitle = 'Решения, использованные на объекте';
include APP_ROOT . '/templates/partials/related.php';

$ctaTitle = 'Хотите так же?';
$ctaText  = 'Посчитаем смету по вашим размерам и предложим решение под ваше помещение.';
$ctaForm  = ['form' => 'case', 'city' => $case['city'] ?? null];
include APP_ROOT . '/templates/partials/cta.php';
?>
