# Blog Production Readiness Implementation Plan

**Goal:** Improve the existing PHP blog editor, article SEO metadata, and crawler sitemap for the cPanel site.

**Architecture:** Extend the current Quill/PHP/MySQL flow, preserve existing article URLs and auth boundaries, and add two dynamic crawler routes. Add SEO columns with a one-time SQL migration for existing installations.

**Tech Stack:** Framework-free PHP, PDO/MySQL, Apache `.htaccess`, Quill 2.0.3, quill-table-better, pinned browser UMD resize module, existing JavaScript and CSS.

**Spec:** `docs/superpowers/specs/2026-10-23-blog-production-readiness-design.md`

## Global Constraints

- Keep the work within the existing PHP blog admin and public blog.
- Preserve `/article.php?slug=...`, current authentication, CSRF checks, and protected image upload flow.
- Sanitize image sizing/alignment before public rendering.
- Keep the non-blog site pages static; add no framework or package build.
- Blank SEO fields must fall back to current article title, summary, and body text.

## Review Focus

- Invalid image style values must not survive sanitization; only bounded dimensions and approved alignment values are retained.
- Existing article rows and URLs remain usable after the SQL migration.
- HTML metadata and JSON-LD values must be escaped/encoded safely when titles contain quotes, ampersands, or markup.
- Drafts must not appear in the sitemap; only public HTML pages and published posts are listed.
- `/robots.txt` must not be treated as security; admin stays protected by login.

---

### Task 1: Persist safe resized/aligned images and table headers

**Files:**
- Modify: `includes/bootstrap.php`
- Modify: `admin/post-edit.php`
- Modify: `assets/js/blog-editor.js`
- Modify: `assets/css/style.css`

**Interfaces:**
- `sanitize_article_html(string $html): string` retains only positive bounded `width`/`height` and supported image alignment styles.
- Existing editor form continues to post `body`; the resize module writes image dimensions/alignment into saved article HTML.

- [x] Load a major-version-pinned browser UMD build of `quill-resize-image`, register `modules/resize` before editor creation, and enable bounded aspect-preserving resize with alignment persistence for images.
- [x] Style the first row of blog tables distinctly in the visual editor and public article view.

### Task 2: Add per-article SEO metadata

**Files:**
- Modify: `database/schema.sql`
- Create: `database/migrations/2026-10-23-add-post-seo-fields.sql`
- Modify: `admin/post-edit.php`
- Modify: `article.php`

**Interfaces:**
- Add optional `seo_title VARCHAR(200)` and `seo_description VARCHAR(320)` columns.
- Public fallbacks: title → post title; description → SEO description → excerpt → plain-text body excerpt.
- Article URL/canonical remains `article.php?slug=<slug>`.

- [x] Add SEO title/description form inputs, validation, and insert/update persistence.
- [x] Add title, description, canonical, Open Graph, Twitter card, and safely encoded `BlogPosting` JSON-LD to public articles.
- [x] Keep published/updated structured-data dates aligned with article display.

### Task 3: Generate sitemap and crawler guidance

**Files:**
- Create: `sitemap.php`
- Create: `robots.php`
- Modify: `.htaccess`
- Modify: `database/.htaccess` if needed to explicitly block the migration/schema files

**Interfaces:**
- `/sitemap.xml` rewrites to `sitemap.php`; output uses `application/xml` and configured absolute `base_url`.
- `/robots.txt` rewrites to `robots.php`; output names the absolute sitemap URL and disallows `/admin/` under the configured base path.

- [x] Include the public blog listing, root public `.html` pages (except redirecting `news.html`), and published article URLs only.
- [x] XML-escape all URLs/date values and emit file/post `lastmod` values when available.
- [x] Preserve existing access controls and block private SQL files from HTTP access.

### Task 4: Document cPanel migration and publishing behavior

**Files:**
- Modify: `docs/cpanel-blog-setup.md`

- [x] Explain when to import the fresh schema vs. apply the one-time SEO migration to an existing database.
- [x] Document the dynamic sitemap/robots routes and image resize/alignment behavior.
- [x] State that cPanel rewrite, database, CDN, and live crawler behavior still require deployment checks.

## Completion Checks

- [x] Review the changed PHP/JavaScript and targeted files; no existing unrelated user changes were staged or reverted.
- [x] Run PHP and JavaScript syntax checks plus `git diff --check`; no syntax or whitespace errors were reported.
- [ ] Browser/authenticated-route, database migration, cPanel rewrite, CDN, and live crawler behavior still need deployment/runtime validation.
