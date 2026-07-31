<?php
/**
 * Печатная форма заказа — то, что кладут в коробку и передают в бухгалтерию.
 *
 * Отдельная страница без меню панели: печатается лист, а не экран.
 * Ожидает $order и $items.
 */

declare(strict_types=1);
?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Заказ <?= e((string)$order['number']) ?></title>
    <style>
      body { font: 14px/1.5 ui-sans-serif, system-ui, "Segoe UI", Roboto, sans-serif; color: #111; margin: 24px; }
      h1 { font-size: 20px; margin: 0 0 4px; }
      .muted { color: #666; }
      table { width: 100%; border-collapse: collapse; margin: 16px 0; }
      th, td { border: 1px solid #ccc; padding: 7px 10px; text-align: left; }
      th { background: #f3f5f4; }
      td.num, th.num { text-align: right; white-space: nowrap; }
      .two { display: flex; gap: 40px; flex-wrap: wrap; }
      .two > div { min-width: 240px; }
      .total { font-size: 16px; font-weight: 700; }
      @media print { .noprint { display: none; } body { margin: 0; } }
    </style>
  </head>
  <body>
    <p class="noprint"><button type="button" onclick="window.print()">Печать</button></p>

    <h1>Заказ <?= e((string)$order['number']) ?></h1>
    <p class="muted">
      <?= e(date('d.m.Y H:i', strtotime((string)$order['created_at']))) ?>
      · <?= e(ORDER_STATUSES[$order['status']] ?? (string)$order['status']) ?>
    </p>

    <div class="two">
      <?php
        $printType = (string)($order['customer_type'] ?? 'individual') === 'legal' ? 'legal' : 'individual';
        $printLegal = $printType === 'legal' ? orders_legal_details($order) : [];
      ?>
      <div>
        <h2 style="font-size:15px">Покупатель</h2>
        <p>
          <b><?= e(ORDER_CUSTOMER_TYPES[$printType]) ?></b><br />
          <?= e((string)$order['customer_name']) ?><br />
          <?= e((string)$order['phone']) ?>
          <?php if ($order['email']): ?><br /><?= e((string)$order['email']) ?><?php endif; ?>
        </p>
      </div>
      <div>
        <h2 style="font-size:15px">Доставка</h2>
        <p>
          <?= e((string)($order['delivery_title'] ?: '—')) ?><br />
          <?php if (!empty($order['delivery_term'])): ?>Срок: <?= e((string)$order['delivery_term']) ?><br /><?php endif; ?>
          <?php if (!empty($order['region'])): ?><?= e((string)$order['region']) ?><br /><?php endif; ?>
          <?php if ($order['city']): ?><?= e((string)$order['city']) ?><br /><?php endif; ?>
          <?php if ($order['address']): ?><?= nl2br(e((string)$order['address'])) ?><br /><?php endif; ?>
          Оплата: <?= e((string)($order['payment'] ?: '—')) ?>
        </p>
      </div>
      <div>
        <h2 style="font-size:15px">Продавец</h2>
        <p>
          <?= e((string)cms_setting('site', 'legal_name', '')) ?><br />
          ИНН <?= e((string)cms_setting('business', 'tax_id', '')) ?><br />
          <?= e((string)cms_setting('contacts', 'address', '')) ?><br />
          <?= e((string)cms_setting('contacts', 'phone', '')) ?>
        </p>
      </div>
    </div>

    <?php if ($printLegal): ?>
    <h2 style="font-size:15px">Реквизиты организации</h2>
    <table>
      <tbody>
        <?php foreach ($printLegal as $label => $value): ?>
        <tr><th style="text-align:left;width:220px"><?= e($label) ?></th><td><?= nl2br(e($value)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <?php if ($items): ?>
    <table>
      <thead>
        <tr><th>№</th><th>Товар</th><th>Артикул</th><th class="num">Цена</th><th class="num">Кол-во</th><th class="num">Сумма</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e((string)$item['title']) ?></td>
          <td><?= e((string)$item['sku']) ?></td>
          <td class="num"><?= e(cms_money((float)$item['price'])) ?></td>
          <td class="num"><?= (int)$item['qty'] ?></td>
          <td class="num"><?= e(cms_money((float)$item['total'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="5" class="num">Товары</td>
          <td class="num"><?= e(cms_money((float)$order['items_total'])) ?></td>
        </tr>
        <tr>
          <td colspan="5" class="num">Доставка</td>
          <td class="num"><?= $order['delivery_price'] === null
              ? 'по тарифам службы'
              : e(cms_money((float)$order['delivery_price'])) ?></td>
        </tr>
        <tr class="total">
          <td colspan="5" class="num">Итого</td>
          <td class="num"><?= e(cms_money((float)$order['total'])) ?></td>
        </tr>
      </tbody>
    </table>
    <?php endif; ?>

    <?php if ($order['comment']): ?>
    <p><b>Комментарий покупателя:</b> <?= nl2br(e((string)$order['comment'])) ?></p>
    <?php endif; ?>

    <p class="muted">Цены указаны на момент оформления заказа.</p>
  </body>
</html>
