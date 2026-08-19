<?php
/**
 * Статья.
 *
 * Если у материала заданы calc_examples, суммы считаются калькулятором
 * по действующему прайсу прямо при выводе страницы. Так примеры расчёта
 * не устаревают и не дублируют цены в тексте.
 */

use Potolki\Calculator\Calculator;

$post = content_item('blog', $slug);

if ($post === null || ($post['status'] ?? 'published') !== 'published') {
    render_404();
}

seo([
    'title'       => $post['seo']['title'] . ' | ' . config('brand.name'),
    'description' => $post['seo']['description'],
    'h1'          => $post['title'],
    'updated'     => $post['updated'] ?? null,
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Материалы', 'url' => '/blog/'],
        ['title' => $post['title'], 'url' => '/blog/' . $slug . '/'],
    ],
]);

schema_add([
    '@type'         => 'Article',
    'headline'      => $post['title'],
    'description'   => $post['seo']['description'],
    'datePublished' => $post['updated'] ?? null,
    'dateModified'  => $post['updated'] ?? null,
    'author'        => ['@id' => absolute_url('/') . '#organization'],
    'publisher'     => ['@id' => absolute_url('/') . '#organization'],
    'mainEntityOfPage' => absolute_url('/blog/' . $slug . '/'),
]);
?>

<article class="section section--tight">
    <div class="container container--narrow">
        <div class="eyebrow">Разбор</div>
        <h1><?= e($post['title']) ?></h1>
        <p class="meta-line" style="margin-top:1rem">
            <span>Обновлено <?= e(date_ru($post['updated'] ?? null)) ?></span>
            <span>Читать <?= e((string) max(2, (int) round(mb_strlen(json_encode($post['sections'], JSON_UNESCAPED_UNICODE) ?: '') / 1200))) ?> мин</span>
        </p>

        <p class="lead" style="margin-top:1.5rem"><?= e($post['lead']) ?></p>

        <div class="prose" style="margin-top:2.5rem">
            <?php foreach ($post['sections'] as $section): ?>
                <h2><?= e($section['h']) ?></h2>
                <?php foreach ((array) ($section['p'] ?? []) as $paragraph): ?>
                    <p><?= e($paragraph) ?></p>
                <?php endforeach; ?>
                <?php if (!empty($section['ul'])): ?>
                    <ul>
                        <?php foreach ($section['ul'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</article>

<?php if (!empty($post['calc_examples'])): ?>
<section class="section section--tech">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Считается прямо сейчас</div>
                <h2>Примеры расчёта</h2>
            </div>
            <p class="lead">
                Суммы получены калькулятором по прайсу версии <?= e((string) config('prices.version')) ?>
                в момент открытия страницы. Если прайс изменится, изменятся и цифры — устареть они не могут.
            </p>
        </div>

        <div class="grid grid--2">
            <?php $calculator = Calculator::fromConfig(); ?>
            <?php foreach ($post['calc_examples'] as $example): ?>
                <?php $result = $calculator->calculate($example['input']); ?>
                <div class="card">
                    <span class="card__title"><?= e($example['title']) ?></span>
                    <p class="card__text"><?= e($example['note']) ?></p>

                    <?php if ($result['ok']): ?>
                        <dl class="speclist" style="margin-top:1rem">
                            <?php foreach ($result['rooms'] as $room): ?>
                                <div>
                                    <dt><?= e($room['title']) ?><br><span style="font-weight:400"><?= e((string) $room['area']) ?> м², <?= e((string) $room['perimeter']) ?> пог. м</span></dt>
                                    <dd><?= e(money((float) $room['subtotal'])) ?></dd>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($result['totals']['minimum_applied']): ?>
                                <div>
                                    <dt>Минимальная сумма заказа</dt>
                                    <dd><?= e(money((float) $result['totals']['minimum_order'])) ?></dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt><strong>Итого</strong></dt>
                                <dd><strong><?= e(money((float) $result['totals']['total'])) ?></strong></dd>
                            </div>
                        </dl>
                    <?php else: ?>
                        <p class="card__text">Пример временно недоступен: проверьте параметры в content/blog.php.</p>
                    <?php endif; ?>

                    <span class="card__meta">
                        <span>Предварительно, без замера</span>
                        <a href="<?= e(site_url('/calculator/')) ?>">Посчитать своё</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$related = array_merge(
    blog_links($post['related'] ?? []),
    page_links($post['related_pages'] ?? [])
);
$relatedTitle = 'Что почитать дальше';
include APP_ROOT . '/templates/partials/related.php';

$ctaTitle = 'Нужен расчёт под вашу задачу?';
$ctaText  = 'Оставьте телефон — уточним детали и посчитаем смету по вашим размерам. Замер бесплатный в зоне выезда.';
include APP_ROOT . '/templates/partials/cta.php';
?>
