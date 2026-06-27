<?php
defined('ABSPATH') || exit;

require_once CQ_DIR . '/includes/class-linker.php';

// Row action on post list
add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type !== 'post' || $post->post_status !== 'publish') return $actions;
    $url = wp_nonce_url(
        admin_url('admin-post.php?action=cq_link&post_id=' . $post->ID),
        'cq_link_' . $post->ID
    );
    $actions['cq_quicksand'] = '<a href="' . esc_url($url) . '" style="color:#8b7355">Quicksand</a>';
    return $actions;
}, 10, 2);

// Handle row action
add_action('admin_post_cq_link', function () {
    $post_id = (int) ($_GET['post_id'] ?? 0);
    $post = get_post($post_id);
    if (!$post || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cq_link_' . $post_id)) {
        wp_die('Invalid request');
    }
    (new CQ_Linker($post))->inject_links();
    wp_safe_redirect(add_query_arg('cq_done', $post_id, wp_get_referer()));
    exit;
});

// Bulk action on post list
add_filter('bulk_actions-edit-post', function ($actions) {
    $actions['cq_quicksand_bulk'] = 'Run Quicksand';
    return $actions;
});

add_filter('handle_bulk_actions-edit-post', function ($redirect, $action, $post_ids) {
    if ($action !== 'cq_quicksand_bulk') return $redirect;
    $linked = 0;
    foreach ($post_ids as $id) {
        $post = get_post($id);
        if (!$post || $post->post_status !== 'publish') continue;
        (new CQ_Linker($post))->inject_links();
        $linked++;
    }
    return add_query_arg('cq_bulk_done', $linked, $redirect);
}, 10, 3);

// Admin notice: single
add_action('admin_notices', function () {
    if (!empty($_GET['cq_done'])) {
        $id = (int) $_GET['cq_done'];
        echo '<div class="notice notice-success is-dismissible"><p>Quicksand: <strong>' . esc_html(get_the_title($id)) . '</strong>에 링크를 추가했습니다.</p></div>';
    }
    if (!empty($_GET['cq_bulk_done'])) {
        $n = (int) $_GET['cq_bulk_done'];
        echo '<div class="notice notice-success is-dismissible"><p>Quicksand: <strong>' . $n . '개</strong> 게시글에 링크를 추가했습니다.</p></div>';
    }
});

// Submenu page: Run on all posts
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Content Quicksand',
        'Quicksand',
        'manage_options',
        'content-quicksand',
        function () {
            if (!empty($_GET['cq_run_all'])) {
                check_admin_referer('cq_run_all');
                $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 200]);
                $linked = 0;
                foreach ($posts as $p) {
                    (new CQ_Linker($p))->inject_links();
                    $linked++;
                }
                echo '<div class="notice notice-success"><p>Quicksand: ' . $linked . '개 게시글에 링크를 추가했습니다.</p></div>';
            }
            $url = wp_nonce_url(admin_url('tools.php?page=content-quicksand&cq_run_all=1'), 'cq_run_all');
            ?>
            <div class="wrap">
                <h1>Content Quicksand</h1>
                <p>모든 발행 게시글에 키워드 기반 내부 링크를 추가합니다.</p>
                <a href="<?php echo esc_url($url); ?>" class="button button-primary" style="background:#8b7355;border-color:#8b7355">전체 게시글에 Quicksand 실행</a>
                <p style="margin-top:20px;color:#8a8a86;font-size:13px">도구 → Quicksand 에서도 실행할 수 있습니다.</p>
            </div>
            <?php
        }
    );
});
