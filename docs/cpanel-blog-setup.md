# cPanel Blog Admin Setup

## Requirements

- A currently supported PHP version selected for the domain in cPanel MultiPHP Manager.
- PHP extensions `PDO MySQL`, `fileinfo`, and `dom` enabled.
- MySQL or MariaDB, Apache `.htaccess` support, and HTTPS for the domain.

The local project was checked with PHP 8.4 and PDO MySQL enabled. Confirm the version and extensions on the target cPanel account before uploading; local checks do not verify the host.

## Database

1. In cPanel **MySQL Databases**, create a database and a database user. Add the user to the database with the required privileges.
2. Open **phpMyAdmin**, select that database, and import `database/schema.sql` from this project. The `database/` directory is blocked from web access after deployment.

For an existing blog database, apply `database/migrations/2026-10-23-add-post-seo-fields.sql` only if its SEO columns are missing, and apply `database/migrations/2026-10-26-add-post-faq-data.sql` once to add the FAQ column. Do not re-run a migration after its columns have been added. Existing articles keep their content; FAQ data stays empty until added in the editor.

## Private configuration

Create `ergomatic-blog-config.php` outside the website's document root with this shape:

```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'cpanelprefix_blog',
    'db_user' => 'cpanelprefix_bloguser',
    'db_password' => 'your-database-password',
    'base_url' => 'https://www.example.com',
    'admin_setup_key' => 'a-long-random-one-time-secret-at-least-32-characters',
];
```

The code checks `BLOG_CONFIG`, then the account directory above the project, then `config.local.php` in the project root. Keep the file owner-readable and do not commit it. The in-root fallback is blocked by the root `.htaccess` and is ignored by Git. Generate a unique setup key, for example with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` on a trusted machine.

## Create the first administrator

1. Upload the site and configuration over SFTP or cPanel File Manager, enable HTTPS, and make `uploads/blog/` writable by the PHP account. Start with the narrowest permissions cPanel allows; do not set it to world-writable.
2. Open `https://your-domain.example/admin/setup.php`, enter the configured setup key, the admin email, and a unique password of 12 to 72 characters.
3. After the account is created, remove `admin_setup_key` from the private configuration. The setup page also refuses to create another admin once an account exists.
4. Sign in at `https://your-domain.example/admin/login.php`.

If articles return a temporary-unavailable message, check the selected PHP version, PDO MySQL/fileinfo extensions, database name/user/password, imported schema, and cPanel PHP error log. Do not enable public error display on production.

## Public routes and content

- The Articles & News listing is `/news.php`; `/news.html` permanently redirects there.
- Each published article has a clean URL such as `/example-article`; the previous `/article.php?slug=example-article` URL redirects there.
- The admin's optional SEO title and description appear in article metadata; blank values fall back to the article title and summary/body. Article pages also provide Open Graph, Twitter card, and `BlogPosting` metadata. Search engines can choose different snippets and indexing is not guaranteed.
- The editor accepts FAQPage JSON-LD for article-specific questions and answers. FAQ answers also appear visibly on the article page, and the JSON-LD is generated from the validated saved data. FAQ structured data does not guarantee a Google rich result.
- `/sitemap.xml` lists published blog articles and public root-level HTML pages, and `/robots.txt` points crawlers to that sitemap. Both use the configured `base_url`; Apache rewrite support must be enabled. The robots rule for `/admin/` is crawler guidance only, while login protection controls access.
- The visual editor supports image resizing and alignment; the resize module, like Quill and the table extension, is loaded from jsDelivr and requires the author's browser to reach that CDN.
- Drafts are visible only in the admin. The editor uses Quill with the `quill-table-better` table extension, loaded from jsDelivr; admin browsers must be able to reach that CDN. Visual mode supports formatted text, links, uploaded images, and visual table editing. Plain text and HTML-source modes remain available, including pasted HTML with inline CSS. Stored HTML is sanitized before public display, and plain-text blank lines become separate paragraphs.
- Cover images accept JPEG, PNG, or WebP up to 3 MB. The upload directory blocks script execution.

This release manages blog posts only. The other website pages remain static files and must still be edited and uploaded through the hosting file manager or a deployment workflow.
