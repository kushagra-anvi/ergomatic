<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

function app_config(): array
{
    static $config;
    if (isset($config)) {
        return $config;
    }

    $externalPath = getenv('BLOG_CONFIG');
    $paths = array_filter([
        $externalPath ?: null,
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ergomatic-blog-config.php',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.local.php',
    ]);

    foreach ($paths as $path) {
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded)) {
                return $config = $loaded;
            }
        }
    }

    http_response_code(503);
    exit('The blog service is not configured.');
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    foreach (['db_host', 'db_name', 'db_user', 'db_password'] as $key) {
        if (!array_key_exists($key, $config)) {
            throw new RuntimeException('The blog service is not configured.');
        }
    }

    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function plain_text_to_html(string $text): string
{
    $paragraphs = preg_split('/\n[\t ]*\n+/u', trim(str_replace(["\r\n", "\r"], "\n", $text))) ?: [];
    return implode('', array_map(static fn(string $paragraph): string => '<p>' . nl2br(e(trim($paragraph)), false) . '</p>', array_filter($paragraphs, static fn(string $paragraph): bool => trim($paragraph) !== '')));
}

function article_html_source(string $body): ?string
{
    $tags = '/<(p|br|strong|b|em|i|u|s|sub|sup|blockquote|h[1-3]|ol|ul|li|a|img|table|thead|tbody|tr|th|td|span|div)\b/i';
    if (preg_match($tags, $body)) return $body;
    $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $decoded !== $body && preg_match($tags, $decoded) ? $decoded : null;
}

function render_article_body(string $body): string
{
    $html = article_html_source($body);
    return $html !== null ? sanitize_article_html($html) : plain_text_to_html($body);
}

function sanitize_article_html(string $html): string
{
    if (!class_exists(DOMDocument::class)) {
        throw new RuntimeException('The DOM extension is required to sanitize article HTML.');
    }
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'blockquote', 'h1', 'h2', 'h3', 'ol', 'ul', 'li', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div'];
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?><div id="article-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->getElementById('article-root');
    if (!$root) {
        return '';
    }
    $clean = static function (DOMNode $node) use (&$clean, $allowed): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowed, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'svg', 'math'], true)) {
                        $node->removeChild($child);
                    } else {
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                    }
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $value = trim($attribute->value);
                    if ($name === 'style') {
                        $styles = [];
                        foreach (explode(';', $value) as $declaration) {
                            if (strpos($declaration, ':') === false) continue;
                            [$property, $styleValue] = array_map('trim', explode(':', $declaration, 2));
                            $property = strtolower($property);
                            $styleValue = strtolower($styleValue);
                            $patterns = [
                                'color' => '/^(#[0-9a-f]{3,8}|[a-z]{3,20}|rgba?\([0-9.,% ]+\))$/i',
                                'background-color' => '/^(#[0-9a-f]{3,8}|[a-z]{3,20}|rgba?\([0-9.,% ]+\))$/i',
                                'text-align' => '/^(left|right|center|justify)$/',
                                'font-size' => '/^[0-9.]+(px|pt|em|rem|%)$/',
                                'font-family' => '/^[a-z0-9 ,"\'_-]{1,80}$/i',
                                'font-weight' => '/^(normal|bold|[1-9]00)$/',
                                'font-style' => '/^(normal|italic|oblique)$/',
                                'text-decoration' => '/^(none|underline|line-through)$/',
                                'line-height' => '/^[0-9.]+(px|em|rem|%)?$/',
                                'vertical-align' => '/^(top|middle|bottom|baseline)$/',
                                'padding' => '/^[0-9.]+(px|em|rem|%)$/',
                                'width' => '/^[0-9.]+(px|%)$/', 'max-width' => '/^[0-9.]+(px|%)$/', 'height' => '/^[0-9.]+(px|%)$/', 'max-height' => '/^[0-9.]+(px|%)$/',
                                'border' => '/^[0-9.]+px (solid|dashed|dotted) (#[0-9a-f]{3,8}|[a-z]{3,20})$/i',
                                'border-collapse' => '/^(collapse|separate)$/',
                            ];
                            if ($tag === 'img') {
                                $patterns += [
                                    'float' => '/^(left|right|none)$/',
                                    'display' => '/^(block|inline|inline-block)$/',
                                    'margin' => '/^(auto|0|[0-9]{1,3}(px|em|rem|%)?)( (auto|0|[0-9]{1,3}(px|em|rem|%)?)){0,3}$/',
                                ];
                            }
                            $validStyle = isset($patterns[$property]) && preg_match($patterns[$property], $styleValue);
                            if ($tag === 'img' && in_array($property, ['width', 'height', 'max-width', 'max-height'], true)) {
                                $dimension = [];
                                $validStyle = preg_match('/^([1-9]\d{0,3})(px|%)$/', $styleValue, $dimension)
                                    && (int) $dimension[1] <= ($dimension[2] === '%' ? 100 : 1600);
                            }
                            if ($validStyle) $styles[] = $property . ':' . $styleValue;
                        }
                        if ($styles) $child->setAttribute('style', implode(';', $styles)); else $child->removeAttribute('style');
                    } elseif (in_array($name, ['href', 'src'], true)) {
                        $parts = parse_url($value);
                        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                        if (($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) || strpos($value, '//') === 0 || preg_match('/[\x00-\x20]/', $value)) $child->removeAttribute($name);
                    } elseif ($name === 'data-list' && $tag === 'li' && in_array($value, ['bullet', 'ordered'], true)) {
                        // Quill 2 uses this marker to distinguish bullet and numbered list items.
                    } elseif (!in_array($name, ['alt', 'title', 'width', 'height', 'colspan', 'rowspan'], true)) {
                        $child->removeAttribute($name);
                    } elseif (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true)) {
                            $validDimension = preg_match('/^\d{1,4}$/', $value);
                        if ($tag === 'img' && in_array($name, ['width', 'height'], true)) {
                            $validDimension = preg_match('/^[1-9]\d{0,3}%?$/', $value)
                                && (int) $value <= (str_ends_with($value, '%') ? 100 : 1600);
                        }
                        if (!$validDimension) $child->removeAttribute($name);
                    }
                }
                if ($tag === 'a' && $child->hasAttribute('href')) $child->setAttribute('rel', 'noopener noreferrer');
                if ($tag === 'img' && !$child->hasAttribute('src')) $node->removeChild($child);
                else $clean($child);
            }
        }
    };
    $clean($root);
    $result = '';
    foreach ($root->childNodes as $child) $result .= $document->saveHTML($child);
    return trim($result);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function post_string(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}

function require_csrf(): void
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(419);
        exit('Your session expired. Please go back, reload the page, and try again.');
    }
}

function admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
    header('Cache-Control: no-store, private');
}

function redirect(string $location): void
{
    header('Location: ' . $location, true, 303);
    exit;
}

function site_base_path(): string
{
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (substr($path, -6) === '/admin') {
        $path = substr($path, 0, -6);
    }
    return rtrim($path, '/') . '/';
}

function site_head(string $title, string $description = '', ?string $canonical = null, array $social = []): void
{
    $descriptionTag = $description !== '' ? '<meta name="description" content="' . e($description) . '">' : '';
    $canonicalTag = $canonical !== null ? '<link rel="canonical" href="' . e($canonical) . '">' : '';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="google-site-verification" content="arY0JNxrFU9JtJOdOKuVkav-7zB7nUwv8Eb3Hp8nO-w"><script async src="https://www.googletagmanager.com/gtag/js?id=G-JRS859JR8Y"></script><script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag("js", new Date()); gtag("config", "G-JRS859JR8Y");</script>';
    echo '<base href="' . e(site_base_path()) . '"><title>' . e($title) . '</title>' . $descriptionTag . $canonicalTag;
    if ($social) {
        foreach (['og:title' => $title, 'og:description' => $description, 'og:type' => 'article', 'og:site_name' => 'INDEVA Ergomatic India', 'og:url' => $canonical, 'og:image' => $social['image'] ?? null, 'article:published_time' => $social['published'] ?? null, 'article:modified_time' => $social['modified'] ?? null, 'twitter:card' => 'summary_large_image', 'twitter:title' => $title, 'twitter:description' => $description, 'twitter:image' => $social['image'] ?? null] as $property => $content) {
            if ($content !== null && $content !== '') {
                $attribute = str_starts_with($property, 'twitter:') ? 'name' : 'property';
                echo '<meta ' . $attribute . '="' . e($property) . '" content="' . e($content) . '">';
            }
        }
        if (!empty($social['json_ld'])) echo '<script type="application/ld+json">' . json_encode($social['json_ld'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) . '</script>';
    }
    echo '<link rel="stylesheet" href="assets/css/style.css"><link rel="icon" type="image/png" href="assets/favicon.png"></head><body>';
    echo '<header id="site-header"></header>';
}

function site_foot(): void
{
    echo '<footer id="site-footer"></footer><script src="https://unpkg.com/lucide@0.294.0"></script><script src="assets/js/main.js?v=4"></script></body></html>';
}

function admin_header(string $title): void
{
    site_head($title . ' | Blog Admin');
    echo '<main class="section"><div class="container blog-admin"><div class="blog-admin-heading"><div><span class="hero-subtitle">INDEVA Ergomatic</span><h1>' . e($title) . '</h1></div>';
    if (admin_logged_in()) {
        echo '<nav class="blog-admin-nav"><a class="btn btn-outline-white" href="admin/posts.php">Manage posts</a><a class="btn btn-outline-white" href="admin/post-edit.php">New article</a><form method="post" action="admin/logout.php">' . csrf_field() . '<button class="btn btn-primary" type="submit">Sign out</button></form></nav>';
    }
    echo '</div>';
}

function admin_footer(): void
{
    echo '</div></main>';
    site_foot();
}

function service_unavailable(): void
{
    http_response_code(503);
    site_head('Articles temporarily unavailable');
    echo '<main class="section"><div class="container"><div class="card"><h1>Articles are temporarily unavailable</h1><p>Please try again later.</p></div></div></main>';
    site_foot();
    exit;
}
