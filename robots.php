<?php
require_once __DIR__ . '/includes/bootstrap.php';

$baseUrl = rtrim((string) (app_config()['base_url'] ?? ''), '/');
$scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Crawler guidance is not configured.');
}
$basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\nDisallow: " . $basePath . "/admin/\nSitemap: " . $baseUrl . "/sitemap.xml\n";
