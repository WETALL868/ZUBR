<?php

/**
 * Downloads every remote product image into /public/assets/ and rewrites all
 * references (data layer, static pages, homepage) to the local copies.
 *
 * Why this exists: the HDD photos were imported from a marketplace export and
 * still point at that marketplace's CDN. Hotlinking is fragile — the CDN can
 * start refusing foreign referers, rename the object, or simply go away, and
 * the product photos would vanish from the site and from the Schema.org
 * `image` field that Yandex/Google read. Running this once makes the site
 * self-contained.
 *
 * Usage on the hosting (from the site root):
 *
 *     php tools/localize-images.php          # dry run, shows what would change
 *     php tools/localize-images.php --apply  # download + rewrite
 *
 * Safe to re-run: already-local images are skipped.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

$root  = dirname(__DIR__);
$apply = in_array('--apply', $argv, true);

$assetsDir = $root . '/public/assets';
if (!is_dir($assetsDir)) {
    exit("public/assets not found — run this from the site root.\n");
}

$productsFile = $root . '/yandexmarket/products.php';
$products = require $productsFile;

// ---------------------------------------------------------------- collect
$jobs = [];
foreach ($products as $p) {
    $picture = (string)($p['picture'] ?? '');
    if ($picture === '' || !preg_match('~^https?://~i', $picture)) {
        continue; // already local
    }

    $ext = strtolower(pathinfo(parse_url($picture, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $ext = 'jpg';
    }

    $jobs[] = [
        'slug'   => (string)$p['slug'],
        'remote' => $picture,
        'name'   => strtolower((string)$p['slug']) . '.' . $ext,
    ];
}

if (!$jobs) {
    exit("Nothing to do: every product image is already local.\n");
}

printf("%d remote image(s) found.%s\n\n", count($jobs), $apply ? '' : ' (dry run)');

// ---------------------------------------------------------------- download
$done = [];
foreach ($jobs as $job) {
    $target = $assetsDir . '/' . $job['name'];
    $localPath = '/public/assets/' . $job['name'];
    printf("  %-16s %s\n      -> %s\n", $job['slug'], $job['remote'], $localPath);

    if (!$apply) {
        $done[$job['remote']] = $localPath;
        continue;
    }

    $ctx = stream_context_create(['http' => [
        'timeout' => 30,
        'header'  => "User-Agent: Mozilla/5.0 (compatible; comp-uter.ru image import)\r\n",
    ]]);
    $bytes = @file_get_contents($job['remote'], false, $ctx);

    if ($bytes === false || strlen($bytes) < 1024) {
        fwrite(STDERR, "      !! download failed — reference left untouched\n");
        continue;
    }
    if (@file_put_contents($target, $bytes) === false) {
        fwrite(STDERR, "      !! could not write {$target}\n");
        continue;
    }

    printf("      OK %.1f KB\n", strlen($bytes) / 1024);
    $done[$job['remote']] = $localPath;
}

if (!$done) {
    exit("\nNo images were localized.\n");
}

// ---------------------------------------------------------------- rewrite
$targets = array_merge(
    [$productsFile, $root . '/index.html'],
    glob($root . '/products/*/index.html') ?: []
);

$changed = 0;
foreach ($targets as $file) {
    if (!is_file($file)) {
        continue;
    }
    $src = (string)file_get_contents($file);
    $out = strtr($src, $done);
    if ($out === $src) {
        continue;
    }
    if ($apply) {
        file_put_contents($file, $out);
    }
    printf("  rewrote %s\n", str_replace($root . '/', '', $file));
    $changed++;
}

echo "\n";
if ($apply) {
    printf("Done: %d image(s) localized, %d file(s) rewritten.\n", count($done), $changed);
    echo "Re-check a product page and the Schema.org `image` field afterwards.\n";
} else {
    printf("Dry run only. %d file(s) would change. Re-run with --apply.\n", $changed);
}
