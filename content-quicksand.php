<?php
/**
 * Plugin Name: Content Quicksand
 * Description: Series-aware related posts with multi-dimensional scoring. Keeps readers on your site.
 * Version: 0.1.0
 * Author: humanerd
 * Text Domain: content-quicksand
 */

defined('ABSPATH') || exit;

define('CQ_VERSION', '0.1.0');
define('CQ_DIR', __DIR__);

require_once CQ_DIR . '/includes/class-scorer.php';
require_once CQ_DIR . '/includes/class-renderer.php';
require_once CQ_DIR . '/includes/class-series.php';
require_once CQ_DIR . '/includes/class-linker.php';

// Post footer: series nav + related posts
add_action('wp', function () {
    if (!is_singular('post')) return;
    $cq = new ContentQuicksand();
    $cq->init();
});

// WP-CLI: wp quicksand [--all] [--ids=123,456]
if (defined('WP_CLI') && WP_CLI) {
    require_once CQ_DIR . '/includes/class-cli.php';
}

// Admin: row action + bulk action on post list
require_once CQ_DIR . '/includes/class-admin.php';

// On publish: inject contextual links immediately
function cq_inject_links_on_publish($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') return;
    $linker = new CQ_Linker($post);
    $linker->inject_links();
}
add_action('publish_post', 'cq_inject_links_on_publish', 10, 1);

class ContentQuicksand {
    private $scorer;
    private $renderer;
    private $series;
    private $post;

    public function init() {
        $this->post = get_queried_object();
        $this->series = new CQ_Series();
        $this->scorer = new CQ_Scorer($this->post, $this->series);
        $this->renderer = new CQ_Renderer($this->post, $this->scorer, $this->series);

        add_filter('the_content', [$this, 'append_quicksand'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue() {
        wp_add_inline_style('generate-style', '
.cq-quicksand { margin-top: 48px; padding-top: 32px; border-top: 1px solid #e8e8e4; }
.cq-quicksand h2 { font-size: 20px; margin-bottom: 20px; }
.cq-series-nav { display: flex; justify-content: space-between; margin-bottom: 28px; padding: 16px 20px; background: #f5f4f0; border-radius: 8px; }
.cq-series-nav a { font-size: 14px; font-weight: 500; color: #8b7355; text-decoration: none; }
.cq-series-nav a:hover { text-decoration: underline; }
.cq-series-nav .cq-series-title { font-size: 13px; color: #8a8a86; text-align: center; }
.cq-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
.cq-card { padding: 16px; background: #fafaf8; border-radius: 8px; transition: background 0.2s; }
.cq-card:hover { background: #f0efea; }
.cq-card .cq-card-cat { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #8b7355; margin-bottom: 4px; }
.cq-card .cq-card-title { font-size: 14px; font-weight: 600; line-height: 1.4; color: #1c1c1a; text-decoration: none; }
.cq-card .cq-card-title:hover { color: #8b7355; }
.cq-card .cq-card-meta { font-size: 12px; color: #8a8a86; margin-top: 4px; }
@media (max-width: 600px) {
  .cq-grid { grid-template-columns: 1fr; }
  .cq-series-nav { flex-direction: column; gap: 8px; text-align: center; }
}
        ');
    }

    public function append_quicksand($content) {
        if (!is_singular('post') || !in_the_loop()) return $content;

        $series_nav = $this->series->render_nav($this->post);
        $related = $this->renderer->render_related();

        if (!$series_nav && !$related) return $content;

        return $content . '<div class="cq-quicksand">' . $series_nav . $related . '</div>';
    }
}
