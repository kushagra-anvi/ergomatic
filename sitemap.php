<?php
require_once __DIR__ . '/includes/bootstrap.php';

$baseUrl = rtrim((string) (app_config()['base_url'] ?? ''), '/');
if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Sitemap is not configured.');
}

try {
    $posts = db()->query("SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY published_at DESC, id DESC")->fetchAll();
} catch (Throwable $exception) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Sitemap is temporarily unavailable.');
}

$entries = [];
$entries[] = ['loc' => $baseUrl . '/news.php', 'lastmod' => ''];
foreach (glob(__DIR__ . '/*.html') ?: [] as $path) {
    $name = basename($path);
    if ($name === 'news.html' || !is_file($path)) continue;
    $modified = @filemtime($path);
    $entries[] = [
        'loc' => $baseUrl . ($name === 'index.html' ? '/' : '/' . rawurlencode($name)),
        'lastmod' => $modified ? date('Y-m-d', $modified) : '',
    ];
}
foreach ($posts as $post) {
    $entries[] = [
        'loc' => $baseUrl . '/' . rawurlencode($post['slug']),
        'lastmod' => substr((string) $post['updated_at'], 0, 10),
    ];
}

$xml = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($entries as $entry) {
    echo '<url><loc>' . $xml($entry['loc']) . '</loc>';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry['lastmod'])) echo '<lastmod>' . $xml($entry['lastmod']) . '</lastmod>';
    echo '</url>';
}
echo '</urlset>';
