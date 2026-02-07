<?php
/**
 * Newsletter Subscribe Handler
 *
 * Sends a notification email to the site admin when someone subscribes.
 */

add_action('wp_ajax_artpress_subscribe', 'artpress_handle_subscribe');
add_action('wp_ajax_nopriv_artpress_subscribe', 'artpress_handle_subscribe');

function artpress_handle_subscribe() {
    if (!check_ajax_referer('artpress_subscribe', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'artpress')], 403);
    }

    $email = sanitize_email($_POST['email'] ?? '');

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Please enter a valid email address.', 'artpress')]);
    }

    $to      = get_option('admin_email');
    $subject = sprintf(__('[%s] New newsletter subscriber', 'artpress'), get_bloginfo('name'));
    $body    = sprintf(__("New subscriber: %s\nDate: %s", 'artpress'), $email, current_time('mysql'));

    $sent = wp_mail($to, $subject, $body);

    if ($sent) {
        wp_send_json_success(['message' => __('Thank you for subscribing!', 'artpress')]);
    }

    wp_send_json_error(['message' => __('Something went wrong. Please try again.', 'artpress')]);
}
