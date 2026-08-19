<?php
/**
 * Отзывы.
 *
 * Модуль выключен по умолчанию (config/site.php → features.reviews).
 * Включайте только когда появятся реальные отзывы с согласием на публикацию:
 * см. /legal/review-publication-consent/.
 *
 * Разметка Review и AggregateRating здесь сознательно НЕ добавляется:
 * размечать выдуманные оценки — прямой путь к ручным санкциям.
 */

seo([
    'title'       => 'Отзывы клиентов — ' . config('brand.name'),
    'description' => 'Отзывы о работе компании: реальные обращения с согласием на публикацию.',
    'h1'          => 'Отзывы',
    'breadcrumbs' => [
        ['title' => 'Главная', 'url' => '/'],
        ['title' => 'Отзывы', 'url' => '/reviews/'],
    ],
]);

$reviews = content('reviews');
?>

<section class="section section--tight">
    <div class="container">
        <div class="section-head section-head--split">
            <div>
                <div class="eyebrow">Мнения клиентов</div>
                <h1>Отзывы</h1>
            </div>
            <p class="lead">
                Публикуем только те отзывы, авторы которых дали согласие на публикацию.
                Контакты и адреса объектов не раскрываем.
            </p>
        </div>

        <?php if ($reviews === []): ?>
            <div class="notice notice--empty">
                <strong>Отзывов пока нет.</strong>
                Мы не пишем отзывы за клиентов и не покупаем их. Как только появятся реальные —
                с согласием на публикацию — они будут здесь.
            </div>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach ($reviews as $review): ?>
                    <blockquote class="card">
                        <p class="card__text" style="color:var(--ink);font-size:var(--step-body)"><?= e((string) $review['text']) ?></p>
                        <span class="card__meta">
                            <span><?= e((string) $review['author']) ?></span>
                            <span><?= e(date_ru($review['date'] ?? null)) ?></span>
                        </span>
                    </blockquote>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Хотите оставить отзыв?';
$ctaText  = 'Напишите нам — и укажите в комментарии, согласны ли вы на публикацию. Без согласия мы ничего не публикуем.';
$ctaForm  = ['form' => 'question', 'button' => 'Отправить отзыв'];
include APP_ROOT . '/templates/partials/cta.php';
?>
