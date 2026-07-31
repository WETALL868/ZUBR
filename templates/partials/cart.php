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
      <?php
        /*
         * Способ получения выбирается на оформлении заказа, а не здесь.
         *
         * Раньше он был и там, и тут: два одинаковых набора переключателей на
         * одной странице, каждый со своим состоянием. Покупатель выбирал
         * доставку в корзине, доходил до формы и видел там другой выбор.
         * Теперь корзина отвечает за состав заказа, а доставка, оплата и
         * реквизиты — за оформление.
         */
      ?>
      <dl class="cart-totals">
        <div class="cart-grand-total">
          <dt>Товары</dt>
          <dd data-cart-subtotal>0 ₽</dd>
        </div>
      </dl>
      <p class="cart-error" data-cart-error role="status" aria-live="polite"></p>
      <button class="button primary cart-checkout" type="button" data-cart-checkout>Оформить заказ</button>
      <p class="cart-note">На следующем шаге выберете доставку и оплату. Менеджер подтвердит наличие и документы в рабочее время.</p>
    </aside>
