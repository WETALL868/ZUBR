<?php
/**
 * Настройки сайта.
 *
 * Всё, что раньше было вписано в разметку каждой страницы: телефон, почта,
 * адрес, реквизиты, счётчик Яндекс.Метрики. Правка здесь применяется ко
 * всему сайту сразу.
 *
 * Паролей здесь нет: пароль почтового ящика и токен Telegram живут в файле
 * вне публичной выдачи, потому что база чаще попадает в резервные копии и
 * выгрузки, чем отдельный файл конфигурации.
 */

declare(strict_types=1);

/** Что показываем: группа => [ключ => [подпись, тип, подсказка]]. */
$groups = [
    'Сайт' => ['site' => [
        'base_url'    => ['Адрес сайта', 'text', 'С https:// и без косой черты в конце.'],
        'name'        => ['Название', 'text', ''],
        'legal_name'  => ['Юридическое лицо', 'text', ''],
        'legal_short' => ['Сокращённо (для подвала)', 'text', ''],
        'footer_text' => ['Текст в подвале страниц товаров', 'textarea', ''],
    ]],
    'Контакты' => ['contacts' => [
        'phone'     => ['Телефон как показывать', 'text', ''],
        'phone_raw' => ['Телефон для ссылки', 'text', 'Без пробелов и скобок: +74993221311'],
        'email'     => ['Почта', 'text', ''],
        'address'   => ['Адрес', 'text', ''],
        'hours'     => ['Часы работы', 'text', ''],
    ]],
    'Организация для поисковых систем' => ['business' => [
        'legal_name'    => ['Полное наименование', 'text', ''],
        'description'   => ['Описание', 'textarea', ''],
        'tax_id'        => ['ИНН', 'text', ''],
        'street'        => ['Улица и дом', 'text', ''],
        'locality'      => ['Город', 'text', ''],
        'postal_code'   => ['Индекс', 'text', ''],
        'country'       => ['Страна', 'text', 'Код из двух букв: RU'],
        'opening_hours' => ['Часы работы для микроразметки', 'text', 'Mo-Fr 09:00-18:00'],
    ]],
    'Поиск и статистика' => ['seo' => [
        'metrika_id'          => ['Номер счётчика Яндекс.Метрики', 'text', ''],
        'yandex_verification' => ['Код подтверждения в Яндекс.Вебмастере', 'text', ''],
        'default_title'       => ['Заголовок для страниц без своего', 'text', ''],
        'default_description' => ['Описание для страниц без своего', 'textarea', ''],
    ]],
    'Главная страница' => ['home' => [
        'list_name'   => ['Название списка товаров для поисковика', 'text', ''],
        'list_desc'   => ['Описание списка', 'textarea', ''],
        'footer_text' => ['Текст в подвале главной', 'textarea', ''],
    ]],
    'Баннер cookie' => ['cookie' => [
        'title'  => ['Заголовок', 'text', ''],
        'text'   => ['Текст согласия', 'textarea',
            'Баннер висит поверх страницы, и на телефоне его высота зависит от длины этого текста. '
            . 'Чем короче — тем меньше он закрывает. Ссылку можно оставить тегом <a href="/privacy_policy/">…</a>.'],
        'button' => ['Надпись на кнопке', 'text', ''],
    ]],
    'Фид Яндекс.Маркета' => ['yml' => [
        'shop_name'         => ['Название магазина', 'text', ''],
        'currency'          => ['Валюта', 'text', 'RUR'],
        'utm'               => ['Метки к ссылкам', 'text', ''],
        'default_country'   => ['Страна-изготовитель по умолчанию', 'text', ''],
        'sales_notes'       => ['Примечание к предложению', 'textarea', ''],
        'cache_ttl_seconds' => ['Как часто пересобирать фид, секунд', 'text', ''],
    ]],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $saved = 0;
    foreach ($groups as $section) {
        foreach ($section as $group => $keys) {
            foreach ($keys as $key => $meta) {
                $field = $group . '__' . $key;
                if (!array_key_exists($field, $_POST)) {
                    continue;
                }
                $value = trim((string)$_POST[$field]);
                if ((string)cms_setting($group, $key, '') !== $value) {
                    cms_setting_save($group, $key, $value);
                    $saved++;
                }
            }
        }
    }

    cms_audit('settings.save', null, null, 'Изменено настроек: ' . $saved);
    admin_flash($saved > 0 ? "Сохранено. Изменено настроек: $saved." : 'Изменений не было.');
    admin_redirect('settings');
}

require __DIR__ . '/../layout/header.php';
?>
        <div class="adm-head">
          <div>
            <h1>Настройки</h1>
            <p>Телефон, почта, реквизиты и счётчик — одно место на весь сайт.</p>
          </div>
        </div>

        <form method="post" action="<?= e(admin_url('settings')) ?>">
          <?= admin_csrf_field() ?>
          <?php foreach ($groups as $sectionTitle => $section): ?>
          <div class="adm-card">
            <h2><?= e($sectionTitle) ?></h2>
            <?php foreach ($section as $group => $keys): ?>
            <div class="adm-cols">
              <?php foreach ($keys as $key => [$label, $type, $hint]):
                  $field = $group . '__' . $key;
                  $value = (string)cms_setting($group, $key, ''); ?>
              <div class="adm-field">
                <label for="<?= e($field) ?>"><?= e($label) ?></label>
                <?php if ($type === 'textarea'): ?>
                <textarea id="<?= e($field) ?>" name="<?= e($field) ?>"><?= e($value) ?></textarea>
                <?php else: ?>
                <input id="<?= e($field) ?>" name="<?= e($field) ?>" type="text" value="<?= e($value) ?>" />
                <?php endif; ?>
                <?php if ($hint !== ''): ?><p class="note"><?= e($hint) ?></p><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>

          <div class="adm-actions">
            <button class="adm-btn" type="submit">Сохранить настройки</button>
          </div>
        </form>

        <div class="adm-card" style="margin-top:18px">
          <h2>Что настраивается не здесь</h2>
          <p class="hint">
            Доступ к базе — в файле <code>config/database.php</code>.
            Пароль почтового ящика и токен Telegram — в <code>api/mail-config.php</code>.
            Оба файла закрыты от посторонних: по HTTP сервер отвечает «доступ запрещён».
            В базе паролей нет намеренно — базу выгружают в резервные копии чаще, чем отдельный файл.
          </p>
        </div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
