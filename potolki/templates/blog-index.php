<?php
/** Список полезных материалов. */

seo([
    'title'       => 'Полезные материалы о натяжных потолках | ' . config('brand.name'),
    'description' => 'Разборы без рекламы: как выбрать материал, что входит в цену, когда ставить потолок в ремонте, что делать после протечки и как ухаживать за полотном.',
    'h1'          => 'Полезные материалы',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Материалы', 'url' => '/blog/'],
    ],
]);

$posts = array_filter(content('blog'), static fn (array $p): bool => ($p['status'] ?? 'published') === 'published');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Разборы</div>
                <h1>Полезные материалы</h1>
            </div>
            <p class="lead">
                Здесь нет «трендов года» и списков достижений. Только вопросы, которые реально задают
                до заказа, и ответы, по которым можно принять решение.
            </p>
        </div>

        <div class="grid grid--3">
            <?php foreach ($posts as $slug => $post): ?>
                <a class="card" href="<?= e(site_url('/blog/' . $slug . '/')) ?>">
                    <span class="card__title"><?= e($post['title']) ?></span>
                    <p class="card__text"><?= e(mb_substr((string) $post['lead'], 0, 150)) ?>…</p>
                    <span class="card__meta">
                        <span>Обновлено <?= e(date_ru($post['updated'] ?? null)) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Остались вопросы по вашему случаю?';
$ctaText  = 'Опишите помещение и задачу — ответим по существу, без попытки продать самое дорогое решение.';
$ctaForm  = ['form' => 'question', 'button' => 'Задать вопрос'];
include APP_ROOT . '/templates/partials/cta.php';
?>
