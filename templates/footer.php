<?php
/**
 * Общий подвал сайта. Ссылки на правовые страницы берутся из таблицы pages:
 * страница, отмеченная «показывать в подвале», появляется здесь сама.
 *
 * $bodyScripts — список скриптов конкретной страницы.
 */
$bodyScripts = $bodyScripts ?? [];
?>
    <footer class="site-footer">
      <div class="footer-brand">
        <a class="brand footer-logo" href="/" aria-label="Comp-Uter">
          <span class="brand-image brand-image-full">
            <img src="/public/assets/comp-uter-logo-full.webp" alt="" />
          </span>
        </a>
        <p><?= e((string)($footerText ?? cms_setting('site', 'footer_text', ''))) ?></p>
      </div>
      <div class="footer-links">
<?php if (!empty($footerCategories)): foreach (repo_categories() as $footerCategory): ?>
        <a href="<?= e(repo_category_url($footerCategory)) ?>"><?= e($footerCategory['name']) ?></a>
        <?php endforeach; endif; ?>
        <?php foreach (cms_all('SELECT slug, title, menu_title FROM pages WHERE status = ? AND in_footer = 1 ORDER BY sort_order, id', ['published']) as $footerPage): ?>
        <a href="/<?= e($footerPage['slug']) ?>/"><?= e((string)($footerPage['menu_title'] ?: $footerPage['title'])) ?></a>
        <?php endforeach; ?>
        <a href="/#requisites">Реквизиты для счета</a>
        <a href="/#order">Оформить заказ</a>
      </div>
      <div class="footer-bottom">
        <span><?= e((string)cms_setting('site','legal_short','ИП Михайловский В.Г.')) ?> · <?= e((string)cms_setting('contacts','phone','+7 (499) 322-13-11')) ?> · <?= e((string)cms_setting('contacts','email','info@comp-uter.ru')) ?></span>
        <span>© 2026 Comp-Uter. Все права защищены. Копирование материалов сайта запрещено.</span>
      </div>
    </footer>

<?php if (!empty($showOverlays)) { require __DIR__ . '/partials/overlays.php'; } ?>
<?php foreach ($bodyScripts as $script): ?>
    <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
  </body>
</html>
