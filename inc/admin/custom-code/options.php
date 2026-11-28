<?php
/**
 * ArtPress Custom Code Settings – Registration & Sanitization
 *
 * A list of independent snippets (analytics, quick CSS/JS fixes, tests), each
 * with its own location, type and on/off switch. Output: inc/custom-code.php.
 */

define('ARTPRESS_CUSTOM_CODE_MAX', 50);

add_action('admin_init', function () {
    register_setting('artpress_custom_code', 'artpress_custom_code', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_custom_code_sanitize',
    ]);
});

/**
 * Where a snippet can be printed.
 *
 * @return array<string, string> Slug => admin label.
 */
function artpress_custom_code_locations() {
    return [
        'head_start' => 'Start of <head>',
        'head_end'   => 'End of <head>',
        'body_start' => 'Start of <body>',
        'body_end'   => 'End of <body>',
    ];
}

/**
 * How a snippet's code is wrapped.
 *
 * @return array<string, string> Slug => admin label.
 */
function artpress_custom_code_types() {
    return [
        'html' => 'HTML (paste the full snippet, with its tags)',
        'js'   => 'JavaScript (wrapped in <script>)',
        'css'  => 'CSS (wrapped in <style>)',
    ];
}

/**
 * Saved snippets, normalized.
 *
 * @return array[] List of ['label', 'location', 'type', 'enabled', 'code'].
 */
function artpress_custom_code_snippets() {
    $saved    = get_option('artpress_custom_code', []);
    $snippets = is_array($saved) && isset($saved['snippets']) && is_array($saved['snippets']) ? $saved['snippets'] : [];

    return array_values(array_filter(array_map('artpress_custom_code_sanitize_snippet', $snippets)));
}

/**
 * Sanitize one snippet; null when it is empty or malformed.
 *
 * The code itself is stored verbatim: scripts are the point of this feature,
 * which is why saving is limited to users with unfiltered_html.
 *
 * @param mixed $row Raw snippet.
 * @return array|null
 */
function artpress_custom_code_sanitize_snippet($row) {
    if (!is_array($row)) {
        return null;
    }

    $label = isset($row['label']) && is_string($row['label']) ? sanitize_text_field($row['label']) : '';
    $code  = isset($row['code']) && is_string($row['code']) ? trim($row['code']) : '';

    if ('' === $code && '' === $label) {
        return null;
    }

    $location = isset($row['location']) && is_string($row['location']) ? $row['location'] : '';
    $type     = isset($row['type']) && is_string($row['type']) ? $row['type'] : '';

    return [
        'label'    => $label,
        'location' => array_key_exists($location, artpress_custom_code_locations()) ? $location : 'head_end',
        'type'     => array_key_exists($type, artpress_custom_code_types()) ? $type : 'html',
        'enabled'  => !empty($row['enabled']),
        'code'     => $code,
    ];
}

/**
 * Sanitize the Custom Code settings.
 *
 * Users without unfiltered_html (e.g. site admins on multisite) can't change
 * anything here: the saved value is kept as it was.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function artpress_custom_code_sanitize($input) {
    if (!current_user_can('unfiltered_html')) {
        add_settings_error('artpress_custom_code', 'artpress_custom_code_cap', 'You are not allowed to edit custom code. Nothing was changed.');

        $saved = get_option('artpress_custom_code', []);

        return is_array($saved) ? $saved : [];
    }

    $rows     = is_array($input) && isset($input['snippets']) && is_array($input['snippets']) ? $input['snippets'] : [];
    $snippets = array_values(array_filter(array_map('artpress_custom_code_sanitize_snippet', array_values($rows))));

    return ['snippets' => array_slice($snippets, 0, ARTPRESS_CUSTOM_CODE_MAX)];
}
