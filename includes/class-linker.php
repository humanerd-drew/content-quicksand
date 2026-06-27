<?php
defined('ABSPATH') || exit;

class CQ_Linker {
    private $post;

    public function __construct($post) {
        $this->post = $post;
    }

    public function inject_links() {
        static $guard = false;
        if ($guard) return;
        $guard = true;

        $targets = $this->find_linkable_posts();
        if (empty($targets)) { $guard = false; return; }

        $content = $this->post->post_content;

        foreach ($targets as $target) {
            $keyword = $target['keyword'];
            $permalink = get_permalink($target['id']);

            $idx = mb_stripos($content, $keyword);
            if ($idx === false) continue;

            $actual = mb_substr($content, $idx, mb_strlen($keyword));
            $link = ' <a href="' . esc_url($permalink) . '">' . esc_html($actual) . '</a> ';
            $content = substr_replace($content, $link, $idx, strlen($keyword));
            break;
        }

        remove_action('publish_post', 'cq_inject_links_on_publish');
        wp_update_post(['ID' => $this->post->ID, 'post_content' => $content]);
        add_action('publish_post', 'cq_inject_links_on_publish', 10, 1);
        $guard = false;
    }

    private function find_linkable_posts() {
        $stopwords = ['—', '–', '-', '·', '의', '에', '를', '이', '가', '은', '는', '과', '와', '도',
                      'the', 'a', 'an', 'and', 'or', 'for', 'that', 'this', 'with', 'from'];

        $keywords = [];
        $title = $this->post->post_title;
        $parts = preg_split('/[\s—–\-·,()"\x{201C}\x{201D}]/u', $title);
        foreach ($parts as $w) {
            $w = trim($w);
            if (mb_strlen($w) > 2 && !in_array($w, $stopwords)) {
                $keywords[] = $w;
            }
        }

        $cats = wp_get_post_categories($this->post->ID);
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'exclude' => [$this->post->ID],
        ];
        if (!empty($cats)) {
            $args['category__in'] = $cats;
        }
        $candidates = get_posts($args);

        $results = [];
        foreach ($candidates as $c) {
            $matched = null;
            foreach ($keywords as $kw) {
                if (mb_strlen($kw) < 3) continue;
                if (mb_stripos($c->post_title, $kw) !== false || mb_stripos($c->post_content, $kw) !== false) {
                    $matched = $kw;
                    break;
                }
            }
            if ($matched) {
                $results[] = ['id' => $c->ID, 'keyword' => $matched];
            }
            if (count($results) >= 3) break;
        }

        return $results;
    }
}
