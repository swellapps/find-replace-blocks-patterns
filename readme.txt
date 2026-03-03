=== Find & Replace Blocks & Patterns ===
Contributors: swellapps, itsdavidmorgan, travistotz
Tags: find replace, blocks, patterns, gutenberg, bulk edit
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk find and replace Gutenberg block or pattern markup across posts, with post-type filtering, dry-run preview, and revision-backed undo.

== Description ==

Find & Replace Blocks & Patterns gives site editors and developers a fast, safe way to do bulk search-and-replace on raw Gutenberg block or pattern markup across all your content.

**Key features:**

* **Paste raw block markup** — search for any block comment, pattern reference, or attribute string exactly as it appears in the editor's Code Editor view.
* **Post-type filtering** — choose which public post types to search (posts, pages, custom post types).
* **Dry-run preview** — see exactly which posts match before applying any changes, including match counts and post status.
* **Revision-backed undo** — for post types that support revisions, a revision is automatically saved before each replacement so you can revert via the post editor if needed.
* **No modified timestamps** — replacements use a direct database update so `post_modified` is not bumped, keeping your editorial workflow clean.

The tool lives under **Tools → Find & Replace Blocks & Patterns** in the WordPress admin and requires the `edit_others_posts` capability.

== Installation ==

1. Upload the `find-replace-blocks-patterns` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Tools → Find & Replace Blocks & Patterns**.

== Frequently Asked Questions ==

= What exactly can I search for? =

Anything stored in `post_content` — block comments (`<!-- wp:paragraph -->`), pattern references (`<!-- wp:pattern {"slug":"my-theme/hero"} /-->`), inline attributes, class names, or any other string.

= Will this affect post modified dates? =

No. Replacements bypass `wp_update_post()` and write directly to the database, so `post_modified` is not changed.

= Can I undo a replacement? =

For post types that support revisions (posts, pages, and most custom post types with revisions enabled), a revision is saved automatically before each replacement. Open the post editor and use the Revisions panel to restore the previous version. Post types that do not support revisions are flagged with a warning in the preview table.

= Does it work with synced patterns (reusable blocks)? =

Yes. Synced patterns are stored as `wp_block` posts. Select the **Block** post type in the post-type filter to include them.

= Is it safe to run on a production site? =

Always run the **Preview Matches** step first to review affected posts. For extra safety, take a full database backup before executing large replacements on post types that do not support revisions.

== Screenshots ==

1. The Find & Replace form with post-type filtering.
2. Preview results table showing matched posts, match counts, and revision support.

== Changelog ==

= 1.1.2 =
* Fixed remaining Plugin Check sanitization warnings by inlining sanitize_key() on post_types array input.

= 1.1.1 =
* Added internationalization support; all user-facing strings are now translation-ready.
* Resolved all Plugin Check warnings: inlined nonce verification, added wp_unslash() to array inputs, and added explanatory phpcs:ignore comments for raw block markup fields.

= 1.1.0 =
* Renamed plugin to Find & Replace Blocks & Patterns; updated all internal prefixes to `frbp`.
* Added Plugin URI, Author URI, Text Domain, Requires at least, and Requires PHP headers.

= 1.0.0 =
* Initial release.
