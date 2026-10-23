# PHP/MySQL Blog Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a secure cPanel-ready PHP/MySQL blog admin and public article pages.

**Architecture:** Server-rendered PHP with PDO/MySQL, minimal shared helpers, session-based admin auth, and the current CSS/header/footer conventions. Existing static pages remain unchanged except the shared Articles & News navigation target. Preserve old `news.html` links with a permanent Apache redirect.

**Tech Stack:** PHP 8.0-compatible syntax, PDO MySQL, MySQL/MariaDB, Apache .htaccess.

**Spec:** `docs/superpowers/specs/2026-10-22-blog-admin-design.md`

## Global Constraints

- No framework or new dependency.
- Use prepared SQL, password hashing, CSRF protection, HTML escaping, and authorization checks.
- Draft posts are never public; destructive actions use POST.
- Admin-created article body is plain text, not raw HTML.
- Confirm PHP/PDO MySQL and document-root layout on target cPanel before deployment.

## Review Focus

- Invalid or duplicate slugs cannot collide or expose another article.
- Draft/unpublished content stays inaccessible by direct article URL.
- Anonymous requests cannot reach admin operations; CSRF-invalid mutations fail.
- Invalid or oversized uploads are rejected and uploaded files cannot execute.
- Database errors do not reveal credentials or SQL details to public users.

## Files

- Create `includes/bootstrap.php`: private config loading, PDO connection, escaping, CSRF, and auth helpers.
- Create `database/schema.sql`: admin and post tables.
- Create `admin/index.php`, `admin/login.php`, `admin/logout.php`, `admin/posts.php`, `admin/post-edit.php`, `admin/post-delete.php`, `admin/setup.php`, `admin/.htaccess`: protected administration.
- Create `article.php`: published article detail.
- Create `uploads/blog/.htaccess`: disable script execution.
- Create `docs/cpanel-blog-setup.md`: DB/config/first-admin/deployment steps.
- Create `.htaccess` redirecting legacy `news.html` to `news.php`; modify `assets/js/main.js` navigation destination.

## Tasks

### Task 1: Database and shared security helpers

**Files:** create the schema and `includes/bootstrap.php`.

- Define `admins` and `posts` with unique email and slug, draft/published status, timestamps, optional image path, and indexes for public ordering.
- Add PDO connection, `e()` HTML escaping, CSRF token validation, login-required redirect, and session setup.
- Ensure credentials are loaded from a private config path and public errors are generic.
- Check with PHP syntax validation; verify schema constraints and helper behavior with a small local PHP self-check.

### Task 2: Admin authentication and first-admin setup

**Files:** create `admin/setup.php`, `admin/login.php`, `admin/logout.php`, `admin/index.php`, `admin/.htaccess`.

- Permit setup only when no admin exists and a one-time configured setup key matches; create account with `password_hash`, then make setup unavailable.
- Authenticate with `password_verify`, regenerate session ID on success, and use POST + CSRF for logout.
- Protect every admin page and deny direct access to setup/config/SQL artifacts.
- Check anonymous redirect, wrong-password rejection, successful login/logout, and setup closing after first account.

### Task 3: Post create/edit/delete workflow

**Files:** create `admin/posts.php`, `admin/post-edit.php`, `admin/post-delete.php`, upload protection.

- Provide list, create, edit, draft/publish, and POST-only delete.
- Validate required title/body, generate and validate unique slugs, use prepared SQL, and render values escaped.
- Accept optional cover image only for approved image MIME types within a fixed size limit; store randomized filenames and reject script execution in the upload directory.
- Check create/edit/delete, duplicate slug, CSRF failure, and invalid/oversized upload behavior. Remove an old cover image only after its post update or deletion succeeds.

### Task 4: Public article pages and existing navigation

**Files:** create `news.php` and `article.php`; create root `.htaccess`; modify `assets/js/main.js`.

- Render published posts in existing card styling and link to `article.php?slug=<slug>`.
- Render one published article with escaped title/summary/body, cover image, canonical metadata, and 404 for missing, invalid, or draft slugs.
- Point the shared Articles & News link to `news.php`; redirect legacy `news.html` requests with HTTP 301; retain existing header/footer injection.
- Check empty listing, published listing/detail, draft direct access, missing slug, and legacy redirect.

### Task 5: cPanel deployment guide and final verification

**Files:** create `docs/cpanel-blog-setup.md`.

- Document PHP/PDO requirements, MySQL database/user setup, schema import, private config placement, one-time setup key, HTTPS, upload permissions, and setup-key removal.
- Run available PHP syntax checks and the project's focused local checks. Inspect changed files and confirm no credentials are committed.
- Treat live cPanel behavior as unverified until deployed on the user's host.

