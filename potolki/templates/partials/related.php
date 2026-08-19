<?php
/**
 * Перелинковка: связанные решения, материалы и территории.
 * Переменные: $relatedTitle, $related (ссылки из related_links / page_links / blog_links).
 */

$related = $related ?? [];

if ($related === []) {
    return;
}
?>
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2><?= e($relatedTitle ?? 'Смотрите также') ?></h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($related as $link): ?>
                <a class="card" href="<?= e(site_url($link['url'])) ?>">
                    <span class="card__title"><?= e($link['title']) ?></span>
                    <?php if (filled($link['text'] ?? ($link['lead'] ?? null))): ?>
                        <p class="card__text"><?= e(mb_substr((string) ($link['text'] ?? $link['lead']), 0, 130)) ?>…</p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
