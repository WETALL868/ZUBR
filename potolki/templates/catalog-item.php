<?php
/**
 * Страница элемента каталога: полотно, конструкция, освещение,
 * решение по помещению или услуга.
 *
 * Параметры: $set, $slug.
 */

$item = content_item($set, $slug);

if ($item === null || ($item['enabled'] ?? true) === false) {
    render_404();
}

$hubs = [
    'ceilings' => ['url' => '/ceilings/', 'title' => 'Виды потолков'],
    'systems'  => ['url' => '/systems/',  'title' => 'Решения и конструкции'],
    'lighting' => ['url' => '/lighting/', 'title' => 'Освещение'],
    'rooms'    => ['url' => '/rooms/',    'title' => 'Решения по помещениям'],
    'services' => ['url' => '/services/', 'title' => 'Услуги'],
];

$hub = $hubs[$set];

seo([
    'title'       => $item['seo']['title'] . ' | ' . config('brand.name'),
    'description' => $item['seo']['description'],
    'h1'          => $item['h1'] ?? $item['title'],
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => $hub['title'], 'url' => $hub['url']],
        ['title' => $item['title'], 'url' => '/' . trim($hub['url'], '/') . '/' . $slug . '/'],
    ],
]);

schema_service($item['h1'] ?? $item['title'], $item['seo']['description']);

$faq = faq_pairs($item['faq'] ?? []);
$related = related_links($item['related'] ?? []);
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow"><?= e($hub['title']) ?></div>
                <h1><?= e($item['h1'] ?? $item['title']) ?></h1>
            </div>
            <p class="lead"><?= e($item['lead']) ?></p>
        </div>

        <?php if (filled($item['what'] ?? null)): ?>
            <div class="prose" style="max-width:72ch">
                <p><?= e($item['what']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($item['for_whom'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2>Кому подходит</h2>
        </div>
        <div class="grid grid--2">
            <?php foreach ($item['for_whom'] as $line): ?>
                <div class="card card--flat">
                    <p class="card__text" style="font-size:var(--step-body);color:var(--ink)"><?= e($line) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($item['pros']) || !empty($item['limits'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="pros-cons">
            <?php if (!empty($item['pros'])): ?>
                <div class="pros-cons__col">
                    <h3>Что вы получаете</h3>
                    <ul>
                        <?php foreach ($item['pros'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['limits'])): ?>
                <div class="pros-cons__col pros-cons__col--limits">
                    <h3>Ограничения, о которых стоит знать заранее</h3>
                    <ul>
                        <?php foreach ($item['limits'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($item['recommend'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2>Что мы обычно советуем</h2>
        </div>
        <dl class="speclist">
            <?php foreach ($item['recommend'] as $line): ?>
                <?php
                // Строки вида «Фактура: матовая» разбираются на пару термин — значение.
                $parts = explode(':', $line, 2);
                $term = count($parts) === 2 ? trim($parts[0]) : 'Рекомендация';
                $value = count($parts) === 2 ? trim($parts[1]) : $line;
                ?>
                <div><dt><?= e($term) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($item['stages'])): ?>
<section class="section section--paper">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Порядок работ</div>
            <h2>Как это происходит</h2>
        </div>
        <div class="steps">
            <?php foreach ($item['stages'] as $stage): ?>
                <div class="step">
                    <div>
                        <h3><?= e($stage['title']) ?></h3>
                        <p><?= e($stage['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (filled($item['construction'] ?? null) || !empty($item['design'])): ?>
<section class="section section--tech">
    <div class="container">
        <div class="grid grid--2">
            <?php if (filled($item['construction'] ?? null)): ?>
                <div>
                    <h2>Конструкция и монтаж</h2>
                    <p style="margin-top:1rem"><?= e($item['construction']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['design'])): ?>
                <div>
                    <h2>Варианты решения</h2>
                    <ul style="margin-top:1rem">
                        <?php foreach ($item['design'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($item['price_factors'])): ?>
<section class="section">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Стоимость</div>
                <h2>Из чего складывается цена</h2>
            </div>
            <p class="lead">Точную сумму называем после замера, но структуру сметы видно уже сейчас — и посчитать её можно самостоятельно.</p>
        </div>

        <dl class="speclist">
            <?php foreach ($item['price_factors'] as $index => $line): ?>
                <div>
                    <dt><?= sprintf('%02d', $index + 1) ?></dt>
                    <dd><?= e($line) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="btn-row" style="margin-top:2rem">
            <a class="btn btn--accent" href="<?= e(site_url('/calculator/')) ?>">Посчитать в калькуляторе</a>
            <a class="btn btn--ghost" href="<?= e(site_url('/prices/')) ?>">Смотреть прайс</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($faq !== []): ?>
<section class="section section--warm">
    <div class="container container--narrow">
        <div class="section-head">
            <h2>Вопросы по этому решению</h2>
        </div>
        <?php include APP_ROOT . '/templates/partials/faq.php'; ?>
    </div>
</section>
<?php endif; ?>

<?php
$relatedTitle = 'С чем это обычно сочетают';
include APP_ROOT . '/templates/partials/related.php';

$ctaTitle = 'Посчитаем ваш вариант';
$ctaText  = 'Оставьте телефон — уточним детали, подскажем, где решение уместно, и согласуем бесплатный замер.';
$ctaForm  = ['comment_placeholder' => null];
include APP_ROOT . '/templates/partials/cta.php';
?>
