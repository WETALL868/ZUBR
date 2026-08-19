<?php
/** Все вопросы и ответы, сгруппированные по темам. */

seo([
    'title'       => 'Вопросы и ответы о натяжных потолках | ' . config('brand.name'),
    'description' => 'Материалы, монтаж, свет, эксплуатация и цены: ответы на вопросы, которые задают до заказа. Без рекламы и обещаний, которых нельзя выполнить.',
    'h1'          => 'Вопросы и ответы',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Вопросы и ответы', 'url' => '/faq/'],
    ],
]);

$groups = content('faq');

// В микроразметку отдаём все вопросы страницы — они действительно на ней выведены.
$all = [];
foreach ($groups as $items) {
    foreach ($items as $item) {
        $all[] = $item;
    }
}
schema_faq($all);
?>

<section class="section section--tight">
    <div class="container container--narrow">
        <div class="eyebrow">Справочник</div>
        <h1>Вопросы и ответы</h1>
        <p class="lead" style="margin-top:1.5rem">
            Здесь собраны вопросы, которые чаще всего задают по телефону. Часть ответов неудобная —
            зато после монтажа не будет сюрпризов.
        </p>

        <?php foreach ($groups as $groupTitle => $items): ?>
            <h2 style="margin-top:3rem"><?= e((string) $groupTitle) ?></h2>
            <?php
            $faq = $items;
            $faqSchema = false; // разметка уже добавлена выше целиком
            include APP_ROOT . '/templates/partials/faq.php';
            ?>
        <?php endforeach; ?>
    </div>
</section>

<?php
$related = page_links(['prices', 'calculator', 'measurement']);
$relatedTitle = 'Полезное рядом';
include APP_ROOT . '/templates/partials/related.php';

$ctaTitle = 'Вашего вопроса здесь нет?';
$ctaText  = 'Задайте его — ответим по существу. Если решение вам не подходит, скажем об этом прямо.';
$ctaForm  = ['form' => 'question', 'button' => 'Задать вопрос'];
include APP_ROOT . '/templates/partials/cta.php';
?>
