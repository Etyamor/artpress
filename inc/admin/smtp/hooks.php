<?php
/**
 * ArtPress SMTP – PHPMailer Hook
 */

add_action('phpmailer_init', function ($phpmailer) {
    $opts = get_option('artpress_smtp', []);

    if (empty($opts['enabled'])) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $opts['host'] ?? '';
    $phpmailer->Port       = $opts['port'] ?? 587;
    $phpmailer->SMTPSecure = ($opts['encryption'] ?? 'none') === 'none' ? '' : $opts['encryption'];
    $phpmailer->SMTPAuth   = !empty($opts['auth']);

    if ($phpmailer->SMTPAuth) {
        $phpmailer->Username = $opts['username'] ?? '';
        $phpmailer->Password = $opts['password'] ?? '';
    }

    if (!empty($opts['from_email'])) {
        $phpmailer->From = $opts['from_email'];
    }

    if (!empty($opts['from_name'])) {
        $phpmailer->FromName = $opts['from_name'];
    }

    if (!empty($opts['reply_to'])) {
        $phpmailer->clearReplyTos();
        $phpmailer->addReplyTo($opts['reply_to']);
    }
});
