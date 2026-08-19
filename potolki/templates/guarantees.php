<?php
/**
 * Гарантии и документы.
 *
 * Сроки гарантии берутся только из конфигурации. Если их нет,
 * страница честно говорит, что условия фиксируются в договоре,
 * и не называет «10 лет» просто потому, что так пишут конкуренты.
 */

seo([
    'title'       => 'Гарантии и документы — ' . config('brand.name'),
    'description' => 'Какие гарантии мы даём, что фиксируется в договоре, какие документы передаём после сдачи работ и что считается гарантийным случаем.',
    'h1'          => 'Гарантии и документы',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Гарантии', 'url' => '/guarantees/'],
    ],
]);

$guarantee = config('guarantee');
$hasTerms = filled($guarantee['canvas_years']) || filled($guarantee['works_years']);
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Ответственность</div>
                <h1>Гарантии и документы</h1>
            </div>
            <p class="lead">
                Гарантия — это то, что записано в договоре, а не цифра в рекламе.
                Ниже — что именно мы гарантируем и что делать, если что-то пошло не так.
            </p>
        </div>

        <?php if ($hasTerms): ?>
            <dl class="hero__facts">
                <?php if (filled($guarantee['canvas_years'])): ?>
                    <div class="hero__fact">
                        <dt>На полотно</dt>
                        <dd><?= e((string) $guarantee['canvas_years']) ?> <?= e(plural((int) $guarantee['canvas_years'], 'год', 'года', 'лет')) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (filled($guarantee['works_years'])): ?>
                    <div class="hero__fact">
                        <dt>На монтаж</dt>
                        <dd><?= e((string) $guarantee['works_years']) ?> <?= e(plural((int) $guarantee['works_years'], 'год', 'года', 'лет')) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (filled($guarantee['lighting_years'])): ?>
                    <div class="hero__fact">
                        <dt>На светотехнику</dt>
                        <dd><?= e((string) $guarantee['lighting_years']) ?> <?= e(plural((int) $guarantee['lighting_years'], 'год', 'года', 'лет')) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>

            <?php if (filled($guarantee['notes'])): ?>
                <p style="margin-top:1.5rem"><?= e((string) $guarantee['notes']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="notice notice--warn">
                <strong>Гарантийные сроки указываются в договоре.</strong>
                Мы не публикуем здесь цифру, пока она не утверждена: обещание гарантии, которого нет
                в документах, ничего не стоит. Точные сроки на полотно, работы и светотехнику
                вам назовут при согласовании сметы, и они будут прописаны в договоре.
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid grid--2">
            <div class="pros-cons__col">
                <h3>Что считается гарантийным случаем</h3>
                <ul>
                    <li>Провисание полотна не по вине эксплуатации.</li>
                    <li>Расхождение сварного шва.</li>
                    <li>Выход профиля из крепления.</li>
                    <li>Дефект полотна: пятна, изменение цвета, брак материала.</li>
                    <li>Ошибки монтажа: неровная линия примыкания, неправильно установленные закладные.</li>
                </ul>
            </div>
            <div class="pros-cons__col pros-cons__col--limits">
                <h3>Что гарантией не покрывается</h3>
                <ul>
                    <li>Механические повреждения: порезы, проколы, удары.</li>
                    <li>Последствия протечки сверху — это не дефект потолка (но мы поможем: см. слив воды).</li>
                    <li>Повреждения от перегрева лампами, не подходящими для натяжных потолков.</li>
                    <li>Эксплуатация в неотапливаемом помещении, если полотно для этого не предназначено.</li>
                    <li>Работы, выполненные другой компанией: на них мы гарантию давать не можем.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section section--tech">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Документы</div>
            <h2>Что вы получаете</h2>
        </div>

        <dl class="speclist">
            <div><dt>Договор</dt><dd>С зафиксированной сметой, сроками и гарантийными обязательствами.</dd></div>
            <div><dt>Смета</dt><dd>Построчная: позиции, количество, единицы измерения, ставки.</dd></div>
            <div><dt>Акт сдачи-приёмки</dt><dd>Подписывается после проверки геометрии, света и отсутствия дефектов.</dd></div>
            <div><dt>Документы на материалы</dt><dd>Документы производителя на поставленное полотно — по запросу и для коммерческих объектов.</dd></div>
        </dl>

        <?php if (config('features.certificates')): ?>
            <div class="notice" style="margin-top:2rem">
                Сканы сертификатов и документов размещаются в этом разделе.
                Загрузите файлы в /assets/images/docs/ и добавьте их сюда.
            </div>
        <?php else: ?>
            <div class="notice notice--empty" style="margin-top:2rem">
                <strong>Сканы сертификатов пока не опубликованы.</strong>
                Мы не выкладываем чужие документы и не показываем «сертификат» без привязки к конкретному
                полотну. Документы на материалы вашего заказа предоставляются по запросу.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Гарантийный случай?';
$ctaText  = 'Опишите проблему и приложите фотографии в комментарии — договоримся о выезде мастера.';
$ctaForm  = ['form' => 'question', 'button' => 'Сообщить о проблеме'];
include APP_ROOT . '/templates/partials/cta.php';
?>
