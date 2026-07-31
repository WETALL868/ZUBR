<?php
/**
 * Карточка товара в каталоге — общий фрагмент.
 *
 * Используется и на главной, и на страницах категорий. Раньше эта разметка
 * была скопирована по два раза на каждый товар (главная + категория), то есть
 * 24 копии, и добавление товара требовало вписать её руками ещё дважды.
 *
 * Ожидает $card (строка таблицы products с присоединённой категорией).
 */

$cardPrice = repo_price($card);
$cardOld   = repo_old_price($card);
$cardSpecs = cms_all(
    'SELECT name, value FROM product_card_specs WHERE product_id = ? ORDER BY sort_order, id',
    [(int)$card['id']]
);
$cardTags = array_values(array_filter(array_map('trim', explode(',', (string)$card['card_tags']))));
$cardName = $card['card_title'] ?: ($card['short_name'] ?: $card['name']);
$stockCode = (string)$card['availability'];
?>
          <?php /* data-old-price — та самая зачёркнутая цена. Из неё считается
                   строка «Скидка» в оформлении заказа: выгода настоящая, взята
                   из карточки товара, а не придумана к распродаже. Пусто —
                   строки скидки не будет. */ ?>
          <article class="model-card<?= (int)$card['is_featured'] === 1 ? ' featured' : '' ?>" id="<?= e($card['slug']) ?>" data-price="<?= e(cms_money_machine($cardPrice)) ?>"<?= $cardOld !== null ? ' data-old-price="' . e(cms_money_machine($cardOld)) . '"' : '' ?> data-unit="<?= e((string)$card['unit']) ?>" data-stock="<?= e($stockCode) ?>"<?= repo_is_orderable($card) ? '' : ' data-orderable="false"' ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="<?= e((string)($card['card_figure_label'] ?: 'Увеличить фото ' . $cardName)) ?>">
              <?= repo_image_tag($card, (string)($card['card_alt'] ?: $card['name']), true) ?>

            </figure>
            <span class="model-stock stock-<?= e($stockCode) ?>"><?= e(repo_stock_label($card)) ?></span>

            <h3><?= e($cardName) ?></h3>
            <p class="model-price<?= $cardPrice === null ? ' price-unknown' : '' ?>"><?= e(cms_money($cardPrice)) ?><?php
              if ($cardOld !== null): ?> <s class="price-old"><?= e(cms_money($cardOld)) ?></s><?php endif; ?></p>

            <p><?= e((string)($card['card_lead'] ?: $card['lead'] ?: $card['description'])) ?></p>
            <?php if ($cardSpecs): ?>
            <dl class="model-specs">
              <?php foreach ($cardSpecs as $spec): ?><div><dt><?= e($spec['name']) ?></dt><dd><?= e((string)$spec['value']) ?></dd></div>
              <?php endforeach; ?>
            </dl>
            <?php endif; ?>
            <?php if ($cardTags): ?>
            <ul class="model-tags" aria-label="Особенности <?= e($card['model'] ?: $cardName) ?>">
              <?php foreach ($cardTags as $tag): ?><li><?= e($tag) ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </article>
