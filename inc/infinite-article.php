<?php
/**
 * Infinite Article — next post lookup and front-end partial renderer.
 *
 * The next post is rendered through a regular front-end request
 * (permalink + ?artpress_infinite=1) instead of admin-ajax. That keeps the
 * full singular context intact — is_singular(), the main query, the loop,
 * template_redirect — so plugins that gate their output on it (Advanced Ads
 * and other ad plugins injecting into the_content, Yoast, etc.) behave
 * exactly as they do on a normal page view.
 */

define('ARTPRESS_INFINITE_QUERY_VAR', 'artpress_infinite');

/**
 * Whether the infinite article feature is turned on.
 */
function artpress_infinite_enabled() {
    $general_opts = get_option('artpress_general', []);

    return (bool) ($general_opts['infinite_article'] ?? false);
}

/**
 * Find the post that should load after the given one (the next older post).
 *
 * @param int|WP_Post|null $post Post to look after. Defaults to the current post.
 * @return int Post ID, or 0 when there is nothing left to load.
 */
function artpress_infinite_next_post_id($post = null) {
    $post = get_post($post);

    if (!$post || 'post' !== $post->post_type) {
        return 0;
    }

    static $cache = [];

    if (!isset($cache[$post->ID])) {
        $query = new WP_Query([
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => 1,
            'post__not_in'        => [$post->ID],
            'date_query'          => [
                [
                    'before'    => $post->post_date,
                    'inclusive' => false,
                ],
            ],
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'fields'              => 'ids',
        ]);

        $next_id = !empty($query->posts) ? (int) $query->posts[0] : 0;

        /**
         * Filter the post that infinite scroll loads next.
         *
         * @param int     $next_id ID of the next post, 0 when there is none.
         * @param WP_Post $post    Post currently being displayed.
         */
        $cache[$post->ID] = (int) apply_filters('artpress_infinite_next_post_id', $next_id, $post);
    }

    return $cache[$post->ID];
}

/**
 * Permalink of the next post to load, or an empty string when there is none.
 *
 * @param int|WP_Post|null $post Post to look after. Defaults to the current post.
 * @return string
 */
function artpress_infinite_next_url($post = null) {
    $next_id = artpress_infinite_next_post_id($post);

    if (!$next_id) {
        return '';
    }

    return (string) get_permalink($next_id);
}

/**
 * Register the request flag so WordPress keeps it through canonical redirects.
 */
add_filter('query_vars', function ($vars) {
    $vars[] = ARTPRESS_INFINITE_QUERY_VAR;

    return $vars;
});

/**
 * Render only the article partial when a post is requested with the flag.
 *
 * Runs at priority 20 so everything hooked to template_redirect earlier has
 * already run — Advanced Ads evaluates whether ads may be shown on this page
 * at priority 11.
 */
add_action('template_redirect', function () {
    if (!get_query_var(ARTPRESS_INFINITE_QUERY_VAR) || !is_singular('post') || !artpress_infinite_enabled()) {
        return;
    }

    // Never let a page cache store this fragment — a cache that ignores the
    // query argument would otherwise serve a headerless partial to everyone
    // asking for the post itself.
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    nocache_headers();
    header('Content-Type: text/html; charset=' . get_option('blog_charset'));
    header('X-Robots-Tag: noindex, nofollow', true);

    // A real loop over the main query: plugins rely on in_the_loop() and on the
    // loop_start / the_post hooks to decide whether to inject into the content.
    while (have_posts()) {
        the_post();
        get_template_part('template-parts/single/infinite-article');
    }

    exit;
}, 20);
