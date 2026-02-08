<?php
/**
 * WordPress Cleanup
 */

add_action('after_setup_theme', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    add_action('wp_enqueue_scripts', function () {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('dashicons');
        wp_dequeue_style('wp-block-library-theme');

        wp_dequeue_script('jquery');
        wp_dequeue_script('jquery-migrate');
        wp_dequeue_script('wp-embed');
    }, 100);

    add_filter('should_load_separate_core_block_assets', '__return_false', 100);
});
