# Blog Admin Design

## Goal

Add a cPanel-compatible PHP/MySQL admin where authorized staff can create, edit, delete, draft, and publish blog posts. Published posts appear on the existing Articles & News page and have individual public pages.

## Project context

The site currently consists of static HTML, CSS, and JavaScript. There is no PHP application, database schema, or authentication flow. Shared navigation and footer are injected by `assets/js/main.js`; `news.html` contains placeholder cards and is linked by that shared navigation.

## Design

Use small, server-rendered PHP pages and PDO with MySQL/MariaDB. Keep public pages and the shared design intact. Replace the Articles & News route with a PHP page that queries published posts; add a PHP article detail route using a validated slug. The admin lives under `/admin/` and requires session authentication.

Store admin accounts and posts in MySQL. Hash passwords with PHP's password API; use prepared statements, CSRF tokens for mutations, session ID regeneration on login, authorization checks on every admin request, escaped output, and validation for title, slug, status, and uploaded cover image. Deletion is POST-only. Drafts never appear publicly. Provide a one-time, configuration-key-protected first-admin setup that is disabled after an admin is created.

Keep DB credentials and the one-time setup key in a private PHP configuration file outside the public document root when cPanel layout allows; document a protected in-root fallback for hosts that place the repository directly in the document root. Use Apache access rules to deny access to configuration, SQL, and upload execution. Do not add a framework or dependency.

## Scope

Included: admin sign-in/out, first-admin provisioning, post list/create/edit/delete, draft/publish, optional cover image, public article listing/detail, cPanel setup notes, and checks for security-sensitive flows.

Excluded: editing non-blog pages, roles, comments, analytics, rich text editor dependencies, search, categories/tags, and email/newsletter functions. Article body uses plain text with safe line-break rendering; no raw HTML.

## Deployment assumptions

The host provides PHP with PDO MySQL and MySQL or MariaDB. The exact available version and document-root layout must be checked before deployment. The code should use PHP 8.0-compatible syntax unless host verification shows a higher baseline.

