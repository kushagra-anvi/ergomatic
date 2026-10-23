<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    http_response_code(419);
    echo json_encode(['error' => 'Your session expired. Reload the editor and try again.']);
    exit;
}
$file = $_FILES['image'] ?? null;
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_int($file['size'] ?? null) || $file['size'] > 3 * 1024 * 1024 || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a valid image smaller than 3 MB.']);
    exit;
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Images must be valid JPEG, PNG, or WebP files.']);
    exit;
}
$filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/uploads/blog/' . $filename)) {
    http_response_code(500);
    echo json_encode(['error' => 'The image could not be saved. Check uploads folder permissions.']);
    exit;
}
echo json_encode(['url' => site_base_path() . 'uploads/blog/' . $filename]);
