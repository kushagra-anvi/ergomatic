<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
try {
    $posts = db()->query('SELECT id, title, slug, status, updated_at FROM posts ORDER BY updated_at DESC')->fetchAll();
} catch (Throwable $exception) {
    service_unavailable();
}

admin_header('Manage articles');
echo '<div class="blog-admin-toolbar"><p>Create and update articles shown on the public website.</p><a class="btn btn-primary" href="admin/post-edit.php">Write an article</a></div>';
$draftCount = count(array_filter($posts, static fn(array $post): bool => $post['status'] === 'draft'));
$publishedCount = count($posts) - $draftCount;
echo '<div class="blog-admin-stats"><div><strong>' . count($posts) . '</strong><span>Total articles</span></div><div><strong>' . $publishedCount . '</strong><span>Published</span></div><div><strong>' . $draftCount . '</strong><span>Drafts</span></div></div>';
if (!$posts) {
    echo '<section class="card"><h2>No articles yet</h2><p>Create your first draft to get started.</p></section>';
} else {
    echo '<div class="blog-admin-table-wrap"><table class="blog-admin-table"><thead><tr><th>Title</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
    foreach ($posts as $post) {
        echo '<tr><td>' . e($post['title']) . '<small>' . e($post['slug']) . '</small></td><td><span class="blog-status ' . e($post['status']) . '">' . e(ucfirst($post['status'])) . '</span></td><td>' . e(date('M j, Y', strtotime($post['updated_at']))) . '</td><td><a href="admin/post-edit.php?id=' . (int) $post['id'] . '">Edit</a>';
        if ($post['status'] === 'published') {
            echo ' · <a href="article.php?slug=' . rawurlencode($post['slug']) . '" target="_blank" rel="noopener">View</a>';
        }
        echo '<form class="blog-delete-form" method="post" action="admin/post-delete.php">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $post['id'] . '"><button class="blog-link-button" type="submit">Delete</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}
admin_footer();
