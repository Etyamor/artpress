<?php
/**
 * Infinite Article — AJAX endpoint for loading next posts.
 */

add_action('wp_ajax_artpress_load_next_post', 'artpress_load_next_post');
add_action('wp_ajax_nopriv_artpress_load_next_post', 'artpress_load_next_post');

function artpress_load_next_post() {
    check_ajax_referer('artpress_infinite', 'nonce');

    $current_post_id = intval($_POST['current_post_id'] ?? 0);
    $excluded_ids    = array_map('intval', (array) ($_POST['excluded_ids'] ?? []));

    if (!$current_post_id) {
        wp_send_json_success(['empty' => true]);
    }

    $current_post = get_post($current_post_id);
    if (!$current_post) {
        wp_send_json_success(['empty' => true]);
    }

    $query = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 1,
        'post__not_in'        => $excluded_ids,
        'date_query'          => [
            [
                'before'    => $current_post->post_date,
                'inclusive' => false,
            ],
        ],
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
    ]);

    if (!$query->have_posts()) {
        wp_reset_postdata();
        wp_send_json_success(['empty' => true]);
    }

    $query->the_post();

    ob_start();
    get_template_part('template-parts/single/infinite-article');
    $html = ob_get_clean();

    $data = [
        'html'   => $html,
        'postId' => get_the_ID(),
        'title'  => get_the_title() . ' - ' . get_bloginfo('name'),
        'url'    => get_permalink(),
    ];

    wp_reset_postdata();
    wp_send_json_success($data);
}
