<?php

/**
 * Схема базы данных Comp-Uter CMS.
 *
 * Схема описана здесь один раз и разворачивается и на MySQL/MariaDB, и на
 * SQLite: типы подставляются под нужный движок. Так владельцу не нужно
 * выбирать между двумя расходящимися .sql-файлами, а установщик может
 * поднять базу на любом хостинге.
 *
 * Готовый MySQL-дамп для ручного импорта: cms/schema.mysql.sql
 * (он генерируется отсюда командой `php cms/schema.php --dump-mysql`).
 *
 * Принципы схемы:
 *   - товар описан одной строкой в products; всё, чего может быть много
 *     (фотографии, характеристики, FAQ, связи «похожие»), вынесено в
 *     отдельные таблицы, а не в одну неуправляемую строку;
 *   - у категорий есть parent_id — вложенность доступна сразу, хотя сейчас
 *     обе категории верхнего уровня;
 *   - SEO-поля лежат рядом с сущностью, а не в отдельной таблице: их всегда
 *     читают вместе с товаром или страницей, отдельная таблица дала бы лишний
 *     JOIN на каждой странице сайта без выигрыша;
 *   - цена хранится в DECIMAL(12,2), а не во float: копейки в float округляются
 *     непредсказуемо, а суммы заказа должны сходиться до копейки;
 *   - у заказа цена товара скопирована в order_items — заказ обязан
 *     показывать цену на момент покупки, даже если товар потом подорожал.
 */

declare(strict_types=1);

/**
 * @return array<string,string[]> имя таблицы => список определений столбцов
 *                                и индексов в переносимом виде
 */
function cms_schema(): array
{
    return [
        // ---------------------------------------------------- администраторы
        'admin_users' => [
            'id            {PK}',
            'name          {STR:120} NOT NULL',
            'login         {STR:120} NOT NULL',
            'email         {STR:190} NULL',
            'password_hash {STR:255} NOT NULL',
            'role          {STR:20}  NOT NULL DEFAULT \'manager\'',   // owner | manager
            'is_active     {BOOL}    NOT NULL DEFAULT 1',
            'session_epoch {INT}     NOT NULL DEFAULT 0',             // «выйти со всех устройств»
            'last_login_at {DT}      NULL',
            'last_login_ip {STR:45}  NULL',
            'created_at    {DT}      NOT NULL',
            'updated_at    {DT}      NULL',
            '{UNIQUE} ux_admin_login (login)',
        ],

        // Попытки входа — для ограничения перебора паролей.
        'admin_login_attempts' => [
            'id         {PK}',
            'login      {STR:120} NOT NULL',
            'ip         {STR:45}  NOT NULL',
            'success    {BOOL}    NOT NULL DEFAULT 0',
            'created_at {DT}      NOT NULL',
            '{INDEX} ix_attempts_ip (ip, created_at)',
            '{INDEX} ix_attempts_login (login, created_at)',
        ],

        // ---------------------------------------------------------- категории
        'categories' => [
            'id            {PK}',
            'parent_id     {INT}     NULL',
            'name          {STR:190} NOT NULL',
            'slug          {STR:190} NOT NULL',
            'h1            {STR:190} NULL',
            'lead          {TEXT}    NULL',   // короткое описание над каталогом
            'label         {STR:120} NULL',   // «Каталог · категория 1 из 2»
            'stock_label   {STR:120} NULL',   // подпись над сеткой товаров
            'stock_title   {STR:190} NULL',   // заголовок секции с товарами
            'list_name     {STR:255} NULL',   // name в микроразметке ItemList
            'list_desc     {TEXT}    NULL',   // description там же
            // Тексты, которые на прежних страницах были разными у каждой
            // категории и не выводятся из названия.
            'selection_text {TEXT}    NULL',   // второй абзац вступления
            'request_goal   {STR:190} NULL',   // data-goal у кнопок подбора
            'grid_anchor    {STR:60}  NULL',   // id секции с товарами
            'request_badge  {STR:60}  NULL',   // «Xeon» / «HDD» в карточке «нужен другой»
            'request_status {STR:60}  NULL',   // «Под заказ» там же
            'request_tags   {TEXT}    NULL',   // список меток через запятую
            'request_title  {STR:190} NULL',
            'request_text   {TEXT}    NULL',
            'request_button {STR:120} NULL',
            'cross_text     {TEXT}    NULL',   // текст в блоке «другая категория»
            'cross_button   {STR:120} NULL',
            'footer_text    {TEXT}    NULL',   // абзац о компании в подвале
            'description   {LONGTEXT} NULL',  // SEO-текст, визуальный редактор
            'image         {STR:255} NULL',
            'seo_title     {STR:255} NULL',
            'seo_desc      {TEXT}    NULL',
            'canonical     {STR:255} NULL',
            'og_title      {STR:255} NULL',
            'og_desc       {TEXT}    NULL',
            'og_image      {STR:255} NULL',
            'noindex       {BOOL}    NOT NULL DEFAULT 0',
            'yml_category_id {INT}   NULL',   // categoryId в фиде Яндекс.Маркета
            'sort_order    {INT}     NOT NULL DEFAULT 0',
            'is_published  {BOOL}    NOT NULL DEFAULT 1',
            'created_at    {DT}      NOT NULL',
            'updated_at    {DT}      NULL',
            '{UNIQUE} ux_categories_slug (slug)',
            '{INDEX}  ix_categories_parent (parent_id)',
        ],

        // ------------------------------------------------------------ товары
        'products' => [
            'id             {PK}',
            'category_id    {INT}     NULL',
            'name           {STR:255} NOT NULL',   // полное название (для фида)
            'short_name     {STR:190} NULL',       // как в карточке каталога
            'model          {STR:190} NULL',       // «Xeon E5-2690 V4» — для хлебных крошек
            'slug           {STR:190} NOT NULL',   // = адрес /products/<slug>/
            'sku            {STR:100} NULL',       // артикул
            'mpn            {STR:100} NULL',
            'brand          {STR:100} NULL',
            'lead           {TEXT}    NULL',       // краткое описание
            'description    {LONGTEXT} NULL',      // полное описание
            'article_title  {STR:255} NULL',       // заголовок SEO-статьи
            'article_html   {LONGTEXT} NULL',      // сама SEO-статья
            'price          {DEC}     NULL',
            'old_price      {DEC}     NULL',
            'cost_price     {DEC}     NULL',       // закупка, покупателю не видна
            'stock_qty      {INT}     NULL',
            'availability   {STR:20}  NOT NULL DEFAULT \'in_stock\'',
            'unit           {STR:20}  NOT NULL DEFAULT \'шт.\'',
            'warranty       {STR:100} NULL',
            'condition_note {STR:100} NULL',       // «OEM, без кулера» и т.п.
            'country        {STR:100} NULL',
            'is_published   {BOOL}    NOT NULL DEFAULT 1',
            'is_featured    {BOOL}    NOT NULL DEFAULT 0',
            // Тексты карточки каталога: заголовок, краткий текст, подпись к
            // фото и список меток. В карточке они короче, чем на странице.
            'card_title        {STR:255} NULL',
            'card_lead         {TEXT}    NULL',
            'card_alt          {STR:255} NULL',
            'card_figure_label {STR:255} NULL',
            'card_tags         {TEXT}    NULL',
            'in_yml         {BOOL}    NOT NULL DEFAULT 1',
            'sort_order     {INT}     NOT NULL DEFAULT 0',
            'seo_title      {STR:255} NULL',
            'seo_desc       {TEXT}    NULL',
            'seo_h1         {STR:255} NULL',
            'canonical      {STR:255} NULL',
            'og_title       {STR:255} NULL',
            'og_desc        {TEXT}    NULL',
            'og_image       {STR:255} NULL',
            'breadcrumb     {STR:190} NULL',
            'noindex        {BOOL}    NOT NULL DEFAULT 0',
            'deleted_at     {DT}      NULL',       // корзина CMS
            'created_at     {DT}      NOT NULL',
            'updated_at     {DT}      NULL',
            'updated_by     {INT}     NULL',
            '{UNIQUE} ux_products_slug (slug)',
            '{INDEX}  ix_products_category (category_id)',
            '{INDEX}  ix_products_published (is_published, deleted_at)',
            '{INDEX}  ix_products_sku (sku)',
        ],

        // ------------------------------------------------------ характеристики
        'attributes' => [
            'id          {PK}',
            'name        {STR:190} NOT NULL',
            'code        {STR:100} NOT NULL',
            'unit        {STR:40}  NULL',
            'field_type  {STR:20}  NOT NULL DEFAULT \'text\'',  // text | number | select
            'is_visible  {BOOL}    NOT NULL DEFAULT 1',
            'in_yml      {BOOL}    NOT NULL DEFAULT 1',
            'sort_order  {INT}     NOT NULL DEFAULT 0',
            'created_at  {DT}      NOT NULL',
            '{UNIQUE} ux_attributes_code (code)',
        ],

        // Шаблон характеристик категории: какие строки предлагать при создании
        // товара в этой категории и в каком порядке.
        'category_attributes' => [
            'id           {PK}',
            'category_id  {INT} NOT NULL',
            'attribute_id {INT} NOT NULL',
            'sort_order   {INT} NOT NULL DEFAULT 0',
            '{UNIQUE} ux_catattr (category_id, attribute_id)',
        ],

        // Сокращённый список для карточки каталога. Отличается от полного:
        // в карточке «Ядра / потоки: 22 / 44» одной строкой, а на странице
        // товара это две отдельные характеристики. Списки составлены вручную,
        // вывести один из другого нельзя.
        'product_card_specs' => [
            'id         {PK}',
            'product_id {INT}     NOT NULL',
            'name       {STR:120} NOT NULL',
            'value      {STR:255} NULL',
            'sort_order {INT}     NOT NULL DEFAULT 0',
            '{INDEX} ix_card_specs (product_id, sort_order)',
        ],

        'product_attribute_values' => [
            'id           {PK}',
            'product_id   {INT}     NOT NULL',
            'attribute_id {INT}     NOT NULL',
            'value        {TEXT}    NULL',
            'sort_order   {INT}     NOT NULL DEFAULT 0',
            '{UNIQUE} ux_pav (product_id, attribute_id)',
            '{INDEX}  ix_pav_product (product_id)',
        ],

        // -------------------------------------------------------- фотографии
        'product_images' => [
            'id          {PK}',
            'product_id  {INT}     NOT NULL',
            'path        {STR:255} NOT NULL',   // /public/images/products/<файл>
            'alt         {STR:255} NULL',
            'title       {STR:255} NULL',
            'is_main     {BOOL}    NOT NULL DEFAULT 0',
            'width       {INT}     NULL',
            'height      {INT}     NULL',
            'filesize    {INT}     NULL',
            'sort_order  {INT}     NOT NULL DEFAULT 0',
            'created_at  {DT}      NOT NULL',
            '{INDEX} ix_images_product (product_id, sort_order)',
        ],

        // ---------------------------------------------------- FAQ и связи
        'product_faq' => [
            'id         {PK}',
            'product_id {INT}      NOT NULL',
            'question   {STR:255}  NOT NULL',
            'answer     {LONGTEXT} NOT NULL',
            'sort_order {INT}      NOT NULL DEFAULT 0',
            '{INDEX} ix_faq_product (product_id, sort_order)',
        ],

        // Похожие / рекомендуемые / сопутствующие — одна таблица с типом связи.
        'product_relations' => [
            'id         {PK}',
            'product_id {INT}    NOT NULL',
            'related_id {INT}    NOT NULL',
            'relation   {STR:20} NOT NULL DEFAULT \'similar\'',
            // Подпись, объясняющая отличие от текущего товара. Была написана
            // вручную под каждую пару и не выводится из описания товара.
            'note       {TEXT}   NULL',
            'sort_order {INT}    NOT NULL DEFAULT 0',
            '{UNIQUE} ux_relations (product_id, related_id, relation)',
        ],

        // ------------------------------------------------------------ страницы
        'pages' => [
            'id           {PK}',
            'title        {STR:255} NOT NULL',
            'menu_title   {STR:190} NULL',       // короткая подпись для меню
            'slug         {STR:190} NOT NULL',   // '' = главная
            'h1           {STR:255} NULL',
            'content      {LONGTEXT} NULL',
            'template     {STR:50}  NOT NULL DEFAULT \'default\'',
            'seo_title    {STR:255} NULL',
            'seo_desc     {TEXT}    NULL',
            'canonical    {STR:255} NULL',
            'og_title     {STR:255} NULL',
            'og_desc      {TEXT}    NULL',
            'og_image     {STR:255} NULL',
            'noindex      {BOOL}    NOT NULL DEFAULT 0',
            'status       {STR:20}  NOT NULL DEFAULT \'published\'', // draft|published|hidden
            'in_header    {BOOL}    NOT NULL DEFAULT 0',
            'in_footer    {BOOL}    NOT NULL DEFAULT 0',
            'sort_order   {INT}     NOT NULL DEFAULT 0',
            'published_at {DT}      NULL',
            'created_at   {DT}      NOT NULL',
            'updated_at   {DT}      NULL',
            'updated_by   {INT}     NULL',
            '{UNIQUE} ux_pages_slug (slug)',
        ],

        // Управляемые секции главной страницы. Каждая — отдельная строка,
        // чтобы главную можно было собирать из блоков, а не править одним
        // огромным полем HTML.
        'home_blocks' => [
            'id          {PK}',
            'code        {STR:60}  NOT NULL',   // hero, catalog, selection, ...
            'title       {STR:255} NULL',
            'subtitle    {TEXT}    NULL',
            'body        {LONGTEXT} NULL',
            'image       {STR:255} NULL',
            'button_text {STR:120} NULL',
            'button_url  {STR:255} NULL',
            'is_enabled  {BOOL}    NOT NULL DEFAULT 1',
            'sort_order  {INT}     NOT NULL DEFAULT 0',
            'updated_at  {DT}      NULL',
            '{UNIQUE} ux_home_blocks_code (code)',
        ],

        // ------------------------------------------------------------- заказы
        'orders' => [
            'id             {PK}',
            'number         {STR:40}  NOT NULL',
            'status         {STR:30}  NOT NULL DEFAULT \'new\'',
            'customer_name  {STR:190} NULL',
            'phone          {STR:40}  NULL',
            'email          {STR:190} NULL',
            'company        {STR:190} NULL',
            'inn            {STR:20}  NULL',
            'city           {STR:190} NULL',
            'address        {TEXT}    NULL',
            'delivery_code  {STR:60}  NULL',
            'delivery_title {STR:190} NULL',
            'delivery_price {DEC}     NULL',
            'payment        {STR:60}  NULL',
            'goal           {STR:190} NULL',
            'comment        {TEXT}    NULL',
            'manager_note   {TEXT}    NULL',
            'items_total    {DEC}     NOT NULL DEFAULT 0',
            'total          {DEC}     NOT NULL DEFAULT 0',
            'source         {STR:60}  NULL',
            'utm            {TEXT}    NULL',
            'user_agent     {TEXT}    NULL',
            'ip             {STR:45}  NULL',
            'is_paid        {BOOL}    NOT NULL DEFAULT 0',
            'mail_status    {STR:60}  NULL',
            'created_at     {DT}      NOT NULL',
            'updated_at     {DT}      NULL',
            '{UNIQUE} ux_orders_number (number)',
            '{INDEX}  ix_orders_status (status, created_at)',
        ],

        'order_items' => [
            'id           {PK}',
            'order_id     {INT}     NOT NULL',
            'product_id   {INT}     NULL',       // NULL, если товар потом удалили
            'sku          {STR:100} NULL',
            'title        {STR:255} NOT NULL',
            'price        {DEC}     NOT NULL',   // цена на момент заказа
            'qty          {INT}     NOT NULL DEFAULT 1',
            'total        {DEC}     NOT NULL',
            '{INDEX} ix_order_items_order (order_id)',
        ],

        'order_status_log' => [
            'id         {PK}',
            'order_id   {INT}     NOT NULL',
            'status     {STR:30}  NOT NULL',
            'note       {TEXT}    NULL',
            'admin_id   {INT}     NULL',
            'created_at {DT}      NOT NULL',
            '{INDEX} ix_order_log (order_id, created_at)',
        ],

        // ----------------------------------------------------------- доставка
        'delivery_methods' => [
            'id            {PK}',
            'code          {STR:60}  NOT NULL',
            'title         {STR:190} NOT NULL',
            'description   {TEXT}    NULL',    // подпись; {цена} заменяется стоимостью
            'option_title  {STR:255} NULL',    // подсказка, уходит в заказ
            'price         {DEC}     NULL',    // NULL = «по тарифам службы»
            'free_from     {DEC}     NULL',      // бесплатно от суммы
            'needs_city    {BOOL}    NOT NULL DEFAULT 0',
            'needs_address {BOOL}    NOT NULL DEFAULT 0',
            'is_active     {BOOL}    NOT NULL DEFAULT 1',
            'sort_order    {INT}     NOT NULL DEFAULT 0',
            '{UNIQUE} ux_delivery_code (code)',
        ],

        // ---------------------------------------------------------- настройки
        'settings' => [
            'id         {PK}',
            'group_code {STR:60}  NOT NULL',   // site | seo | contacts | yml | mail
            'key_code   {STR:120} NOT NULL',
            'value      {LONGTEXT} NULL',
            'updated_at {DT}      NULL',
            '{UNIQUE} ux_settings (group_code, key_code)',
        ],

        // --------------------------------------------------------- редиректы
        'redirects' => [
            'id          {PK}',
            'from_path   {STR:255} NOT NULL',
            'to_path     {STR:255} NOT NULL',
            'code        {INT}     NOT NULL DEFAULT 301',
            'is_active   {BOOL}    NOT NULL DEFAULT 1',
            'hits        {INT}     NOT NULL DEFAULT 0',
            'created_at  {DT}      NOT NULL',
            '{UNIQUE} ux_redirects_from (from_path)',
        ],

        // ------------------------------------------------------ история цен
        'price_history' => [
            'id         {PK}',
            'product_id {INT} NOT NULL',
            'old_price  {DEC} NULL',
            'new_price  {DEC} NULL',
            'admin_id   {INT} NULL',
            'created_at {DT}  NOT NULL',
            '{INDEX} ix_price_history (product_id, created_at)',
        ],

        // -------------------------------------------------- журнал изменений
        'audit_log' => [
            'id          {PK}',
            'admin_id    {INT}     NULL',
            'admin_login {STR:120} NULL',
            'action      {STR:60}  NOT NULL',
            'entity      {STR:60}  NULL',
            'entity_id   {INT}     NULL',
            'summary     {TEXT}    NULL',
            'ip          {STR:45}  NULL',
            'created_at  {DT}      NOT NULL',
            '{INDEX} ix_audit_created (created_at)',
            '{INDEX} ix_audit_entity (entity, entity_id)',
        ],

        // ---------------------------------- версии для отката правок товара
        'revisions' => [
            'id          {PK}',
            'entity      {STR:60} NOT NULL',
            'entity_id   {INT}    NOT NULL',
            'payload     {LONGTEXT} NOT NULL',   // снимок в JSON
            'admin_id    {INT}    NULL',
            'created_at  {DT}     NOT NULL',
            '{INDEX} ix_revisions (entity, entity_id, created_at)',
        ],
    ];
}

/** Переносимые типы -> конкретный диалект. */
function cms_schema_types(string $driver): array
{
    if ($driver === 'sqlite') {
        return [
            '{PK}'       => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            '{INT}'      => 'INTEGER',
            '{BOOL}'     => 'INTEGER',
            '{DEC}'      => 'NUMERIC(12,2)',
            '{DT}'       => 'TEXT',
            '{TEXT}'     => 'TEXT',
            '{LONGTEXT}' => 'TEXT',
        ];
    }

    return [
        '{PK}'       => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        '{INT}'      => 'INT',
        '{BOOL}'     => 'TINYINT(1)',
        '{DEC}'      => 'DECIMAL(12,2)',
        '{DT}'       => 'DATETIME',
        '{TEXT}'     => 'TEXT',
        '{LONGTEXT}' => 'MEDIUMTEXT',
    ];
}

/**
 * Собирает CREATE TABLE и CREATE INDEX для выбранного движка.
 *
 * @return string[] список SQL-запросов
 */
function cms_schema_sql(string $driver): array
{
    $types = cms_schema_types($driver);
    $sql = [];

    foreach (cms_schema() as $table => $lines) {
        $columns = [];
        $indexes = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '{UNIQUE}') || str_starts_with($line, '{INDEX}')) {
                $unique = str_starts_with($line, '{UNIQUE}');
                if (!preg_match('~\{(?:UNIQUE|INDEX)\}\s+(\w+)\s*\(([^)]+)\)~', $line, $m)) {
                    continue;
                }
                $indexes[] = 'CREATE ' . ($unique ? 'UNIQUE ' : '') . 'INDEX ' . $m[1]
                    . ' ON ' . $table . ' (' . $m[2] . ')';
                continue;
            }

            // «name  {STR:190} NOT NULL» -> «name VARCHAR(190) NOT NULL»
            $line = preg_replace_callback('~\{STR:(\d+)\}~', static function ($m) use ($driver) {
                return $driver === 'sqlite' ? 'TEXT' : 'VARCHAR(' . $m[1] . ')';
            }, $line);
            $columns[] = trim(strtr($line, $types));
        }

        $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $table . " (\n  " . implode(",\n  ", $columns) . "\n)"
            . ($driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        foreach ($indexes as $index) {
            $sql[] = $driver === 'sqlite'
                ? str_replace('CREATE INDEX ', 'CREATE INDEX IF NOT EXISTS ', str_replace('CREATE UNIQUE INDEX ', 'CREATE UNIQUE INDEX IF NOT EXISTS ', $index))
                : $index;
        }
    }

    return $sql;
}

// Запуск из консоли: php cms/schema.php --dump-mysql > cms/schema.mysql.sql
if (PHP_SAPI === 'cli' && isset($argv[1]) && $argv[1] === '--dump-mysql') {
    echo "-- Схема базы данных Comp-Uter CMS для MySQL / MariaDB.\n";
    echo "-- Сгенерировано из cms/schema.php — правьте схему там, а не здесь.\n";
    echo "-- Импорт: mysql -u ПОЛЬЗОВАТЕЛЬ -p ИМЯ_БАЗЫ < cms/schema.mysql.sql\n\n";
    echo "SET NAMES utf8mb4;\n\n";
    foreach (cms_schema_sql('mysql') as $query) {
        echo $query, ";\n\n";
    }
}
