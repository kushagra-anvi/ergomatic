<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$post = ['title' => '', 'slug' => '', 'excerpt' => '', 'seo_title' => '', 'seo_description' => '', 'body' => '', 'status' => 'draft', 'cover_image' => null];
$error = '';
try {
    if ($id) {
        $statement = db()->prepare('SELECT * FROM posts WHERE id = ?');
        $statement->execute([$id]);
        $post = $statement->fetch();
        if (!$post) {
            http_response_code(404);
            exit('Article not found.');
        }
    }
} catch (Throwable $exception) {
    service_unavailable();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post['title'] = trim(post_string('title'));
    $post['slug'] = strtolower(trim(post_string('slug')));
    $post['excerpt'] = trim(post_string('excerpt'));
    $post['seo_title'] = trim(post_string('seo_title'));
    $post['seo_description'] = trim(post_string('seo_description'));
    $post['body'] = trim(post_string('body'));
    if (preg_match('/<[a-z][^>]*>/i', $post['body'])) {
        try {
            $post['body'] = sanitize_article_html($post['body']);
        } catch (Throwable $exception) {
            $error = 'The HTML editor is unavailable because the server DOM extension is missing.';
        }
    }
    $post['status'] = post_string('status') ?: 'draft';
    $newImage = null;

    if ($post['slug'] === '') {
        $slugSource = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $post['title']) : $post['title'];
        $post['slug'] = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $slugSource)), '-');
    }

    $bodyText = html_entity_decode(strip_tags($post['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $hasBodyContent = trim(str_replace("\u{00A0}", ' ', $bodyText)) !== '' || preg_match('/<(img|table)\b/i', $post['body']);
    if ($post['title'] === '' || strlen($post['title']) > 200 || !$hasBodyContent) {
        $error = 'Title and article body are required. The title must be at most 200 characters.';
    } elseif (strlen($post['excerpt']) > 320) {
        $error = 'The summary must be 320 characters or fewer.';
    } elseif (strlen($post['seo_title']) > 200 || strlen($post['seo_description']) > 320) {
        $error = 'The SEO title must be 200 characters or fewer and the SEO description 320 characters or fewer.';
    } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $post['slug']) || strlen($post['slug']) > 200) {
        $error = 'Use letters, numbers, and single hyphens in the article URL.';
    } elseif (!in_array($post['status'], ['draft', 'published'], true)) {
        $error = 'Choose draft or published status.';
    } elseif (strlen($post['body']) > 1000000) {
        $error = 'The article body is too long.';
    } else {
        try {
            $duplicate = db()->prepare('SELECT id FROM posts WHERE slug = ? AND id <> ? LIMIT 1');
            $duplicate->execute([$post['slug'], $id]);
            if ($duplicate->fetch()) {
                $error = 'That article URL is already in use.';
            }
        } catch (Throwable $exception) {
            service_unavailable();
        }
    }

    if ($error === '' && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['cover_image'];
        $mimeExtensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_array($file) || !isset($file['error'], $file['size'], $file['tmp_name']) || !is_int($file['error']) || !is_int($file['size']) || !is_string($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
            $error = 'Choose an image smaller than 3 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $dimensions = @getimagesize($file['tmp_name']);
            if (!isset($mimeExtensions[$mime]) || !$dimensions) {
                $error = 'Cover images must be valid JPEG, PNG, or WebP files.';
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $mimeExtensions[$mime];
                $destination = dirname(__DIR__) . '/uploads/blog/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = 'The image could not be saved. Check the uploads folder permissions.';
                } else {
                    $newImage = $filename;
                }
            }
        }
    }

    if ($error === '') {
        try {
            if ($id) {
                $sql = "UPDATE posts SET title = ?, slug = ?, excerpt = ?, seo_title = ?, seo_description = ?, body = ?, status = ?, cover_image = ?, published_at = CASE WHEN ? = 'published' THEN COALESCE(published_at, NOW()) ELSE NULL END WHERE id = ?";
                $statement = db()->prepare($sql);
                $statement->execute([$post['title'], $post['slug'], $post['excerpt'], $post['seo_title'] ?: null, $post['seo_description'] ?: null, $post['body'], $post['status'], $newImage ?: $post['cover_image'], $post['status'], $id]);
            } else {
                $sql = "INSERT INTO posts (admin_id, title, slug, excerpt, seo_title, seo_description, body, status, cover_image, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'published' THEN NOW() ELSE NULL END)";
                $statement = db()->prepare($sql);
                $statement->execute([$_SESSION['admin_id'], $post['title'], $post['slug'], $post['excerpt'], $post['seo_title'] ?: null, $post['seo_description'] ?: null, $post['body'], $post['status'], $newImage, $post['status']]);
            }
            if ($newImage && !empty($post['cover_image'])) {
                $oldPath = dirname(__DIR__) . '/uploads/blog/' . basename((string) $post['cover_image']);
                if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', (string) $post['cover_image']) && is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            redirect('posts.php');
        } catch (Throwable $exception) {
            if ($newImage) {
                unlink(dirname(__DIR__) . '/uploads/blog/' . $newImage);
            }
            $error = 'The article could not be saved. Check its URL and try again.';
        }
    } elseif ($newImage) {
        unlink(dirname(__DIR__) . '/uploads/blog/' . $newImage);
    }
}

admin_header($id ? 'Edit article' : 'Write an article');
echo '<section class="card blog-admin-editor">';
if ($error !== '') {
    echo '<p class="blog-admin-error" role="alert">' . e($error) . '</p>';
}
echo '<form method="post" enctype="multipart/form-data" action="admin/post-edit.php' . ($id ? '?id=' . (int) $id : '') . '">' . csrf_field();
echo '<div class="blog-editor-layout"><div class="blog-editor-main">';
echo '<label class="blog-editor-title"><span class="screen-reader-text">Title</span><input type="text" name="title" maxlength="200" value="' . e($post['title']) . '" placeholder="Add title" required></label>';
echo '<label>Article URL slug<input type="text" name="slug" maxlength="200" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="' . e($post['slug']) . '" placeholder="generated-from-title"><small>Leave blank to generate it from the title, or use lowercase letters, numbers, and hyphens.</small></label>';
echo '<label>Summary<textarea name="excerpt" maxlength="320" rows="3" placeholder="Write a short summary for the article listing">' . e($post['excerpt']) . '</textarea></label>';
echo '<details class="blog-seo-fields"><summary>Search preview and SEO</summary><label>SEO title<input type="text" name="seo_title" maxlength="200" value="' . e($post['seo_title'] ?? '') . '" placeholder="Uses article title when blank"></label><label>Meta description<textarea name="seo_description" maxlength="320" rows="3" placeholder="Uses summary or article text when blank">' . e($post['seo_description'] ?? '') . '</textarea><small>Search engines may choose a different title or snippet.</small></label></details>';
try {
    $visualBody = render_article_body($post['body']);
} catch (Throwable $exception) {
    $visualBody = '';
}
$editorBody = article_html_source($post['body']) ?? $post['body'];
$startMode = $post['body'] !== '' && article_html_source($post['body']) === null ? 'plain' : 'visual';
echo '<div class="blog-editor-field"><div class="blog-editor-modes" role="tablist" aria-label="Writing mode"><button type="button" class="blog-mode-button" data-mode="visual" role="tab">Visual</button><button type="button" class="blog-mode-button" data-mode="plain" role="tab">Plain text</button><button type="button" class="blog-mode-button" data-mode="html" role="tab">HTML</button></div><div id="blog-quill-toolbar" class="blog-quill-toolbar"><span class="ql-formats"><select class="ql-header" aria-label="Text style"><option selected></option><option value="2"></option><option value="3"></option><option value="4"></option></select><select class="ql-size" aria-label="Text size"><option value="small"></option><option selected></option><option value="large"></option><option value="huge"></option></select></span><span class="ql-formats"><button class="ql-bold" aria-label="Bold"></button><button class="ql-italic" aria-label="Italic"></button><button class="ql-underline" aria-label="Underline"></button><button class="ql-strike" aria-label="Strikethrough"></button></span><span class="ql-formats"><select class="ql-color" aria-label="Text color"></select><select class="ql-background" aria-label="Highlight color"></select></span><span class="ql-formats"><button class="ql-list" value="ordered" aria-label="Numbered list"></button><button class="ql-list" value="bullet" aria-label="Bulleted list"></button><button class="ql-indent" value="-1" aria-label="Decrease indent"></button><button class="ql-indent" value="+1" aria-label="Increase indent"></button></span><span class="ql-formats"><button class="ql-blockquote" aria-label="Quote"></button><button class="ql-link" aria-label="Insert link"></button><button class="ql-image" aria-label="Insert image"></button><button class="ql-clean" aria-label="Clear formatting"></button><button class="ql-table-better" aria-label="Insert table"></button></span></div><div id="article-body-visual" class="blog-visual-editor" aria-label="Article content editor"></div><textarea id="article-body-plain" class="blog-plain-source" aria-label="Plain text article" placeholder="Write your article. Separate paragraphs with a blank line." spellcheck="true"></textarea><textarea id="article-body-source" class="blog-html-source" aria-label="Article HTML source" placeholder="Paste or edit HTML, including inline CSS." spellcheck="false"></textarea><textarea id="article-body-initial" hidden>' . e($editorBody) . '</textarea><textarea id="article-body-initial-visual" hidden>' . e($visualBody) . '</textarea><input type="hidden" id="article-body-start-mode" value="' . e($startMode) . '"><input type="hidden" name="body" id="article-body-value"><input type="file" id="article-image-upload" accept="image/jpeg,image/png,image/webp" hidden><p class="blog-editor-hint">Use the toolbar to format text, insert links and images, or build comparison tables visually. Choose HTML to paste markup with inline CSS.</p></div>';
echo '</div><aside class="blog-editor-sidebar"><section class="blog-publish-panel"><h2>Publish</h2><label>Status<select name="status"><option value="draft"' . ($post['status'] === 'draft' ? ' selected' : '') . '>Draft</option><option value="published"' . ($post['status'] === 'published' ? ' selected' : '') . '>Published</option></select></label><label>Cover image<input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG, or WebP; max 3 MB.</small></label>';
if (!empty($post['cover_image'])) {
    echo '<p class="blog-cover-current">Current cover image: ' . e($post['cover_image']) . '</p>';
}
echo '<div class="blog-admin-actions"><button class="btn btn-primary" type="submit">' . ($post['status'] === 'published' ? 'Save changes' : 'Save draft') . '</button><a href="admin/posts.php">Cancel</a></div></section></aside></div></form></section>';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-resize-image@1/dist/quill-resize-image.min.js"></script>
<script src="assets/js/blog-editor.js" defer></script>
<?php admin_footer(); ?>
