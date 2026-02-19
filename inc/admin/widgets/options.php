<?php
/**
 * ArtPress Widgets Settings – Menu, Registration & Sanitization
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'artpress-settings',
        'WordPress Widgets',
        'Widgets',
        'manage_options',
        'artpress-widgets',
        'artpress_widgets_page'
    );
}, 20);

add_action('admin_init', function () {
    register_setting('artpress_widgets', 'artpress_widgets', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_widgets_sanitize',
    ]);
});

function artpress_widgets_sanitize($input) {
    $clean = [];

    $limit = $input['category_limit'] ?? '';
    $clean['category_limit'] = $limit !== '' ? absint($limit) : '';

    $clean['category_orderby'] = in_array($input['category_orderby'] ?? '', ['name', 'count'], true)
        ? $input['category_orderby']
        : 'name';

    $clean['search_title']   = !empty($input['search_title']);
    $clean['search_content'] = !empty($input['search_content']);
    $clean['search_excerpt'] = !empty($input['search_excerpt']);
    $clean['search_slug']    = !empty($input['search_slug']);

    return $clean;
}
