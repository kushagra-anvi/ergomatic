<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = (string) ($_GET['slug'] ?? '');
if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    http_response_code(404);
    $post = null;
} else {
    try {
        $statement = db()->prepare("SELECT title, slug, excerpt, seo_title, seo_description, body, cover_image, published_at, updated_at FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
        $statement->execute([$slug]);
        $post = $statement->fetch();
        $baseUrl = rtrim((string) (app_config()['base_url'] ?? ''), '/');
    } catch (Throwable $exception) {
        service_unavailable();
    }
    if (!$post) {
        http_response_code(404);
    }
}

if (!$post) {
    site_head('Article not found | INDEVA Ergomatic India');
    echo '<main class="section"><div class="container"><div class="card"><h1>Article not found</h1><p>This article may have been removed or is not published.</p><a class="btn btn-primary" href="news.php">Back to Articles & News</a></div></div></main>';
    site_foot();
    exit;
}

try {
    $bodyHtml = render_article_body($post['body']);
} catch (Throwable $exception) {
    service_unavailable();
}
$plainDescription = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($bodyHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
$description = trim((string) ($post['seo_description'] ?? '')) ?: (trim($post['excerpt']) ?: (function_exists('mb_substr') ? mb_substr($plainDescription, 0, 320, 'UTF-8') : substr($plainDescription, 0, 320)));
$seoTitle = trim((string) ($post['seo_title'] ?? '')) ?: $post['title'];
$canonical = $baseUrl ? $baseUrl . '/article.php?slug=' . rawurlencode($post['slug']) : null;
$publishedTime = strtotime((string) $post['published_at']);
$updatedTime = strtotime((string) $post['updated_at']);
$publishedIso = $publishedTime ? date('Y-m-d', $publishedTime) : null;
$updatedIso = $updatedTime ? date('Y-m-d', $updatedTime) : $publishedIso;
$publishedAtom = $publishedTime ? date(DATE_ATOM, $publishedTime) : null;
$updatedAtom = $updatedTime ? date(DATE_ATOM, $updatedTime) : $publishedAtom;
$coverUrl = $baseUrl && $post['cover_image'] ? $baseUrl . '/uploads/blog/' . rawurlencode($post['cover_image']) : null;
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $description,
    'mainEntityOfPage' => $canonical,
    'datePublished' => $publishedIso,
    'dateModified' => $updatedIso,
    'author' => ['@type' => 'Organization', 'name' => 'INDEVA Ergomatic India'],
    'publisher' => ['@type' => 'Organization', 'name' => 'INDEVA Ergomatic India'],
];
if ($coverUrl) $jsonLd['image'] = [$coverUrl];
site_head($seoTitle . ' | INDEVA Ergomatic India', $description, $canonical, ['image' => $coverUrl, 'published' => $publishedAtom, 'modified' => $updatedAtom, 'json_ld' => $jsonLd]);
?>
<section class="page-hero"><div class="container"><span class="hero-subtitle">Published <?= e(date('F j, Y', strtotime($post['published_at']))) ?><?php if ($updatedTime && date('Y-m-d', $updatedTime) > date('Y-m-d', (int) $publishedTime)): ?> · Updated <?= e(date('F j, Y', $updatedTime)) ?><?php endif; ?></span><h1><?= e($post['title']) ?></h1><?php if ($post['excerpt'] !== ''): ?><p><?= e($post['excerpt']) ?></p><?php endif; ?></div></section>
<main class="section"><article class="container blog-article">
    <?php if ($post['cover_image']): ?><img class="blog-article-cover" src="uploads/blog/<?= e($post['cover_image']) ?>" alt=""><?php endif; ?>
    <div class="card blog-article-body"><?= $bodyHtml ?></div>
    <a class="btn btn-secondary" href="news.php">Back to Articles & News</a>
</article></main>
<?php site_foot(); ?>
