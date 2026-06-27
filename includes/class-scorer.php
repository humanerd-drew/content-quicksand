<?php
defined('ABSPATH') || exit;

class CQ_Scorer {
    private $post;
    private $series;
    private $cge_data = null;

    public function __construct($post, $series) {
        $this->post = $post;
        $this->series = $series;

        $cge_file = WP_CONTENT_DIR . '/mu-plugins/../../P4-cortex/content/content-graph.json';
        if (file_exists($cge_file)) {
            $this->cge_data = json_decode(file_get_contents($cge_file), true);
        }
    }

    public function get_related($limit = 6) {
        $candidates = $this->load_candidates();
        $scored = [];

        foreach ($candidates as $c) {
            if ($c->ID === $this->post->ID) continue;
            $score = $this->score($c);
            if ($score > 0) {
                $scored[$c->ID] = ['post' => $c, 'score' => $score];
            }
        }

        uasort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scored, 0, $limit);
    }

    private function load_candidates() {
        $cats = wp_get_post_categories($this->post->ID);
        return get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 30,
            'category__in' => $cats,
            'exclude' => [$this->post->ID],
        ]);
    }

    private function score($candidate) {
        $score = 0;

        // Series match: +60
        $si = $this->series->detect($this->post);
        $ci = $this->series->detect($candidate);
        if ($si && $ci && $si['base'] === $ci['base']) {
            $score += 60;
        }

        // Same category: +20
        $shared = array_intersect(
            wp_get_post_categories($this->post->ID),
            wp_get_post_categories($candidate->ID)
        );
        $score += count($shared) * 10;

        // CGE similarity (if available): +0~30
        $cge_score = $this->get_cge_score($candidate->ID);
        $score += $cge_score;

        // Recency: +0~10
        $days_old = max(1, (time() - get_post_time('U', false, $candidate)) / DAY_IN_SECONDS);
        $recency = max(0, 10 - log($days_old, 2));
        $score += $recency;

        // Tag overlap (if any tags): +0~10
        $shared_tags = array_intersect(
            wp_get_post_tags($this->post->ID, ['fields' => 'ids']),
            wp_get_post_tags($candidate->ID, ['fields' => 'ids'])
        );
        $score += count($shared_tags) * 5;

        return $score;
    }

    private function get_cge_score($target_id) {
        if (!$this->cge_data) return 0;
        $posts = $this->cge_data['posts'] ?? [];
        $source_id = (string) $this->post->ID;
        $target_id = (string) $target_id;

        $matrix = $this->cge_data['matrix'] ?? [];
        if (isset($matrix[$source_id][$target_id])) {
            return (float) $matrix[$source_id][$target_id] * 30;
        }
        return 0;
    }
}
