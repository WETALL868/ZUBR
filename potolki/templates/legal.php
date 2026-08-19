<?php
/**
 * Юридический документ.
 *
 * Пока не заполнены реквизиты оператора, документ:
 *   • отдаётся с noindex, follow;
 *   • показывает предупреждение вместо того, чтобы выдавать
 *     недооформленный текст за действующий.
 * Ссылки на документ при этом работают — они нужны формам.
 */

$doc = content_item('legal', $slug);

if ($doc === null) {
    render_404();
}

$ready = legal_data_ready();
$versionKey = $doc['version'] ?? null;
$version = $versionKey !== null ? config('consent.' . $versionKey) : null;

seo([
    'title'       => $doc['seo']['title'] . ' | ' . config('brand.name'),
    'description' => $doc['seo']['description'],
    'h1'          => $doc['title'],
    'robots'      => $ready ? 'index, follow' : 'noindex, follow',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Документы', 'url' => '/legal/requisites/'],
        ['title' => $doc['title'], 'url' => '/legal/' . $slug . '/'],
    ],
]);
?>

<section class="section section--tight">
    <div class="container container--narrow">
        <div class="eyebrow">Документ</div>
        <h1><?= e($doc['title']) ?></h1>

        <?php if ($version !== null): ?>
            <p class="meta-line" style="margin-top:1rem">
                <span>Версия <?= e((string) $version['version']) ?></span>
                <span>Действует с <?= e(date_ru((string) $version['date'])) ?></span>
            </p>
        <?php endif; ?>

        <p class="lead" style="margin-top:1.5rem"><?= e(legal_placeholders((string) $doc['lead'])) ?></p>

        <?php if (!$ready): ?>
            <div class="notice notice--warn" style="margin-top:2rem">
                <strong>Документ не готов к публикации.</strong>
                Не заполнены реквизиты оператора персональных данных: наименование, ИНН и юридический адрес.
                До их внесения текст размещён с заглушками и закрыт от индексации.
                Данные вносятся в config/site.php, раздел legal.
            </div>
        <?php endif; ?>

        <?php if (!empty($doc['requisites_block'])): ?>
            <dl class="speclist" style="margin-top:2rem">
                <div><dt>Наименование</dt><dd><?= e(legal_placeholders('{operator}')) ?></dd></div>
                <div><dt>ИНН</dt><dd><?= e(legal_placeholders('{inn}')) ?></dd></div>
                <?php if (filled(config('legal.kpp'))): ?>
                    <div><dt>КПП</dt><dd><?= e((string) config('legal.kpp')) ?></dd></div>
                <?php endif; ?>
                <div><dt>ОГРН / ОГРНИП</dt><dd><?= e(legal_placeholders('{ogrn}')) ?></dd></div>
                <div><dt>Юридический адрес</dt><dd><?= e(legal_placeholders('{address}')) ?></dd></div>
                <?php if (filled(config('legal.postal_address'))): ?>
                    <div><dt>Почтовый адрес</dt><dd><?= e((string) config('legal.postal_address')) ?></dd></div>
                <?php endif; ?>
                <?php if (filled(config('contacts.phone.raw'))): ?>
                    <div><dt>Телефон</dt><dd><?= e((string) config('contacts.phone.display')) ?></dd></div>
                <?php endif; ?>
                <div><dt>Электронная почта</dt><dd><?= e(legal_placeholders('{email}')) ?></dd></div>
                <div><dt>Сайт</dt><dd><?= e((string) config('site.domain')) ?></dd></div>
            </dl>

            <?php if (config('legal.rkn_notified') === false): ?>
                <div class="notice notice--warn" style="margin-top:1.5rem">
                    <strong>Напоминание владельцу.</strong>
                    Уведомление об обработке персональных данных в Роскомнадзор не подано.
                    Проверьте необходимость подачи до начала обработки данных посетителей.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="prose" style="margin-top:2.5rem">
            <?php foreach ($doc['sections'] as $section): ?>
                <h2><?= e($section['h']) ?></h2>

                <?php foreach ((array) ($section['p'] ?? []) as $paragraph): ?>
                    <p><?= e(legal_placeholders($paragraph)) ?></p>
                <?php endforeach; ?>

                <?php if (!empty($section['ul'])): ?>
                    <ul>
                        <?php foreach ($section['ul'] as $line): ?>
                            <li><?= e(legal_placeholders($line)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php foreach ((array) ($section['p2'] ?? []) as $paragraph): ?>
                    <p><?= e(legal_placeholders($paragraph)) ?></p>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <h2 style="margin-top:3rem">Другие документы</h2>
        <ul class="geo-list" style="margin-top:1rem">
            <?php foreach (content('legal') as $otherSlug => $other): ?>
                <?php if ($otherSlug === $slug) { continue; } ?>
                <li><a href="<?= e(site_url('/legal/' . $otherSlug . '/')) ?>"><?= e($other['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
