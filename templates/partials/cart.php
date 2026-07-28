<?php
/**
 * Корзина — общий фрагмент для всех страниц каталога.
 *
 * Способы доставки и их стоимость приходят из таблицы delivery_methods:
 * раньше они были прописаны в разметке трёх страниц одновременно.
 */
require_once __DIR__ . '/../../cms/repo.php';
?>
    <div class="cart-backdrop" data-cart-close hidden></div>
    <aside class="cart-drawer" aria-hidden="true" aria-labelledby="cart-title">
      <div class="cart-drawer-head">
        <div>
          <p class="section-label">Корзина</p>
          <h2 id="cart-title">Ваш заказ</h2>
        </div>
        <button class="cart-close" type="button" data-cart-close aria-label="Закрыть корзину">×</button>
      </div>
      <p class="cart-empty" data-cart-empty>Корзина пока пустая. Добавьте процессор или накопитель из каталога, чтобы оформить заказ на сайте.</p>
      <div class="cart-list" data-cart-list></div>
      <section class="cart-delivery" aria-labelledby="cart-delivery-title">
        <h3 id="cart-delivery-title">Способ получения</h3>
        <?php foreach (repo_delivery_methods() as $i => $method):
            $isFree   = $method['price'] !== null && (float)$method['price'] <= 0;
            $byTariff = $method['price'] === null;
        ?>
        <label class="delivery-option">
          <input type="radio" name="cartDelivery" value="<?= e($method['title']) ?>" data-delivery-price="<?= $byTariff ? '' : ($isFree ? '0' : e(cms_money_machine((float)$method['price']))) ?>" data-delivery-title="<?= e((string)($method['option_title'] ?: $method['title'])) ?>"<?= (int)$method['needs_address'] === 1 ? ' data-delivery-details="true"' : '' ?><?= $i === 0 ? ' checked' : '' ?> />
          <span>
            <strong><?= e($method['title']) ?></strong>
            <em><?= e(str_replace('{цена}',
                $byTariff ? 'по тарифам службы доставки' : ($isFree ? 'бесплатно' : cms_money((float)$method['price'])),
                (string)$method['description'])) ?></em>
          </span>
        </label>
        <?php endforeach; ?>
      </section>
      <div class="cart-delivery-fields" data-delivery-fields>
        <label>
          Город или населенный пункт
          <input type="text" data-delivery-city placeholder="Например, Москва, Подольск, Казань" />
        </label>
        <label>
          Адрес, пункт выдачи или пожелание
          <textarea data-delivery-address rows="3" placeholder="Адрес доставки, пункт СДЭК или удобный способ получения"></textarea>
        </label>
        <label>
          Комментарий по доставке
          <input type="text" data-delivery-comment placeholder="Например: СДЭК до ПВЗ, Яндекс Маркет, Ozon, ТК Деловые линии" />
        </label>
      </div>
      <dl class="cart-totals">
        <div>
          <dt>Товары</dt>
          <dd data-cart-subtotal>0 ₽</dd>
        </div>
        <div>
          <dt>Доставка</dt>
          <dd data-cart-delivery-total>Самовывоз бесплатно</dd>
        </div>
        <div class="cart-grand-total">
          <dt>Итого</dt>
          <dd data-cart-grand-total>0 ₽</dd>
        </div>
      </dl>
      <p class="cart-error" data-cart-error role="status" aria-live="polite"></p>
      <button class="button primary cart-checkout" type="button" data-cart-checkout>Оформить заказ</button>
      <p class="cart-note">После оформления менеджер подтвердит наличие, доставку, документы и способ оплаты в рабочее время.</p>
    </aside>
