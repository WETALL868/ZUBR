<?php
/** Страница 404. Отдаётся с корректным статусом и помогает найти нужное. */
?>
<section class="section">
    <div class="container container--narrow">
        <div class="eyebrow">Ошибка 404</div>
        <h1>Такой страницы нет</h1>
        <p class="lead" style="margin-top:1.5rem">
            Возможно, страница переехала или в адресе опечатка. Вот что обычно ищут:
        </p>

        <div class="grid grid--2" style="margin-top:2rem">
            <a class="card" href="<?= e(site_url('/calculator/')) ?>">
                <span class="card__title">Калькулятор</span>
                <p class="card__text">Расчёт сметы по вашим размерам с расшифровкой всех позиций.</p>
            </a>
            <a class="card" href="<?= e(site_url('/prices/')) ?>">
                <span class="card__title">Цены</span>
                <p class="card__text">Полный прайс: полотна, профили, свет, дополнительные работы.</p>
            </a>
            <a class="card" href="<?= e(site_url('/ceilings/')) ?>">
                <span class="card__title">Виды потолков</span>
                <p class="card__text">Матовые, сатиновые, глянцевые, тканевые — с ограничениями.</p>
            </a>
            <a class="card" href="<?= e(site_url('/geography/')) ?>">
                <span class="card__title">Где мы работаем</span>
                <p class="card__text">Москва и города области, условия выезда.</p>
            </a>
        </div>

        <p style="margin-top:2rem">
            <a class="link-arrow" href="<?= e(site_url('/sitemap/')) ?>">Открыть карту сайта</a>
        </p>
    </div>
</section>
