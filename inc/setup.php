<?php
/**
 * Theme Setup
 */

add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary'           => __('Primary Menu', 'artpress'),
        'footer'            => __('Footer Menu', 'artpress'),
        'footer_categories' => __('Footer Categories', 'artpress'),
        'footer_legal'      => __('Footer Legal', 'artpress'),
    ]);
});