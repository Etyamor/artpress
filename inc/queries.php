<?php
/**
 * Query helpers for the index template.
 */

define('ARTPRESS_HERO_COUNT', 9);

/**
 * Get hero section query (latest N posts).
 */
function artpress_get_hero_query() {
    return new WP_Query([
        'posts_per_page'      => ARTPRESS_HERO_COUNT,
        'ignore_sticky_posts' => true,
    ]);
}

/**
 * Replace the global wp_query with an offset query for home pages.
 *
 * When using WP_Query with `offset`, the `paged` parameter is ignored for SQL
 * but still stored in query_vars — which is what `the_posts_pagination()` reads
 * to highlight the current page. We calculate offset manually:
 *   offset = hero_count + (current_page - 1) * posts_per_page
 *
 * `found_posts` from WP_Query with offset returns ALL matching posts (offset
 * doesn't affect the COUNT). We subtract hero_count so pagination reflects
 * only the remaining posts.
 *
 * @return WP_Query The original global query (caller must restore it later).
 */
function artpress_setup_home_query() {
    $original_query = $GLOBALS['wp_query'];

    $posts_per_page = get_option('posts_per_page');
    $current_page   = max(1, get_query_var('paged', 1));
    $offset         = ARTPRESS_HERO_COUNT + ($current_page - 1) * $posts_per_page;

    $GLOBALS['wp_query'] = new WP_Query([
        'posts_per_page'      => $posts_per_page,
        'offset'              => $offset,
        'paged'               => $current_page,
        'ignore_sticky_posts' => true,
    ]);

    $GLOBALS['wp_query']->found_posts   = max(0, $GLOBALS['wp_query']->found_posts - ARTPRESS_HERO_COUNT);
    $GLOBALS['wp_query']->max_num_pages = ceil($GLOBALS['wp_query']->found_posts / $posts_per_page);

    return $original_query;
}

/**
 * Restore the original global query after the home loop.
 */
function artpress_restore_query($original_query) {
    $GLOBALS['wp_query'] = $original_query;
    wp_reset_postdata();
}
