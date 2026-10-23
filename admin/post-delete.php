<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
require_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid article.');
}
try {
    $select = db()->prepare('SELECT cover_image FROM posts WHERE id = ?');
    $select->execute([$id]);
    $post = $select->fetch();
    $delete = db()->prepare('DELETE FROM posts WHERE id = ?');
    $delete->execute([$id]);
    if ($delete->rowCount() && $post && !empty($post['cover_image']) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $post['cover_image'])) {
        $image = dirname(__DIR__) . '/uploads/blog/' . $post['cover_image'];
        if (is_file($image)) {
            unlink($image);
        }
    }
} catch (Throwable $exception) {
    service_unavailable();
}
redirect('posts.php');
