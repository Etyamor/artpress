<?php
/**
 * ArtPress General Settings – Registration & Sanitization
 */

add_action('admin_init', function () {
    register_setting('artpress_general', 'artpress_general', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_general_sanitize',
    ]);
});

function artpress_general_sanitize($input) {
    $clean = [];

    $clean['subscribe_section'] = !empty($input['subscribe_section']);
    $clean['share_buttons']     = !empty($input['share_buttons']);
    $clean['share_facebook']    = !empty($input['share_facebook']);
    $clean['share_x']           = !empty($input['share_x']);
    $clean['share_linkedin']    = !empty($input['share_linkedin']);

    return $clean;
}
