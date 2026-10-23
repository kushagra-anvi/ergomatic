# WordPress Classic-Style Blog Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the inconsistent post editor with a WordPress Classic-inspired writing screen that supports visual formatting and tables, plain text, and HTML source.

**Architecture:** Restore Quill 2.0.3 for rich text and add `quill-table-better` 1.x for visual table creation/editing. Keep the existing PHP post schema, image-upload endpoint, CSRF flow, and HTML sanitizer. Use a focused writing column with title and summary above the editor and a publishing sidebar for status, cover image, and save actions.

**Tech Stack:** PHP 8+, existing PDO/MySQL application, Quill 2.0.3, quill-table-better 1.x, plain JavaScript, CSS. Load pinned-major CDN assets from jsDelivr; the cPanel admin browser must be able to reach the CDN.

**Spec:** `docs/superpowers/specs/2026-10-23-wordpress-style-blog-editor-design.md`

## Global Constraints

- Must run on the existing framework-free PHP/MySQL site and cPanel hosting.
- No Gutenberg block data model, full-site CMS, or changes to static page administration.
- Keep image upload authenticated and CSRF-protected; sanitize stored HTML server-side.
- Keep Visual, Plain text, and HTML modes; preserve paragraph boundaries and safe inline styles.

## Review Focus

- A saved visual table reloads in the editor and renders as a table publicly; test the table plugin HTML serialization and re-import path in Task 2.
- Existing plain-text articles retain blank-line paragraph breaks; test `plain_text_to_html()` in Task 1.
- HTML-source mode cannot store script/event-handler or unsafe URL content; extend the sanitizer self-check in Task 1.
- Failed image upload does not replace article content or bypass CSRF; manually verify the existing upload endpoint in Task 2.
- The publishing sidebar remains usable on a narrow viewport; inspect responsive layout in Task 3.

---

### Task 1: Confirm existing article rendering safeguards

**Files:**
- Modify: `includes/bootstrap.php` to retain Quill list type markers safely
- Modify: `tests/blog_helpers_self_check.php`
- Modify: `assets/css/style.css` for public Quill list rendering
- Inspect: `includes/bootstrap.php`
- Inspect: `article.php`
- Run: `tests/blog_helpers_self_check.php`

**Interfaces:**
- `plain_text_to_html(string $text): string` renders blank-line-separated paragraphs and single line breaks safely.
- `article_html_source(string $body): ?string` returns raw or legacy entity-decoded allowed article HTML, otherwise `null`.
- `render_article_body(string $body): string` sanitizes HTML or renders plain text as paragraphs.

- [x] Confirm `article.php` uses `render_article_body()` and the existing self-check covers paragraphs, legacy table markup, inline styles, and unsafe content removal.
- [x] Add a self-check for preserving only Quill's safe bullet/ordered list markers; observed the failure before updating the sanitizer.
- [x] Run `php tests/blog_helpers_self_check.php`; all 13 checks pass.

### Task 2: Build the Classic-style editor

**Files:**
- Modify: `admin/post-edit.php`
- Create: `assets/js/blog-editor.js`

**Interfaces:**
- Form field `#article-body-value` remains the submitted `body` field.
- `#article-body-visual` is the Quill canvas; `#article-body-plain` is plain text; `#article-body-source` is HTML source.
- Image insertion continues to POST to `admin/image-upload.php` with `X-CSRF-Token` and consumes its `{url}` response.

- [x] Replace the `contenteditable` toolbar with Quill 2.x plus `quill-table-better` 1.x from jsDelivr; add the table plugin CSS/JS and its toolbar button.
- [x] Configure the table plugin with Quill's default table module disabled, enable the plugin's table toolbar/grid, register its keyboard bindings, and use the plugin's initialization conversion path for existing table HTML.
- [x] Before saving Visual content, call the plugin's `deleteTableTemporary()` then serialize with `getSemanticHTML()`; preserve source and plain text values when their modes are active.
- [x] Implement mode switches that synchronize representable content, keeping HTML source editable and plain text submitted without markup.
- [x] Keep the image handler connected to the existing authenticated upload endpoint and CSRF token.
- [ ] Local login verification requires a configured admin session; the route redirects to `admin/login.php` when unauthenticated.

### Task 3: Restyle the post form as a WordPress-style writing screen

**Files:**
- Modify: `admin/post-edit.php`
- Modify: `assets/css/style.css`

- [x] Place title, slug, and summary above a wide writing canvas.
- [x] Move status, cover image, and Save/Publish controls into a compact right sidebar on desktop; stack it below the editor on mobile.
- [x] Style the toolbar, canvas, mode tabs, table grid, focus state, and validation messages to match the site's existing admin colors while keeping the writing surface calm and uncluttered.
- [ ] Inspect the editor at desktop and mobile widths; browser validation requires a logged-in admin session.

### Task 4: Update deployment notes and verify public rendering

**Files:**
- Modify: `docs/cpanel-blog-setup.md`

- [x] Document Visual/Plain text/HTML modes, visual table editing, the CDN dependency, and the `dom` PHP extension requirement.
- [x] Run PHP lint on changed PHP, the blog helper self-check, JavaScript syntax validation, and `git diff --check`.
- [x] Locally confirm the admin route remains protected; it redirects to sign-in without an admin session. Saved visual table checks require an authenticated session, and cPanel deployment behavior remains unverified.
