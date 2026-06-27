# Changelog

## 0.1.0 — 2026-06-27

Initial release.

### Added
- **Series Navigation**: Auto-detects post series from title patterns (`Ep.1`, `Part 2`, `에피소드 3`, etc.) and renders prev/next navigation with position indicator.
- **Post Footer**: Renders a "함께 읽으면 좋은 글" section at the bottom of every single post view, with up to 6 related post cards (category, title, date).
- **In-Content Links**: On publish (`publish_post` hook), automatically extracts keywords from the new post title, finds matching existing posts, and inserts one contextual link. Pure PHP, no LLM/Python/cron.
- **Admin Row Action**: Each published post on the list (`edit.php`) gets a "Quicksand" link — click to run the linker on that single post.
- **Admin Bulk Action**: Select multiple posts → Bulk Actions → "Run Quicksand" — mass process in one click.
- **Admin Page**: Tools → Quicksand — a dashboard page with a "Run on all posts" button.
- **WP-CLI**: `wp quicksand --all` or `wp quicksand --ids=49,73,104` — headless batch processing.
- **Scoring**: Series match (+60), shared categories (+10 each), recency (+0-10), tag overlap (+5 each), optional CGE TF-IDF (+0-30).
- **Zero Dependencies**: No Python, no cron, no LLM, no external APIs. Works out of the box on any WordPress 5.5+ site.
- **Responsive**: Auto-adapts from multi-column to single-column on mobile. All CSS inline via `wp_add_inline_style`.

### Technical
- Files: `content-quicksand.php` (main + hooks), `includes/class-{series,scorer,renderer,linker,admin,cli}.php`
- No build step, no JavaScript, no composer
- GPLv2
