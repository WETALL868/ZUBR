<?php
/**
 * О компании.
 *
 * Здесь нет ни одного проверяемого факта, который не пришёл бы из конфигурации:
 * год основания, число объектов и бригад выводятся, только если владелец их указал.
 * Всё остальное — о подходе к работе, и за это отвечает сам текст.
 */

seo([
    'title'       => 'О компании — ' . config('brand.name'),
    'description' => 'Как мы работаем с натяжными потолками: подход к смете, честные ограничения решений, порядок работ и зона обслуживания.',
    'h1'          => 'О компании',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'О компании', 'url' => '/about/'],
    ],
]);

$facts = config('facts');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow"><?= e((string) config('brand.positioning')) ?></div>
                <h1>О компании</h1>
            </div>
            <p class="lead">
                Мы делаем натяжные потолки со светом: от простого ровного полотна до теневых систем,
                световых линий и ниш под шторы. Работаем в <?= e((string) config('geo.base_city')) ?>
                и <?= e((string) config('geo.region')) ?>.
            </p>
        </div>

        <?php
        $factCards = [];
        if (filled($facts['founded_year'])) {
            $factCards[] = ['Работаем с', $facts['founded_year'] . ' года'];
        }
        if (filled($facts['objects_done'])) {
            $factCards[] = ['Выполнено объектов', (string) $facts['objects_done']];
        }
        if (filled($facts['crews_count'])) {
            $factCards[] = ['Монтажных бригад', (string) $facts['crews_count']];
        }
        if ($facts['own_production'] === true) {
            $factCards[] = ['Производство', 'Собственный цех раскроя'];
        }
        ?>

        <?php if ($factCards !== []): ?>
            <dl class="hero__facts">
                <?php foreach ($factCards as [$label, $value]): ?>
                    <div class="hero__fact"><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Принципы</div>
            <h2>Как мы работаем</h2>
        </div>

        <div class="grid grid--2">
            <div class="card">
                <span class="card__index">01</span>
                <h3 class="card__title">Смета построчно</h3>
                <p class="card__text">
                    Вы видите каждую позицию: полотно, монтаж, профиль, каждую световую точку, каждый погонный метр.
                    «Работы под ключ — одна сумма» это не смета, а способ не объяснять, за что вы платите.
                </p>
            </div>
            <div class="card">
                <span class="card__index">02</span>
                <h3 class="card__title">Ограничения называем сразу</h3>
                <p class="card__text">
                    У теневого профиля есть требования к стенам, у ткани — к уходу, у ПВХ — к температуре.
                    Проще сказать об этом до заказа, чем объясняться после монтажа.
                </p>
            </div>
            <div class="card">
                <span class="card__index">03</span>
                <h3 class="card__title">Цена не меняется после договора</h3>
                <p class="card__text">
                    Сумма фиксируется по результатам замера. Если на объекте вскрывается что-то непредвиденное,
                    мы согласовываем это отдельно и до начала работ, а не ставим перед фактом.
                </p>
            </div>
            <div class="card">
                <span class="card__index">04</span>
                <h3 class="card__title">Не продаём лишнее</h3>
                <p class="card__text">
                    Если для комнаты достаточно стандартного примыкания, мы так и скажем. Второй уровень
                    ради формы и двадцать светильников в комнате 16 м² — это не «премиум», а перерасход.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tech">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Что мы делаем</div>
                <h2>Направления работы</h2>
            </div>
            <p class="lead">От одной комнаты до квартиры целиком, от квартиры до коммерческого помещения.</p>
        </div>

        <div class="grid grid--3">
            <?php foreach (content('services') as $serviceSlug => $service): ?>
                <a class="card" href="<?= e(site_url('/services/' . $serviceSlug . '/')) ?>">
                    <span class="card__title"><?= e($service['title']) ?></span>
                    <p class="card__text"><?= e(mb_substr((string) $service['lead'], 0, 130)) ?>…</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid grid--2">
            <div>
                <h2>Юридическая информация</h2>
                <p style="margin-top:1rem">
                    <?php if (legal_data_ready()): ?>
                        Работаем по договору. Полные реквизиты, политика обработки персональных данных
                        и другие документы — в <a href="<?= e(site_url('/legal/requisites/')) ?>">разделе документов</a>.
                    <?php else: ?>
                        Реквизиты и юридические документы публикуются в
                        <a href="<?= e(site_url('/legal/requisites/')) ?>">отдельном разделе</a>.
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <h2>Зона обслуживания</h2>
                <p style="margin-top:1rem">
                    <?= e((string) config('geo.base_city')) ?> целиком и города
                    <?= e((string) config('geo.region')) ?> в пределах
                    <?= e((string) config('prices.travel.max_km')) ?> км от МКАД.
                    Подробнее — на странице <a href="<?= e(site_url('/geography/')) ?>">«Где мы работаем»</a>.
                </p>
            </div>
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Обсудить задачу';
$ctaText  = 'Расскажите, что нужно сделать. Ответим по существу и посчитаем смету.';
include APP_ROOT . '/templates/partials/cta.php';
?>
