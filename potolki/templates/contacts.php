<?php
/** Контакты. Всё, что не заполнено в конфигурации, просто не выводится. */

seo([
    'title'       => 'Контакты — ' . config('brand.name'),
    'description' => 'Телефон, почта и режим работы. Принимаем заявки на замер и расчёт по ' . config('geo.base_city') . ' и ' . config('geo.region') . '.',
    'h1'          => 'Контакты',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Контакты', 'url' => '/contacts/'],
    ],
]);

$contacts = config('contacts');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Связь</div>
                <h1>Контакты</h1>
            </div>
            <p class="lead">
                Звоните или пишите: <?= e((string) $contacts['work_hours']) ?>.
                Если звонок неудобен, оставьте заявку — перезвоним сами.
            </p>
        </div>

        <div class="grid grid--2">
            <div class="calc__panel">
                <dl class="speclist">
                    <?php if (filled($contacts['phone']['raw'])): ?>
                        <div>
                            <dt>Телефон</dt>
                            <dd><a href="tel:<?= e((string) $contacts['phone']['raw']) ?>"><?= e((string) $contacts['phone']['display']) ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if (filled($contacts['phone_secondary'])): ?>
                        <div>
                            <dt>Дополнительный телефон</dt>
                            <dd><?= e((string) $contacts['phone_secondary']) ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if (filled($contacts['email'])): ?>
                        <div>
                            <dt>Почта</dt>
                            <dd><a href="mailto:<?= e((string) $contacts['email']) ?>"><?= e((string) $contacts['email']) ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if (filled($contacts['whatsapp']) || filled($contacts['telegram']) || filled($contacts['max'])): ?>
                        <div>
                            <dt>Мессенджеры</dt>
                            <dd>
                                <?php if (filled($contacts['whatsapp'])): ?>
                                    <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $contacts['whatsapp']) ?? '') ?>" data-messenger="whatsapp" rel="noopener">WhatsApp</a>
                                <?php endif; ?>
                                <?php if (filled($contacts['telegram'])): ?>
                                    <a href="<?= e((string) $contacts['telegram']) ?>" data-messenger="telegram" rel="noopener">Telegram</a>
                                <?php endif; ?>
                                <?php if (filled($contacts['max'])): ?>
                                    <a href="<?= e((string) $contacts['max']) ?>" data-messenger="max" rel="noopener">MAX</a>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt>Режим работы</dt>
                        <dd><?= e((string) $contacts['work_hours']) ?></dd>
                    </div>

                    <?php if (filled($contacts['office_address'])): ?>
                        <div>
                            <dt>Адрес</dt>
                            <dd>
                                <?= e((string) $contacts['office_address']) ?>
                                <?php if (filled($contacts['map_url'])): ?>
                                    <br><a href="<?= e((string) $contacts['map_url']) ?>" target="_blank" rel="noopener">Открыть на карте</a>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div>
                        <dt>Зона выезда</dt>
                        <dd>
                            <?= e((string) config('geo.base_city')) ?> и <?= e((string) config('geo.region')) ?>,
                            бесплатно до <?= e((string) config('prices.travel.free_km')) ?> км от МКАД.
                            <br><a href="<?= e(site_url('/geography/')) ?>">Где мы работаем</a>
                        </dd>
                    </div>
                </dl>

                <?php if (!filled($contacts['office_address'])): ?>
                    <div class="notice" style="margin-top:1.5rem">
                        <strong>Офиса для приёма клиентов нет.</strong>
                        Мы не указываем адрес, по которому нельзя приехать. Замерщик приезжает к вам —
                        с образцами полотен, чтобы выбрать фактуру при вашем освещении.
                    </div>
                <?php endif; ?>
            </div>

            <div class="calc__panel">
                <?php render_lead_form([
                    'form'   => 'consult',
                    'id'     => 'contacts-form',
                    'title'  => 'Написать нам',
                    'text'   => 'Ответим в рабочее время. Если нужен расчёт — приложите площадь и тип помещения.',
                    'fields' => ['name', 'phone', 'email', 'contact_way', 'comment'],
                    'button' => 'Отправить',
                ]); ?>
            </div>
        </div>
    </div>
</section>

<?php if (filled($contacts['map_embed'])): ?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head"><h2>На карте</h2></div>
        <div class="table-wrap"><?= $contacts['map_embed'] ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section section--tech">
    <div class="container">
        <div class="section-head">
            <h2>Что происходит после заявки</h2>
        </div>
        <div class="steps">
            <div class="step"><div><h3>Звонок в рабочее время</h3><p>Уточняем задачу, помещение и сроки. Если решение вам не подходит, скажем сразу — это экономит время обеим сторонам.</p></div></div>
            <div class="step"><div><h3>Согласование замера</h3><p>Выбираем удобный интервал. Замер бесплатный в зоне выезда и ни к чему не обязывает.</p></div></div>
            <div class="step"><div><h3>Смета</h3><p>Построчный расчёт по фактическим размерам. Сумма фиксируется в договоре.</p></div></div>
        </div>

        <p class="meta-line" style="margin-top:2rem">
            <span>Персональные данные обрабатываются по <a href="<?= e(site_url('/legal/privacy-policy/')) ?>">политике</a>.</span>
            <span>Согласие можно отозвать в любой момент.</span>
        </p>
    </div>
</section>
