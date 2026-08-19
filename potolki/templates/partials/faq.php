<?php
/**
 * Блок вопросов и ответов.
 * Переменные: $faq (пары q/a), $faqTitle, $faqSchema (добавлять ли разметку).
 */

$faq = faq_pairs($faq ?? []);

if ($faq === []) {
    return;
}

if ($faqSchema ?? true) {
    schema_faq($faq);
}
?>
<div class="faq">
    <?php foreach ($faq as $item): ?>
        <details>
            <summary><?= e($item['q']) ?></summary>
            <div class="faq__answer"><?= e($item['a']) ?></div>
        </details>
    <?php endforeach; ?>
</div>
