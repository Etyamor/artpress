<?php
/**
 * Theme Setup
 */

add_action('after_setup_theme', function () {
    load_theme_textdomain('artpress', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'primary'           => __('Primary Menu', 'artpress'),
        'footer'            => __('Footer Menu', 'artpress'),
        'footer_categories' => __('Footer Categories', 'artpress'),
        'footer_legal'      => __('Footer Legal', 'artpress'),
    ]);
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

/**
 * Count words in plain text, in any script.
 *
 * str_word_count() only knows ASCII letters, so Cyrillic (and other non-Latin)
 * text counted as almost zero words. A word here is a run of letters/digits;
 * apostrophes (', ’, ʼ) and hyphens inside it keep "п'ять" or "well-known" as
 * one word.
 *
 * @param string $text Plain text.
 */
function artpress_count_words(string $text): int {
    return (int) preg_match_all("/[\\p{L}\\p{N}][\\p{L}\\p{N}\\p{M}'\\x{2019}\\x{02BC}-]*/u", $text);
}

/**
 * Get estimated reading time for a post.
 *
 * Words / 200 per minute (filter `artpress_reading_time_wpm`), rounded up,
 * minimum 1 minute. Shortcodes, HTML and entities are stripped first.
 *
 * @param int|WP_Post|null $post_id Post; defaults to the current post.
 */
function artpress_reading_time($post_id = null) {
    $content = (string) get_post_field('post_content', $post_id);
    $text    = html_entity_decode(wp_strip_all_tags(strip_shortcodes($content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $wpm     = max(1, (int) apply_filters('artpress_reading_time_wpm', 200));
    $minutes = max(1, (int) ceil(artpress_count_words($text) / $wpm));

    return sprintf(
        /* translators: %d: reading time in minutes. */
        _n('%d min', '%d min', $minutes, 'artpress'),
        $minutes
    );
}
