<?php
defined('ABSPATH') || exit;

class CQ_Series {
    private $patterns = [
        '/Ep\.?\s*(\d+)/i',
        '/Episode\s*(\d+)/i',
        '/Part\s*(\d+)/i',
        '/\b(\d+)\s*부작\b/u',
    ];

    public function detect($post) {
        $title = $post->post_title;
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $title, $m)) {
                $name = $this->extract_series_name($title, $pattern);
                return ['name' => $name, 'episode' => (int) $m[1], 'pattern' => $pattern];
            }
        }
        return null;
    }

    private function extract_series_name($title, $pattern) {
        $parts = preg_split('/[—\-–]/', $title);
        if (count($parts) > 1) {
            $last = trim(end($parts));
            $name = trim(preg_replace($pattern, '', $last));
            $name = preg_replace('/[\(\)\[\]「」]/u', '', $name);
            $name = preg_replace('/\s+완결$|\s+完$|\s+end$/i', '', $name);
            return trim($name);
        }
        return trim(preg_replace($pattern, '', $title));
    }

    public function get_series_posts($post) {
        $info = $this->detect($post);
        if (!$info) return false;

        $all = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
        ]);

        $series = [];
        foreach ($all as $p) {
            $pi = $this->detect($p);
            // Loose match: if one contains the other or exact match
            if ($pi && ($pi['name'] === $info['name'] || strpos($pi['name'], $info['name']) !== false || strpos($info['name'], $pi['name']) !== false)) {
                $series[$pi['episode']] = $p;
            }
        }

        if (count($series) < 2) return false;
        ksort($series);
        return $series;
    }

    public function render_nav($post) {
        $series = $this->get_series_posts($post);
        if (!$series) return '';

        $eps = array_keys($series);
        $current_ep = null;
        $info = $this->detect($post);
        if ($info) $current_ep = $info['episode'];

        $prev = $next = null;
        $pos = array_search($current_ep, $eps);
        if ($pos !== false) {
            if ($pos > 0) $prev = $series[$eps[$pos - 1]];
            if ($pos < count($eps) - 1) $next = $series[$eps[$pos + 1]];
        }

        $series_name = $info['base'] ?? '';
        $out = '<div class="cq-series-nav">';
        $out .= $prev
            ? '<a href="' . get_permalink($prev) . '">← ' . esc_html(wp_trim_words(get_the_title($prev), 6)) . '</a>'
            : '<span></span>';
        $out .= '<span class="cq-series-title">' . esc_html($series_name) . ' <small>(' . $current_ep . '/' . count($eps) . ')</small></span>';
        $out .= $next
            ? '<a href="' . get_permalink($next) . '">' . esc_html(wp_trim_words(get_the_title($next), 6)) . ' →</a>'
            : '<span></span>';
        $out .= '</div>';
        return $out;
    }
}
