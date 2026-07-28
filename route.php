<?php

/**
 * Разбор адреса вида /<slug>/.
 *
 * Один контроллер на категории и на обычные страницы. Раньше каждому такому
 * адресу соответствовала своя папка с файлом на сервере, поэтому создать
 * категорию или страницу в панели управления было невозможно — требовался
 * доступ по FTP. Теперь адрес ищется в базе.
 */

declare(strict_types=1);

require_once __DIR__ . '/cms/repo.php';

$slug = (string)($_GET['slug'] ?? '');

if ($slug === '') {
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    $slug = trim((string)$path, '/');
}

$slug = strtolower(trim($slug, '/'));

/* --------------------------------------------------- сохранённые адреса */

// Если страницу переименовали, старый адрес отвечает постоянным
// перенаправлением, а не 404: ссылки и позиции в поиске не теряются.
$redirect = cms_one(
    'SELECT to_path, code FROM redirects WHERE from_path = ? AND is_active = 1',
    ['/' . $slug . '/']
);
if ($redirect) {
    cms_query('UPDATE redirects SET hits = hits + 1 WHERE from_path = ?', ['/' . $slug . '/']);
    http_response_code((int)$redirect['code'] ?: 301);
    header('Location: ' . $redirect['to_path']);
    exit;
}

/* -------------------------------------------------------------- поиск */

$category = repo_category($slug);
if ($category && (int)$category['is_published'] === 1) {
    require __DIR__ . '/templates/category.php';
    exit;
}

$page = repo_page($slug);
if ($page) {
    require __DIR__ . '/templates/page.php';
    exit;
}

/* --------------------------------------------------------------- 404 */

http_response_code(404);
$pageTitle = 'Страница не найдена | Comp-Uter';
$noindex = true;
require __DIR__ . '/templates/header.php';
?>
    <main class="product-page product-not-found">
      <section class="product-page-card">
        <h1>Страница не найдена</h1>
        <p>Возможно, адрес изменился или страница была удалена.</p>
        <a class="button primary" href="/">На главную</a>
      </section>
    </main>
<?php require __DIR__ . '/templates/footer.php'; ?>
