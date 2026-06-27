<?php
defined('ABSPATH') || exit;

class CQ_Renderer {
    private $post;
    private $scorer;
    private $series;

    public function __construct($post, $scorer, $series) {
        $this->post = $post;
        $this->scorer = $scorer;
        $this->series = $series;
    }

    public function render_related() {
        $related = $this->scorer->get_related(6);
        if (empty($related)) return '';

        $out = '<h2>함께 읽으면 좋은 글</h2>';
        $out .= '<div class="cq-grid">';

        foreach ($related as $r) {
            $p = $r['post'];
            $cats = wp_get_post_categories($p->ID, ['fields' => 'names']);
            $cat_name = $cats[0] ?? '';
            $date = get_the_date(get_option('date_format'), $p->ID);

            $out .= '<div class="cq-card">';
            if ($cat_name) {
                $out .= '<div class="cq-card-cat">' . esc_html($cat_name) . '</div>';
            }
            $out .= '<a class="cq-card-title" href="' . get_permalink($p) . '">'
                  . esc_html(get_the_title($p)) . '</a>';
            $out .= '<div class="cq-card-meta">' . $date . '</div>';
            $out .= '</div>';
        }

        $out .= '</div>';
        return $out;
    }
}
