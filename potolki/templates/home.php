<?php
/** Главная страница. */

$needsCalculator = true;

seo([
    'title'       => 'Натяжные потолки в Москве и области — расчёт сметы онлайн | ' . config('brand.name'),
    'description' => 'Проектирование, изготовление и монтаж натяжных потолков в ' . config('geo.base_city') . ' и ' . config('geo.region') . '. Теневые и бесщелевые системы, световые линии, треки, скрытый карниз. Смета построчно и бесплатный замер.',
    'h1'          => 'Натяжные потолки в Москве и Московской области',
    'breadcrumbs' => [],
]);

schema_service(
    'Монтаж натяжных потолков',
    'Проектирование, изготовление и установка натяжных потолков в ' . config('geo.base_city') . ' и ' . config('geo.region') . '.'
);

$ceilings = content('ceilings');
$systems  = content('systems');
$lighting = content('lighting');
$rooms    = content('rooms');
$prices   = config('prices');

/** Навигатор по задачам: что человек на самом деле хочет получить. */
$tasks = [
    ['url' => '/systems/standartnoe-primykanie/', 'title' => 'Ровный потолок без лишних деталей', 'text' => 'Классическое решение с минимальным отступом от плиты.'],
    ['url' => '/systems/teneviye/',               'title' => 'Чистая линия у стены',            'text' => 'Теневое или бесщелевое примыкание без маскировочной ленты.'],
    ['url' => '/lighting/svetovye-linii/',        'title' => 'Свет линиями, а не точками',      'text' => 'Равномерное освещение без россыпи светильников.'],
    ['url' => '/lighting/treki/',                 'title' => 'Свет, который можно двигать',     'text' => 'Трековые системы под меняющуюся расстановку мебели.'],
    ['url' => '/systems/skrytyj-karniz/',         'title' => 'Шторы из потолка',                'text' => 'Ниша под карниз: окно визуально выше, механики не видно.'],
    ['url' => '/ceilings/bez-nagreva/',           'title' => 'Монтаж без нагрева',              'text' => 'Когда нельзя выносить вещи и греть помещение.'],
    ['url' => '/rooms/dlya-vannoj/',              'title' => 'Влажные помещения',               'text' => 'Ванная и кухня: вентиляция, свет, защита от протечки.'],
    ['url' => '/rooms/dlya-doma/',                'title' => 'Загородный дом',                  'text' => 'Деревянные перекрытия, большие пролёты, сезонное отопление.'],
];
?>

<section class="hero">
    <div class="container">
        <div class="hero__grid">
            <div>
                <div class="eyebrow"><?= e((string) config('geo.base_city')) ?> и <?= e((string) config('geo.region')) ?></div>
                <h1 class="hero__title">Натяжные потолки<br>как <em>часть архитектуры</em> интерьера</h1>
                <p class="hero__text">
                    Проектируем, изготавливаем и монтируем потолки со светом: теневые и бесщелевые системы,
                    световые линии, треки, скрытый карниз. Смету считаем построчно — вы видите каждую позицию
                    ещё до выезда замерщика.
                </p>
                <div class="hero__actions btn-row">
                    <a class="btn btn--accent" href="<?= e(site_url('/calculator/')) ?>">Рассчитать стоимость</a>
                    <a class="btn btn--ghost" href="<?= e(site_url('/measurement/')) ?>">Вызвать замерщика</a>
                </div>
            </div>

            <div class="hero__media" role="img" aria-label="Интерьер с натяжным потолком и световой линией">
                <?php
                /* Реальная фотография интерьера подставляется владельцем:
                   /assets/images/hero.webp. См. IMAGE_BRIEF.md. */
                $heroImage = APP_ROOT . '/assets/images/hero.webp';
                ?>
                <?php if (is_file($heroImage)): ?>
                    <img src="<?= e(asset('images/hero.webp')) ?>" alt="Интерьер гостиной с натяжным потолком и световой линией" width="1200" height="900" fetchpriority="high">
                <?php else: ?>
                    <?php /* Пока владелец не передал фотографию, показываем схему потолка,
                             а не чужой интерьер из фотобанка. См. IMAGE_BRIEF.md. */ ?>
                    <svg viewBox="0 0 800 680" width="800" height="680" aria-hidden="true" focusable="false" style="width:100%;height:100%">
                        <g fill="none" stroke="#D8D2C8" stroke-width="1">
                            <rect x="80" y="90" width="640" height="500"></rect>
                            <rect x="112" y="122" width="576" height="436"></rect>
                        </g>
                        <g stroke="#B28A52" stroke-width="6" stroke-linecap="round">
                            <line x1="180" y1="250" x2="620" y2="250"></line>
                            <line x1="180" y1="430" x2="470" y2="430"></line>
                        </g>
                        <g fill="#C9C2B6">
                            <circle cx="220" cy="340" r="7"></circle>
                            <circle cx="340" cy="340" r="7"></circle>
                            <circle cx="460" cy="340" r="7"></circle>
                            <circle cx="580" cy="340" r="7"></circle>
                            <circle cx="220" cy="520" r="7"></circle>
                            <circle cx="340" cy="520" r="7"></circle>
                            <circle cx="460" cy="520" r="7"></circle>
                            <circle cx="580" cy="520" r="7"></circle>
                        </g>
                        <g stroke="#B9B2A5" stroke-width="1" stroke-dasharray="4 6">
                            <line x1="80" y1="60" x2="720" y2="60"></line>
                            <line x1="80" y1="52" x2="80" y2="68"></line>
                            <line x1="720" y1="52" x2="720" y2="68"></line>
                        </g>
                        <text x="400" y="46" text-anchor="middle" fill="#8B9096"
                              font-family="Manrope, sans-serif" font-size="16" letter-spacing="2">
                            ПЛАН ПОТОЛКА: СВЕТОВЫЕ ЛИНИИ И ТОЧКИ
                        </text>
                    </svg>
                <?php endif; ?>
            </div>
        </div>

        <dl class="hero__facts">
            <div class="hero__fact">
                <dt>Замер</dt>
                <dd>Бесплатно в зоне выезда</dd>
            </div>
            <div class="hero__fact">
                <dt>Смета</dt>
                <dd>Построчно, фиксируется в договоре</dd>
            </div>
            <div class="hero__fact">
                <dt>Бесплатный выезд</dt>
                <dd>До <?= e((string) $prices['travel']['free_km']) ?> км от МКАД</dd>
            </div>
            <div class="hero__fact">
                <dt>География</dt>
                <dd><a href="<?= e(site_url('/geography/')) ?>">Москва и область</a></dd>
            </div>
        </dl>
    </div>
</section>

<section class="section" id="calc">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Расчёт за минуту</div>
                <h2>Считайте сами — цены не спрятаны</h2>
            </div>
            <p class="lead">
                Калькулятор работает по тому же прайсу, что и наши сметы. Он покажет не только итог,
                но и каждую строку: полотно, профиль, свет, дополнительные работы.
            </p>
        </div>

        <?php
        $calcMode = 'quick';
        include APP_ROOT . '/templates/partials/calc.php';
        ?>
    </div>
</section>

<section class="section section--warm">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">С чего начать</div>
            <h2>Выберите задачу, а не название технологии</h2>
            <p class="lead">Большинство решений называются непонятно. Здесь — по тому, что вы хотите получить в комнате.</p>
        </div>

        <div class="grid grid--4">
            <?php foreach ($tasks as $task): ?>
                <a class="card" href="<?= e(site_url($task['url'])) ?>">
                    <span class="card__title"><?= e($task['title']) ?></span>
                    <p class="card__text"><?= e($task['text']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Материалы</div>
                <h2>Виды полотен без маркетинга</h2>
            </div>
            <p class="lead">У каждого материала есть не только преимущества. Сравнение по параметрам, которые действительно влияют на выбор.</p>
        </div>

        <div class="table-wrap">
            <table>
                <caption>Ограничения указаны честно: они экономят время на замере.</caption>
                <thead>
                    <tr>
                        <th scope="col">Полотно</th>
                        <th scope="col">Ширина без шва</th>
                        <th scope="col">Держит воду</th>
                        <th scope="col">Монтаж</th>
                        <th scope="col">Где уместно</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href="<?= e(site_url('/ceilings/matovye/')) ?>">Матовое ПВХ</a></td>
                        <td>до <?= e((string) $prices['canvas']['pvc_matte']['seamless_up_to']) ?> м</td>
                        <td>да</td>
                        <td>с прогревом</td>
                        <td>Спальня, детская, кабинет</td>
                    </tr>
                    <tr>
                        <td><a href="<?= e(site_url('/ceilings/satinovye/')) ?>">Сатиновое ПВХ</a></td>
                        <td>до <?= e((string) $prices['canvas']['pvc_satin']['seamless_up_to']) ?> м</td>
                        <td>да</td>
                        <td>с прогревом</td>
                        <td>Гостиная, спальня</td>
                    </tr>
                    <tr>
                        <td><a href="<?= e(site_url('/ceilings/glyancevye/')) ?>">Глянцевое ПВХ</a></td>
                        <td>до <?= e((string) $prices['canvas']['pvc_gloss']['seamless_up_to']) ?> м</td>
                        <td>да</td>
                        <td>с прогревом</td>
                        <td>Санузел, коридор</td>
                    </tr>
                    <tr>
                        <td><a href="<?= e(site_url('/ceilings/tkanevye/')) ?>">Тканевое</a></td>
                        <td>до <?= e((string) $prices['canvas']['fabric']['seamless_up_to']) ?> м</td>
                        <td>нет</td>
                        <td>без нагрева</td>
                        <td>Дом, дача, широкие комнаты</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="margin-top:1.5rem"><a class="link-arrow" href="<?= e(site_url('/ceilings/')) ?>">Все виды полотен</a></p>
    </div>
</section>

<section class="section section--tech">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Конструкция</div>
            <h2>Как потолок подходит к стене</h2>
            <p class="lead">Именно это решение сильнее всего влияет и на вид потолка, и на смету — сильнее, чем выбор фактуры.</p>
        </div>

        <div class="grid grid--3">
            <?php foreach (['standartnoe-primykanie', 'teneviye', 'besshelevye', 'paryashchie', 'konturnye', 'dvuhurovnevye'] as $slug): ?>
                <?php $item = $systems[$slug] ?? null; if ($item === null) { continue; } ?>
                <a class="card" href="<?= e(site_url('/systems/' . $slug . '/')) ?>">
                    <span class="card__title"><?= e($item['title']) ?></span>
                    <p class="card__text"><?= e(mb_substr($item['lead'], 0, 140)) ?>…</p>
                    <span class="card__meta">
                        <span><?= e($prices['mounting'][$item['calc_preset']['mounting'] ?? '']['title'] ?? 'Конструкция') ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Свет</div>
                <h2>Освещение планируется до монтажа</h2>
            </div>
            <p class="lead">Все закладные ставятся под полотно. Добавить светильник потом можно, но это уже частичный демонтаж.</p>
        </div>

        <div class="grid grid--3">
            <?php foreach ($lighting as $slug => $item): ?>
                <a class="card" href="<?= e(site_url('/lighting/' . $slug . '/')) ?>">
                    <span class="card__title"><?= e($item['title']) ?></span>
                    <p class="card__text"><?= e(mb_substr($item['lead'], 0, 140)) ?>…</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--warm">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Работы</div>
            <h2>Выполненные объекты</h2>
        </div>

        <?php $cases = content('portfolio'); ?>
        <?php if ($cases === []): ?>
            <div class="notice notice--empty">
                <strong>Раздел наполняется.</strong>
                Мы не показываем чужие фотографии и не пишем «более 5000 объектов» без подтверждения.
                Здесь появятся реальные работы с площадью, составом и сроками — по мере фотосъёмки объектов.
                Пока можно посмотреть, как считается смета, в <a href="<?= e(site_url('/calculator/')) ?>">калькуляторе</a>.
            </div>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach (array_slice($cases, 0, 3, true) as $slug => $case): ?>
                    <a class="card" href="<?= e(site_url('/portfolio/' . $slug . '/')) ?>">
                        <span class="card__title"><?= e($case['title']) ?></span>
                        <p class="card__text"><?= e((string) ($case['lead'] ?? '')) ?></p>
                        <span class="card__meta"><span><?= e((string) $case['city']) ?></span><span><?= e((string) $case['area']) ?> м²</span></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <p style="margin-top:1.5rem"><a class="link-arrow" href="<?= e(site_url('/portfolio/')) ?>">Все работы</a></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Прозрачность</div>
                <h2>Из чего складывается цена</h2>
            </div>
            <p class="lead">Три независимые части, а не одна «цена за квадратный метр». Поэтому маленький коридор стоит дороже, чем кажется.</p>
        </div>

        <div class="grid grid--3">
            <div class="card card--tech">
                <span class="card__index">Считается по площади</span>
                <h3 class="card__title">Полотно и монтаж</h3>
                <p class="card__text">Материал зависит от площади помещения. Работа по натяжке — тоже, но это отдельная строка: дешёвое полотно не означает дешёвый монтаж.</p>
            </div>
            <div class="card card--tech">
                <span class="card__index">Считается по периметру</span>
                <h3 class="card__title">Профиль и примыкание</h3>
                <p class="card__text">Погонные метры по стенам. Теневой профиль дороже стандартного в несколько раз — именно здесь смета растёт быстрее всего.</p>
            </div>
            <div class="card card--tech">
                <span class="card__index">Считается поштучно и метрами</span>
                <h3 class="card__title">Свет и конструкции</h3>
                <p class="card__text">Светильники, линии, треки, ниша под шторы, второй уровень, обход труб. В сложных проектах эта часть больше стоимости полотна.</p>
            </div>
        </div>

        <p style="margin-top:1.5rem"><a class="link-arrow" href="<?= e(site_url('/prices/')) ?>">Открыть прайс-лист</a></p>
    </div>
</section>

<section class="section section--paper">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Порядок работы</div>
            <h2>Как проходит заказ</h2>
        </div>

        <div class="steps">
            <?php foreach (content('services')['ustanovka']['stages'] as $stage): ?>
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

<?php if (filled(config('guarantee.canvas_years')) || config('features.certificates')): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Документы</div>
            <h2>Гарантии и подтверждения</h2>
        </div>
        <div class="grid grid--3">
            <?php if (filled(config('guarantee.canvas_years'))): ?>
                <div class="card">
                    <span class="card__title">Гарантия на полотно</span>
                    <p class="card__text"><?= e((string) config('guarantee.canvas_years')) ?> <?= e(plural((int) config('guarantee.canvas_years'), 'год', 'года', 'лет')) ?> по договору.</p>
                </div>
            <?php endif; ?>
            <?php if (filled(config('guarantee.works_years'))): ?>
                <div class="card">
                    <span class="card__title">Гарантия на работы</span>
                    <p class="card__text"><?= e((string) config('guarantee.works_years')) ?> <?= e(plural((int) config('guarantee.works_years'), 'год', 'года', 'лет')) ?> на монтаж.</p>
                </div>
            <?php endif; ?>
            <div class="card">
                <span class="card__title">Документы на материалы</span>
                <p class="card__text">Предоставляем документы производителя на поставленное полотно.</p>
            </div>
        </div>
        <p style="margin-top:1.5rem"><a class="link-arrow" href="<?= e(site_url('/guarantees/')) ?>">Подробнее о гарантиях</a></p>
    </div>
</section>
<?php endif; ?>

<section class="section section--warm">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Помещения</div>
                <h2>Решения по комнатам</h2>
            </div>
            <p class="lead">В каждой комнате свои условия: влажность, свет, высота, риск протечки. Универсального «лучшего потолка» не бывает.</p>
        </div>

        <ul class="tags">
            <?php foreach ($rooms as $slug => $room): ?>
                <li><a class="tag" href="<?= e(site_url('/rooms/' . $slug . '/')) ?>"><?= e($room['menu_title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container container--narrow">
        <div class="section-head">
            <div class="eyebrow">Вопросы</div>
            <h2>Частые вопросы</h2>
        </div>

        <?php
        $faqGroups = content('faq');
        $faq = array_merge($faqGroups['Выбор и материалы'], array_slice($faqGroups['Монтаж и подготовка'], 0, 3));
        include APP_ROOT . '/templates/partials/faq.php';
        ?>

        <p style="margin-top:1.5rem"><a class="link-arrow" href="<?= e(site_url('/faq/')) ?>">Все вопросы и ответы</a></p>
    </div>
</section>

<section class="section section--night">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Контакты</div>
                <h2>Ответим и посчитаем</h2>
                <p class="lead" style="margin-top:1rem">
                    <?= e((string) config('contacts.work_hours')) ?>.
                    Работаем в <?= e((string) config('geo.base_city')) ?> и <?= e((string) config('geo.region')) ?>.
                </p>
            </div>
            <div class="btn-row">
                <?php if (filled(config('contacts.phone.raw'))): ?>
                    <a class="btn btn--accent" href="tel:<?= e((string) config('contacts.phone.raw')) ?>"><?= e((string) config('contacts.phone.display')) ?></a>
                <?php endif; ?>
                <a class="btn btn--outline-light" href="<?= e(site_url('/contacts/')) ?>">Все контакты</a>
            </div>
        </div>
    </div>
</section>
