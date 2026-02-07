<?php
/**
 * ArtPress SMTP Settings
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

    if ($clean['port'] < 1 || $clean['port'] > 65535) {
        $clean['port'] = 587;
    }

    return $clean;
}

function artpress_smtp_render_fields() {
    $opts = get_option('artpress_smtp', []);

    $enabled    = !empty($opts['enabled']);
    $host       = $opts['host'] ?? '';
    $port       = $opts['port'] ?? 587;
    $encryption = $opts['encryption'] ?? 'tls';
    $auth       = !empty($opts['auth']);
    $username   = $opts['username'] ?? '';
    $password   = $opts['password'] ?? '';
    $from_email = $opts['from_email'] ?? '';
    $from_name  = $opts['from_name'] ?? '';

    ?>
    <table class="form-table">
        <tr>
            <th scope="row">Enable SMTP</th>
            <td>
                <label>
                    <input type="checkbox" name="artpress_smtp[enabled]" value="1" <?php checked($enabled); ?>>
                    Route emails through SMTP server
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_host">SMTP Host</label></th>
            <td>
                <input type="text" id="artpress_smtp_host" name="artpress_smtp[host]"
                       value="<?php echo esc_attr($host); ?>" class="regular-text"
                       placeholder="smtp.gmail.com">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_port">Port</label></th>
            <td>
                <input type="number" id="artpress_smtp_port" name="artpress_smtp[port]"
                       value="<?php echo esc_attr($port); ?>" class="small-text"
                       min="1" max="65535">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_encryption">Encryption</label></th>
            <td>
                <select id="artpress_smtp_encryption" name="artpress_smtp[encryption]">
                    <option value="none" <?php selected($encryption, 'none'); ?>>None</option>
                    <option value="ssl" <?php selected($encryption, 'ssl'); ?>>SSL</option>
                    <option value="tls" <?php selected($encryption, 'tls'); ?>>TLS</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">Authentication</th>
            <td>
                <label>
                    <input type="checkbox" name="artpress_smtp[auth]" value="1" <?php checked($auth); ?>>
                    Enable SMTP authentication
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_username">Username</label></th>
            <td>
                <input type="text" id="artpress_smtp_username" name="artpress_smtp[username]"
                       value="<?php echo esc_attr($username); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_password">Password</label></th>
            <td>
                <input type="password" id="artpress_smtp_password" name="artpress_smtp[password]"
                       value="<?php echo esc_attr($password); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_from_email">From Email</label></th>
            <td>
                <input type="email" id="artpress_smtp_from_email" name="artpress_smtp[from_email]"
                       value="<?php echo esc_attr($from_email); ?>" class="regular-text"
                       placeholder="noreply@example.com">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_smtp_from_name">From Name</label></th>
            <td>
                <input type="text" id="artpress_smtp_from_name" name="artpress_smtp[from_name]"
                       value="<?php echo esc_attr($from_name); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

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
});
