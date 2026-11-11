<?php
/**
 * ArtPress Search Widget – Frontend Hook
 */

add_filter('posts_search', function ($search, $query) {
    if (!$query->is_search() || is_admin()) {
        return $search;
    }

    $opts = get_option('artpress_widgets', []);

    $columns = [];
    if (!empty($opts['search_title']))   $columns[] = 'post_title';
    if (!empty($opts['search_content'])) $columns[] = 'post_content';
    if (!empty($opts['search_slug']))    $columns[] = 'post_name';

    if (empty($columns)) {
        return $search;
    }

    global $wpdb;

    $term = $query->get('s');
    if (empty($term)) {
        return $search;
    }

    $like = '%' . $wpdb->esc_like($term) . '%';

    $clauses = [];
    foreach ($columns as $col) {
        // $col comes from the fixed whitelist above, never from user input.
        $clauses[] = $wpdb->prepare("{$wpdb->posts}.{$col} LIKE %s", $like); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    return ' AND (' . implode(' OR ', $clauses) . ') ';
}, 10, 2);
