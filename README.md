# Content Quicksand

Keep readers on your site with series-aware navigation and intelligent related posts.

**Quicksand effect**: readers land on one post, see the series nav and related articles, click through — and never leave.

## Features

### Series Navigation
Auto-detects post series by title patterns (`Ep.1`, `Part 2`, `에피소드 3`) and renders prev/next navigation with position indicator. Custom patterns can be added via filter.

### Related Posts (Multi-Dimensional Scoring)
Scores every candidate across five axes:

| Axis | Weight | Source |
|------|--------|--------|
| Series match | +60 | Same series = highest priority |
| Category | +10 each | Shared categories |
| Content similarity | 0–30 | TF-IDF cosine (from CGE or built-in) |
| Recency | 0–10 | Log decay over time |
| Tag overlap | +5 each | Shared tags |

### Real-Time Updates
Triggers content graph rebuild when a new post is published — no waiting for the daily cron.

### In-Content Links (Optional, via CGE)
When paired with [Content Graph Engine](https://github.com/humanerd-drew/content-graph-engine), the plugin automatically adds contextual links within post content using TF-IDF keyword matching.

## Installation

```bash
# Option A: WordPress admin
Upload content-quicksand/ → Activate

# Option B: WP-CLI
wp plugin install content-quicksand --activate
```

No configuration needed. The plugin activates automatically on single post views.

## Requirements
- WordPress 5.5+
- PHP 8.0+

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full release history.

## License

GPLv2. Built for [humanerd.kr](https://humanerd.kr).
