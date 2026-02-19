<?php
/**
 * ArtPress SMTP Settings – Registration & Sanitization
 */

add_action('admin_init', function () {
    register_setting('artpress_smtp', 'artpress_smtp', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_smtp_sanitize',
    ]);
});

function artpress_smtp_sanitize($input) {
    $clean = [];

    $clean['enabled']    = !empty($input['enabled']);
    $clean['host']       = sanitize_text_field($input['host'] ?? '');
    $clean['port']       = absint($input['port'] ?? 587);
    $clean['encryption'] = in_array($input['encryption'] ?? '', ['none', 'ssl', 'tls'], true)
        ? $input['encryption']
        : 'tls';
    $clean['auth']       = !empty($input['auth']);
    $clean['username']   = sanitize_text_field($input['username'] ?? '');
    $clean['password']   = $input['password'] ?? '';
    $clean['from_email'] = sanitize_email($input['from_email'] ?? '');
    $clean['from_name']  = sanitize_text_field($input['from_name'] ?? '');
    $clean['reply_to']   = sanitize_email($input['reply_to'] ?? '');

    if ($clean['port'] < 1 || $clean['port'] > 65535) {
        $clean['port'] = 587;
    }

    return $clean;
}
