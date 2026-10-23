<?php
require_once __DIR__ . '/includes/bootstrap.php';
try {
    $posts = db()->query("SELECT title, slug, excerpt, cover_image, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC, id DESC")->fetchAll();
    $baseUrl = rtrim((string) (app_config()['base_url'] ?? ''), '/');
} catch (Throwable $exception) {
    service_unavailable();
}

site_head('Articles & News | INDEVA Ergomatic India', 'Industry insights, company updates, and material handling innovations.', $baseUrl ? $baseUrl . '/news.php' : null);
?>
<section class="page-hero"><div class="container"><span class="hero-subtitle">Stay Informed</span><h1>Articles & News</h1><p>Industry insights, company updates, and material handling innovations</p></div></section>
<section class="section tech-grid-bg"><div class="container">
    <div style="text-align:center;margin-bottom:3rem"><h2 class="center">Latest Updates</h2><p style="max-width:550px;margin:1.5rem auto 0">Stay updated with the latest from INDEVA Ergomatic India and the industrial automation industry.</p></div>
    <?php if (!$posts): ?>
        <div class="card blog-empty"><h3>Articles coming soon</h3><p>New insights and company updates will appear here.</p></div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($posts as $post): ?>
                <article class="card blog-card">
                    <?php if ($post['cover_image']): ?><img class="blog-cover" src="uploads/blog/<?= e($post['cover_image']) ?>" alt="" loading="lazy"><?php endif; ?>
                    <div class="blog-date"><?= e(date('F j, Y', strtotime($post['published_at']))) ?></div>
                    <h3><?= e($post['title']) ?></h3>
                    <p><?= e($post['excerpt']) ?></p>
                    <a class="btn btn-secondary" href="article.php?slug=<?= rawurlencode($post['slug']) ?>">Read article</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
<?php site_foot(); ?>
