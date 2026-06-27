# Content Quicksand

**Keep readers on your site.** Land on one post, see the series nav and related articles, click through — and never leave.

Content Quicksand auto-detects post series, shows intelligent related posts, and inserts contextual links within your content. All without LLMs, cron jobs, or external services.

## Table of Contents

- [Features](#features)
- [How It Works](#how-it-works)
- [Installation](#installation)
- [Usage](#usage)
- [WP-CLI](#wp-cli)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Requirements](#requirements)
- [Changelog](#changelog)
- [License](#license)

## Features

### Series Navigation

Detects post series automatically from title patterns:

| Pattern | Example | Matches |
|---------|---------|---------|
| `Ep.X` | `Ep.1`, `Ep 5` | `Origin Story Ep.3` |
| `Part X` | `Part 1`, `Part 2` | `Getting Started Part 1` |
| `에피소드 X` | `에피소드 3` | `시리즈 에피소드 3` |

Renders prev/next navigation with position indicator: `← Previous | 3/7 | Next →`

Add custom patterns via `cq_series_patterns` filter.

### Related Posts (Multi-Dimensional Scoring)

Every candidate post is scored across five axes:

| Axis | Weight | Why |
|------|--------|-----|
| Same series | +60 | Same series = highest relevance |
| Shared categories | +10 each | Same topic area |
| Content similarity | 0–30 | TF-IDF cosine similarity (if CGE data available) |
| Recency | 0–10 | Newer posts get a log-scale boost |
| Shared tags | +5 each | Same tags |

The top 6 scored posts appear as a card grid below the post content, showing category, title, and date.

### In-Content Links (Automatic)

When you publish a new post, the plugin:

1. Extracts meaningful keywords from your post title
2. Searches 20 existing posts (same category, or all if uncategorized)
3. Finds the first keyword match in the target post
4. Inserts one contextual link on the keyword's first occurrence
5. Done in milliseconds — no queue, no cron, no LLM

Example: publishing "My ARD Implementation and Obsidian Workflow" will automatically link "ARD" to your ARD post and "Obsidian" to your Obsidian post.

This runs via WordPress's `publish_post` hook — instant, not scheduled.

### Admin Dashboard

| Feature | Where | How |
|---------|-------|-----|
| Per-post link | Post list → Quicksand link | Click to link that single post |
| Bulk link | Select → Bulk Actions → Run Quicksand | Link multiple posts at once |
| Link all | Tools → Quicksand → button | One click, all posts |

### WP-CLI

```bash
# Link all published posts
wp quicksand --all

# Link specific posts
wp quicksand --ids=49,73,104

# Link single post
wp quicksand 49
```

## How It Works

```
                         publish_post hook
                                │
                    ┌───────────┴───────────┐
                    │                       │
               New Post               Existing Posts
                    │                       │
           Extract keywords          Same category?
           from title (filter         └─ Yes: search 20
           stopwords, short words)       └─ No: search all
                    │                       │
                    └─────── Match ─────────┘
                                │
                     ┌──────────┴──────────┐
                     │                     │
               Keyword found?         Not found?
                     │                     │
             Insert <a> link           Skip (footer
             on first match            still shows
                     │                 related posts)
               wp_update_post()
```

No external services. No Python scripts. No cron jobs. Pure PHP, runs in the same request as the publish action.

## Installation

### From GitHub (current)

```bash
wp plugin install https://github.com/humanerd-drew/content-quicksand/archive/refs/heads/main.zip --activate
```

### Manual

1. Download the [latest release](https://github.com/humanerd-drew/content-quicksand/archive/refs/heads/main.zip)
2. Upload `content-quicksand` folder to `/wp-content/plugins/`
3. Activate through WordPress admin

### From WordPress.org

Coming soon.

## Usage

No configuration needed. After activation:

1. Visit any published post on your site
2. Scroll to the bottom — you'll see the quicksand section
3. Write and publish a new post — the linker runs automatically

To manually trigger on existing posts, use the admin dashboard or WP-CLI (see above).

## Frequently Asked Questions

**Does this work with my theme?**

It hooks into `the_content()` — the standard WordPress filter. It works with any theme that uses this function (Practically all of them).

**Does this slow down my site?**

The related posts query runs on single post views only (via `is_singular('post')`). It queries 30 posts max and uses standard WordPress caching. The in-content linker runs only on publish, not on page load.

**Can I exclude certain posts or categories?**

Not yet — this is planned for a future release.

**Does this work with custom post types?**

Currently only `post`. Custom post type support is planned.

**Why in-content links only on publish?**

The `publish_post` hook runs the linker immediately when you hit Publish. For existing posts, use the admin dashboard or WP-CLI (`wp quicksand --all`).

**Is there a JavaScript dependency?**

No. Zero JavaScript. All rendering happens server-side via `the_content` filter.

**Can I use this without CGE (Content Graph Engine)?**

Yes. The plugin works fully without CGE. It falls back to category + recency scoring. CGE integration is optional and Drewgent-specific.

## Requirements

- WordPress 5.5+
- PHP 8.0+
- `mbstring` PHP extension (for Korean/UTF-8 keyword matching)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full release history.

## License

GPLv2. Built for [humanerd.kr](https://humanerd.kr).
