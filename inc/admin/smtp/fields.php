<?php
/**
 * ArtPress SMTP Settings – Field Rendering
 */

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
    $reply_to   = $opts['reply_to'] ?? '';

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
        <tr>
            <th scope="row"><label for="artpress_smtp_reply_to">Reply-To</label></th>
            <td>
                <input type="email" id="artpress_smtp_reply_to" name="artpress_smtp[reply_to]"
                       value="<?php echo esc_attr($reply_to); ?>" class="regular-text"
                       placeholder="reply@example.com">
                <p class="description">Recipients will reply to this address instead of the From email.</p>
            </td>
        </tr>
    </table>
    <?php
}
