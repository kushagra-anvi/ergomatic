# PHP Blog Production Readiness Design

**Date:** 2026-10-23

## Intent

Make the existing PHP/MySQL blog easier to write and publish, and improve its public search metadata and sitemap for the cPanel deployment. Keep this limited to the blog system; the rest of the website remains static.

## Chosen approach

Extend the current PHP admin and Quill editor. Keep the existing post URLs, authentication, image upload endpoint, sanitizer, and public theme. Add only the fields and routes needed for the requested publishing workflow. Do not migrate to WordPress or introduce a framework.

## Authoring experience

- Add image resizing and left/center/right alignment to the existing visual editor. Persist only validated dimensions and alignment styles through the existing article sanitizer so saved content behaves the same after reload and on the public article page.
- Give the first row of blog tables a darker header treatment in the editor and public article styles. The first row remains regular table content that authors can populate visually.
- Keep Visual, Plain text, and HTML modes and the existing authenticated/CSRF-protected image upload flow.

## SEO and public article pages

- Add optional SEO title and meta description fields to the post editor and database. Use the article title when SEO title is blank; use the summary when available, otherwise a trimmed plain-text excerpt from the body, for the description.
- Render description, canonical URL, Open Graph and Twitter card metadata, and `BlogPosting` JSON-LD for published articles. Derive structured data from the same public article data, encode JSON safely, and keep publication/update dates consistent with visible dates.
- Preserve `/article.php?slug=...` URLs and use those exact URLs as canonical and sitemap entries; this avoids invalidating existing links. SEO fields guide snippets but cannot guarantee search ranking or that a search engine will display the supplied title/description.
- Do not add meta keywords or claim guaranteed indexing/rich results.

## Sitemap and crawler guidance

- Add dynamic `/sitemap.xml` and `/robots.txt` routes using the configured `base_url`, including correct subdirectory deployments.
- The sitemap lists published article URLs and public root-level `.html` pages, excluding `news.html` because it redirects to the PHP listing. Add `lastmod` where reliable and XML-escape every value.
- `robots.txt` points crawlers to the sitemap and disallows `/admin/`. It is crawler guidance only; the existing authentication remains the access control.
- Keep database and private configuration files inaccessible through Apache rules.

## Database and deployment

- Add nullable SEO columns to the fresh-install `database/schema.sql` and provide a separate one-time migration for current installations (with instructions for applying once in phpMyAdmin).
- Update cPanel setup notes with the new metadata fields, route behavior, and required Apache rewrite support. Do not require a new PHP package or Composer/NPM build.
- Keep deployments backward-compatible after migration: blank SEO fields use fallbacks. Existing published posts and links remain intact.

## Security and boundaries

- Continue to validate at the HTML trust boundary. Only allow positive bounded image width/height values and the small alignment style set required by the editor; strip event handlers, script content, and unsafe URLs as before.
- Preserve CSRF, authenticated admin checks, random upload names, file-type checks, and the existing upload-directory execution block.
- Do not add static-page editing, multi-user roles, categories, comments, media management/orphan cleanup, a new authentication provider, or external SEO submission service. Uploaded images remain shared files; automatic deletion is excluded to avoid removing a file still referenced by another post.

## Verification

- Extend the existing assert-based blog helper check for allowed image sizing/alignment and rejection of unsafe style values.
- Run PHP syntax checks, the blog helper self-check, JavaScript syntax validation, and `git diff --check` after implementation.
- Check generated article metadata and sitemap/robots output locally where the current DB configuration permits. Inspect the public article/table styles and editor at desktop/mobile widths if an authenticated local session is available.
- cPanel database migration, Apache rewrite behavior, remote CDN availability, and live crawler behavior remain deployment checks; code-level checks cannot prove those external conditions.
