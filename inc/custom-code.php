<?php
/**
 * Custom code snippets from ArtPress → Custom Code (option
 * `artpress_custom_code`, see inc/admin/custom-code/options.php).
 *
 * Locations:
 * - head_start: `artpress_head_start`, fired in template-parts/header.php right
 *   after the charset/viewport meta — before anything wp_head() prints.
 * - head_end:   wp_head, last.
 * - body_start: wp_body_open, first.
 * - body_end:   wp_footer, last (after enqueued footer scripts).
 */

/**
 * Markup for one snippet.
 *
 * @param array $snippet Sanitized snippet.
 * @return string
 */
function artpress_custom_code_snippet_html(array $snippet) {
    $code = trim((string) ($snippet['code'] ?? ''));
    if ('' === $code) {
        return '';
    }

    $type = $snippet['type'] ?? 'html';

    if ('js' === $type && !preg_match('/^<script\b/i', $code)) {
        $code = "<script>\n" . $code . "\n</script>";
    } elseif ('css' === $type && !preg_match('/^<style\b/i', $code)) {
        $code = "<style>\n" . $code . "\n</style>";
    }

    // "--" would end the HTML comment early.
    $label = str_replace('--', '- -', (string) ($snippet['label'] ?? ''));
    $label = '' !== $label ? ': ' . $label : '';

    return "\n<!-- ArtPress custom code" . $label . " -->\n" . $code . "\n";
}

/**
 * Markup of all enabled snippets for a location.
 *
 * @param string $location One of the keys of artpress_custom_code_locations().
 * @return string
 */
function artpress_custom_code_for($location) {
    $snippets = array_filter(artpress_custom_code_snippets(), static function ($snippet) use ($location) {
        return $snippet['enabled'] && $snippet['location'] === $location;
    });

    /**
     * Filters the snippets printed at a location.
     *
     * @param array[] $snippets Enabled snippets for this location.
     * @param string  $location 'head_start', 'head_end', 'body_start' or 'body_end'.
     */
    $snippets = apply_filters('artpress_custom_code_snippets', array_values($snippets), $location);

    return implode('', array_map('artpress_custom_code_snippet_html', (array) $snippets));
}

/**
 * Print the snippets for a location.
 *
 * @param string $location Location slug.
 */
function artpress_custom_code_print($location) {
    // Code saved by a user with unfiltered_html; printing it raw is the feature.
    echo artpress_custom_code_for($location); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see above.
}

function artpress_custom_code_head_start() {
    artpress_custom_code_print('head_start');
}

function artpress_custom_code_head_end() {
    artpress_custom_code_print('head_end');
}

function artpress_custom_code_body_start() {
    artpress_custom_code_print('body_start');
}

function artpress_custom_code_body_end() {
    artpress_custom_code_print('body_end');
}

add_action('artpress_head_start', 'artpress_custom_code_head_start');
add_action('wp_head', 'artpress_custom_code_head_end', PHP_INT_MAX);
add_action('wp_body_open', 'artpress_custom_code_body_start', 1);
add_action('wp_footer', 'artpress_custom_code_body_end', PHP_INT_MAX);
