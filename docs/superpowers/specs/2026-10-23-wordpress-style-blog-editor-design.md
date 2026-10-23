# WordPress Classic-Style Blog Editor Design

**Date:** 2026-10-23

## Intent

Make the PHP blog admin feel like a familiar WordPress writing screen. Authors need a reliable visual editor for formatted paragraphs, links, uploaded images, and comparison tables, while retaining plain-text and HTML-source editing modes.

## Approaches

- **Classic editor:** A focused title and writing canvas, predictable formatting toolbar, visual table controls, and a separate publishing sidebar. Keep plain-text and HTML modes alongside visual editing. This is the recommended choice because it matches the user's requested WordPress feel without introducing block-editor complexity.
- **Block editor:** Build a Gutenberg-like block system. This can match modern WordPress more closely, but would add substantial editing and persistence behavior outside the current need.

## Scope

- Replace the inconsistent browser-native toolbar with a maintained Classic-style rich-text editor that supports visual table creation and editing.
- Arrange title and summary above a wide writing canvas; move status, cover image, and save/publish actions into a compact sidebar that adapts on mobile.
- Provide Visual, Plain text, and HTML modes. Switching modes should preserve content where representable; raw HTML remains available for inline CSS and advanced markup.
- Keep existing PHP/MySQL post fields, upload flow, and server-side HTML sanitization. Preserve paragraph boundaries for plain text and display saved tables as actual tables.
- Keep the change confined to blog administration and public article rendering.

## Constraints and Non-goals

- Must run on the existing framework-free PHP/MySQL site and cPanel hosting.
- No Gutenberg block data model, full-site CMS, or changes to static page administration.
- Select and package the editor library using its current supported cPanel-compatible distribution after checking official documentation.

## Verification

Check editor mode switching, formatted text, table creation/edit/save/reload/public rendering, image insertion, plain-text paragraph rendering, HTML inline-style sanitization, and the responsive layout. cPanel behavior remains unverified until deployed there.