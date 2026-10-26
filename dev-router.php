<?php
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#', $path, $matches)) {
    $localPath = __DIR__ . DIRECTORY_SEPARATOR . $matches[1];
    if (!is_file($localPath) && !is_dir($localPath)) {
        $_GET['slug'] = $matches[1];
        require __DIR__ . '/article.php';
        return true;
    }
}

return false;
