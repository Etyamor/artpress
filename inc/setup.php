<?php
/**
 * Theme Setup
 */

add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'primary'           => __('Primary Menu', 'artpress'),
        'footer'            => __('Footer Menu', 'artpress'),
        'footer_categories' => __('Footer Categories', 'artpress'),
        'footer_legal'      => __('Footer Legal', 'artpress'),
    ]);
});

// Add 'current-tag' class to the active tag in the tag cloud widget.
add_filter('wp_tag_cloud', function ($output) {
    if (!is_tag()) {
        return $output;
    }
    $current_tag = get_queried_object();
    if (!$current_tag) {
        return $output;
    }
    return str_replace(
        'tag-link-' . $current_tag->term_id . ' ',
        'tag-link-' . $current_tag->term_id . ' current-tag ',
        $output
    );
});

add_action('widgets_init', function () {
    register_sidebar([
        'name'          => __('Sidebar', 'artpress'),
        'id'            => 'sidebar-1',
        'before_widget' => '<div class="bg-white border border-border rounded-lg p-5">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="text-base font-semibold text-foreground mb-3">',
        'after_title'   => '</h3>',
    ]);
});

add_filter('excerpt_more', function () {
    return '&hellip;';
});
