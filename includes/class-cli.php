<?php
defined('ABSPATH') || exit;

WP_CLI::add_command('quicksand', function ($args, $assoc) {
    require_once CQ_DIR . '/includes/class-linker.php';

    $ids = [];
    if (!empty($assoc['ids'])) {
        $ids = array_map('intval', explode(',', $assoc['ids']));
    } elseif (!empty($assoc['all'])) {
        $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 200]);
        $ids = wp_list_pluck($posts, 'ID');
    } elseif (!empty($args)) {
        $ids = array_map('intval', $args);
    } else {
        WP_CLI::error('Usage: wp quicksand [--all] [--ids=123,456] [--dry-run]');
    }

    $dry = !empty($assoc['dry-run']);
    $linked = 0;

    foreach ($ids as $id) {
        $post = get_post($id);
        if (!$post || $post->post_status !== 'publish') {
            WP_CLI::warning("Post $id not found or not published");
            continue;
        }

        $before = get_post_field('post_content', $id);
        (new CQ_Linker($post))->inject_links();
        $after = get_post_field('post_content', $id);

        if ($before !== $after) {
            $linked++;
            $action = $dry ? '[dry-run] would link' : 'linked';
            WP_CLI::line("  $action post $id: " . get_the_title($id));
        }
    }

    WP_CLI::success("$linked / " . count($ids) . " posts linked");
});
