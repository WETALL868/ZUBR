<?php
/**
 * Общие модальные блоки страниц каталога: быстрый просмотр товара,
 * баннер cookie и окно «заявка принята».
 *
 * Разметка одинакова на главной и на страницах категорий — раньше она была
 * скопирована в каждую из них.
 */
?>
    <div class="photo-viewer" aria-hidden="true">
      <button class="photo-viewer-close" type="button" aria-label="Закрыть карточку товара">×</button>
      <article class="photo-viewer-frame product-dialog" role="dialog" aria-modal="true" aria-labelledby="product-dialog-title">
        <div class="product-dialog-media">
          <img alt="" />
        </div>
        <div class="product-dialog-copy">
          <p class="section-label">Карточка товара</p>
          <h3 id="product-dialog-title"></h3>
          <p class="product-dialog-price"></p>
          <p class="product-dialog-lead"></p>
          <dl class="product-dialog-specs"></dl>
          <ul class="product-dialog-tags" aria-label="Особенности модели"></ul>
          <div class="product-page-actions">
            <button class="button primary product-dialog-order" type="button">В корзину</button>
            <a class="button secondary product-dialog-full-link" data-product-full-link href="/#stock">Открыть карточку товара</a>
          </div>
        </div>
      </article>
    </div>

    <?php
      /*
       * Текст согласия — из настроек, а не из разметки. Баннер висит поверх
       * страницы, и на телефоне его высота целиком зависит от длины этого
       * текста: короче текст — меньше закрыто. Раз это юридическая
       * формулировка, менять её должен владелец сайта, а не программист.
       */
    ?>
    <div class="cookie-banner" role="dialog" aria-live="polite" aria-label="Согласие на использование cookie">
      <div>
        <strong><?= e((string)cms_setting('cookie', 'title', 'Cookie и персональные данные')) ?></strong>
        <p>
          <?= cms_setting('cookie', 'text',
              'Мы используем cookie для работы сайта и обработки заявок. Нажимая «Согласен», вы подтверждаете согласие'
              . "\n" . '          с использованием cookie и можете ознакомиться с <a href="/privacy_policy/">политикой конфиденциальности</a>.') ?>
        </p>
      </div>
      <button class="button primary cookie-accept" type="button"><?= e((string)cms_setting('cookie', 'button', 'Согласен')) ?></button>
    </div>

    <div class="order-success" aria-hidden="true">
      <button class="order-success-close" type="button" aria-label="Закрыть сообщение о заказе">×</button>
      <article class="order-success-card" role="dialog" aria-modal="true" aria-labelledby="order-success-title">
        <p class="section-label">Заказ оформлен</p>
        <h2 id="order-success-title">ЗАЯВКА ПРИНЯТА</h2>
        <p class="order-success-number">Номер заявки: <strong></strong></p>
        <p>Мы свяжемся с вами в ближайшее время, уточним детали заказа, доставку и документы.</p>
        <button class="button primary order-success-ok" type="button">Хорошо</button>
      </article>
    </div>
    <!-- /order-success -->
