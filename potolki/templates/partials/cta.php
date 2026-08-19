<?php
/**
 * Целевой блок с формой.
 *
 * Ставится один раз на страницу — в конце содержательной части.
 * Повторять один и тот же призыв после каждого экрана мы не будем:
 * это раздражает и не работает.
 *
 * Переменные: $ctaTitle, $ctaText, $ctaForm (опции render_lead_form).
 */

$ctaTitle = $ctaTitle ?? 'Посчитать стоимость и вызвать замерщика';
$ctaText  = $ctaText ?? 'Замер бесплатный в пределах зоны выезда и ни к чему не обязывает. Смета фиксируется до начала работ.';
$ctaForm  = $ctaForm ?? [];
?>
<section class="section section--paper">
    <div class="container">
        <div class="calc" style="align-items:start">
            <div>
                <div class="eyebrow">Следующий шаг</div>
                <h2><?= e($ctaTitle) ?></h2>
                <p class="lead" style="margin-top:1rem"><?= e($ctaText) ?></p>

                <dl class="hero__facts" style="margin-top:2rem">
                    <div class="hero__fact">
                        <dt>Замер</dt>
                        <dd>Бесплатно в зоне выезда</dd>
                    </div>
                    <div class="hero__fact">
                        <dt>Смета</dt>
                        <dd>Построчно, до начала работ</dd>
                    </div>
                    <div class="hero__fact">
                        <dt>Расчёт онлайн</dt>
                        <dd><a href="<?= e(site_url('/calculator/')) ?>">Открыть калькулятор</a></dd>
                    </div>
                </dl>
            </div>

            <div class="calc__panel">
                <?php render_lead_form(array_merge([
                    'form'   => 'measure',
                    'fields' => ['name', 'phone', 'comment'],
                    'button' => 'Вызвать замерщика',
                ], $ctaForm)); ?>
            </div>
        </div>
    </div>
</section>
