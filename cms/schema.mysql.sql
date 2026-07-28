-- Схема базы данных Comp-Uter CMS для MySQL / MariaDB.
-- Сгенерировано из cms/schema.php — правьте схему там, а не здесь.
-- Импорт: mysql -u ПОЛЬЗОВАТЕЛЬ -p ИМЯ_БАЗЫ < cms/schema.mysql.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  login         VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'manager',
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  session_epoch INT     NOT NULL DEFAULT 0,
  last_login_at DATETIME      NULL,
  last_login_ip VARCHAR(45)  NULL,
  created_at    DATETIME      NOT NULL,
  updated_at    DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_admin_login ON admin_users (login);

CREATE TABLE IF NOT EXISTS admin_login_attempts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login      VARCHAR(120) NOT NULL,
  ip         VARCHAR(45)  NOT NULL,
  success    TINYINT(1)    NOT NULL DEFAULT 0,
  created_at DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_attempts_ip ON admin_login_attempts (ip, created_at);

CREATE INDEX ix_attempts_login ON admin_login_attempts (login, created_at);

CREATE TABLE IF NOT EXISTS categories (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id     INT     NULL,
  name          VARCHAR(190) NOT NULL,
  slug          VARCHAR(190) NOT NULL,
  h1            VARCHAR(190) NULL,
  lead          TEXT    NULL,
  description   MEDIUMTEXT NULL,
  image         VARCHAR(255) NULL,
  seo_title     VARCHAR(255) NULL,
  seo_desc      TEXT    NULL,
  canonical     VARCHAR(255) NULL,
  og_title      VARCHAR(255) NULL,
  og_desc       TEXT    NULL,
  og_image      VARCHAR(255) NULL,
  noindex       TINYINT(1)    NOT NULL DEFAULT 0,
  yml_category_id INT   NULL,
  sort_order    INT     NOT NULL DEFAULT 0,
  is_published  TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    DATETIME      NOT NULL,
  updated_at    DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_categories_slug ON categories (slug);

CREATE INDEX ix_categories_parent ON categories (parent_id);

CREATE TABLE IF NOT EXISTS products (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id    INT     NULL,
  name           VARCHAR(255) NOT NULL,
  short_name     VARCHAR(190) NULL,
  slug           VARCHAR(190) NOT NULL,
  sku            VARCHAR(100) NULL,
  mpn            VARCHAR(100) NULL,
  brand          VARCHAR(100) NULL,
  lead           TEXT    NULL,
  description    MEDIUMTEXT NULL,
  article_title  VARCHAR(255) NULL,
  article_html   MEDIUMTEXT NULL,
  price          DECIMAL(12,2)     NULL,
  old_price      DECIMAL(12,2)     NULL,
  cost_price     DECIMAL(12,2)     NULL,
  stock_qty      INT     NULL,
  availability   VARCHAR(20)  NOT NULL DEFAULT 'in_stock',
  unit           VARCHAR(20)  NOT NULL DEFAULT 'шт.',
  warranty       VARCHAR(100) NULL,
  condition_note VARCHAR(100) NULL,
  country        VARCHAR(100) NULL,
  is_published   TINYINT(1)    NOT NULL DEFAULT 1,
  is_featured    TINYINT(1)    NOT NULL DEFAULT 0,
  in_yml         TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order     INT     NOT NULL DEFAULT 0,
  seo_title      VARCHAR(255) NULL,
  seo_desc       TEXT    NULL,
  seo_h1         VARCHAR(255) NULL,
  canonical      VARCHAR(255) NULL,
  og_title       VARCHAR(255) NULL,
  og_desc        TEXT    NULL,
  og_image       VARCHAR(255) NULL,
  breadcrumb     VARCHAR(190) NULL,
  noindex        TINYINT(1)    NOT NULL DEFAULT 0,
  deleted_at     DATETIME      NULL,
  created_at     DATETIME      NOT NULL,
  updated_at     DATETIME      NULL,
  updated_by     INT     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_products_slug ON products (slug);

CREATE INDEX ix_products_category ON products (category_id);

CREATE INDEX ix_products_published ON products (is_published, deleted_at);

CREATE INDEX ix_products_sku ON products (sku);

CREATE TABLE IF NOT EXISTS attributes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(190) NOT NULL,
  code        VARCHAR(100) NOT NULL,
  unit        VARCHAR(40)  NULL,
  field_type  VARCHAR(20)  NOT NULL DEFAULT 'text',
  is_visible  TINYINT(1)    NOT NULL DEFAULT 1,
  in_yml      TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order  INT     NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_attributes_code ON attributes (code);

CREATE TABLE IF NOT EXISTS category_attributes (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id  INT NOT NULL,
  attribute_id INT NOT NULL,
  sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_catattr ON category_attributes (category_id, attribute_id);

CREATE TABLE IF NOT EXISTS product_attribute_values (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id   INT     NOT NULL,
  attribute_id INT     NOT NULL,
  value        TEXT    NULL,
  sort_order   INT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_pav ON product_attribute_values (product_id, attribute_id);

CREATE INDEX ix_pav_product ON product_attribute_values (product_id);

CREATE TABLE IF NOT EXISTS product_images (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id  INT     NOT NULL,
  path        VARCHAR(255) NOT NULL,
  alt         VARCHAR(255) NULL,
  title       VARCHAR(255) NULL,
  is_main     TINYINT(1)    NOT NULL DEFAULT 0,
  width       INT     NULL,
  height      INT     NULL,
  filesize    INT     NULL,
  sort_order  INT     NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_images_product ON product_images (product_id, sort_order);

CREATE TABLE IF NOT EXISTS product_faq (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT      NOT NULL,
  question   VARCHAR(255)  NOT NULL,
  answer     MEDIUMTEXT NOT NULL,
  sort_order INT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_faq_product ON product_faq (product_id, sort_order);

CREATE TABLE IF NOT EXISTS product_relations (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT    NOT NULL,
  related_id INT    NOT NULL,
  relation   VARCHAR(20) NOT NULL DEFAULT 'similar',
  sort_order INT    NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_relations ON product_relations (product_id, related_id, relation);

CREATE TABLE IF NOT EXISTS pages (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  slug         VARCHAR(190) NOT NULL,
  h1           VARCHAR(255) NULL,
  content      MEDIUMTEXT NULL,
  template     VARCHAR(50)  NOT NULL DEFAULT 'default',
  seo_title    VARCHAR(255) NULL,
  seo_desc     TEXT    NULL,
  canonical    VARCHAR(255) NULL,
  og_title     VARCHAR(255) NULL,
  og_desc      TEXT    NULL,
  og_image     VARCHAR(255) NULL,
  noindex      TINYINT(1)    NOT NULL DEFAULT 0,
  status       VARCHAR(20)  NOT NULL DEFAULT 'published',
  in_header    TINYINT(1)    NOT NULL DEFAULT 0,
  in_footer    TINYINT(1)    NOT NULL DEFAULT 0,
  sort_order   INT     NOT NULL DEFAULT 0,
  published_at DATETIME      NULL,
  created_at   DATETIME      NOT NULL,
  updated_at   DATETIME      NULL,
  updated_by   INT     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_pages_slug ON pages (slug);

CREATE TABLE IF NOT EXISTS home_blocks (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(60)  NOT NULL,
  title       VARCHAR(255) NULL,
  subtitle    TEXT    NULL,
  body        MEDIUMTEXT NULL,
  image       VARCHAR(255) NULL,
  button_text VARCHAR(120) NULL,
  button_url  VARCHAR(255) NULL,
  is_enabled  TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order  INT     NOT NULL DEFAULT 0,
  updated_at  DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_home_blocks_code ON home_blocks (code);

CREATE TABLE IF NOT EXISTS orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  number         VARCHAR(40)  NOT NULL,
  status         VARCHAR(30)  NOT NULL DEFAULT 'new',
  customer_name  VARCHAR(190) NULL,
  phone          VARCHAR(40)  NULL,
  email          VARCHAR(190) NULL,
  company        VARCHAR(190) NULL,
  inn            VARCHAR(20)  NULL,
  city           VARCHAR(190) NULL,
  address        TEXT    NULL,
  delivery_code  VARCHAR(60)  NULL,
  delivery_title VARCHAR(190) NULL,
  delivery_price DECIMAL(12,2)     NULL,
  payment        VARCHAR(60)  NULL,
  goal           VARCHAR(190) NULL,
  comment        TEXT    NULL,
  manager_note   TEXT    NULL,
  items_total    DECIMAL(12,2)     NOT NULL DEFAULT 0,
  total          DECIMAL(12,2)     NOT NULL DEFAULT 0,
  source         VARCHAR(60)  NULL,
  utm            TEXT    NULL,
  user_agent     TEXT    NULL,
  ip             VARCHAR(45)  NULL,
  is_paid        TINYINT(1)    NOT NULL DEFAULT 0,
  mail_status    VARCHAR(60)  NULL,
  created_at     DATETIME      NOT NULL,
  updated_at     DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_orders_number ON orders (number);

CREATE INDEX ix_orders_status ON orders (status, created_at);

CREATE TABLE IF NOT EXISTS order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id     INT     NOT NULL,
  product_id   INT     NULL,
  sku          VARCHAR(100) NULL,
  title        VARCHAR(255) NOT NULL,
  price        DECIMAL(12,2)     NOT NULL,
  qty          INT     NOT NULL DEFAULT 1,
  total        DECIMAL(12,2)     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_order_items_order ON order_items (order_id);

CREATE TABLE IF NOT EXISTS order_status_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id   INT     NOT NULL,
  status     VARCHAR(30)  NOT NULL,
  note       TEXT    NULL,
  admin_id   INT     NULL,
  created_at DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_order_log ON order_status_log (order_id, created_at);

CREATE TABLE IF NOT EXISTS delivery_methods (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(60)  NOT NULL,
  title         VARCHAR(190) NOT NULL,
  description   TEXT    NULL,
  price         DECIMAL(12,2)     NULL,
  free_from     DECIMAL(12,2)     NULL,
  needs_city    TINYINT(1)    NOT NULL DEFAULT 0,
  needs_address TINYINT(1)    NOT NULL DEFAULT 0,
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order    INT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_delivery_code ON delivery_methods (code);

CREATE TABLE IF NOT EXISTS settings (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_code VARCHAR(60)  NOT NULL,
  key_code   VARCHAR(120) NOT NULL,
  value      MEDIUMTEXT NULL,
  updated_at DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_settings ON settings (group_code, key_code);

CREATE TABLE IF NOT EXISTS redirects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  from_path   VARCHAR(255) NOT NULL,
  to_path     VARCHAR(255) NOT NULL,
  code        INT     NOT NULL DEFAULT 301,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  hits        INT     NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_redirects_from ON redirects (from_path);

CREATE TABLE IF NOT EXISTS price_history (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  old_price  DECIMAL(12,2) NULL,
  new_price  DECIMAL(12,2) NULL,
  admin_id   INT NULL,
  created_at DATETIME  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_price_history ON price_history (product_id, created_at);

CREATE TABLE IF NOT EXISTS audit_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT     NULL,
  admin_login VARCHAR(120) NULL,
  action      VARCHAR(60)  NOT NULL,
  entity      VARCHAR(60)  NULL,
  entity_id   INT     NULL,
  summary     TEXT    NULL,
  ip          VARCHAR(45)  NULL,
  created_at  DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_audit_created ON audit_log (created_at);

CREATE INDEX ix_audit_entity ON audit_log (entity, entity_id);

CREATE TABLE IF NOT EXISTS revisions (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity      VARCHAR(60) NOT NULL,
  entity_id   INT    NOT NULL,
  payload     MEDIUMTEXT NOT NULL,
  admin_id    INT    NULL,
  created_at  DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX ix_revisions ON revisions (entity, entity_id, created_at);

