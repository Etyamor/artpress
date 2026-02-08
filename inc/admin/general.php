<?php
/**
 * ArtPress General Settings
 */

add_action('admin_init', function () {
    register_setting('artpress_general', 'artpress_general', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_general_sanitize',
    ]);
});

function artpress_general_sanitize($input) {
    $clean = [];

    $clean['share_buttons']  = !empty($input['share_buttons']);
    $clean['share_facebook'] = !empty($input['share_facebook']);
    $clean['share_x']        = !empty($input['share_x']);
    $clean['share_linkedin'] = !empty($input['share_linkedin']);

    return $clean;
}

function artpress_general_render_fields() {
    $opts = get_option('artpress_general', []);

    $share_buttons  = $opts['share_buttons'] ?? true;
    $share_facebook = $opts['share_facebook'] ?? true;
    $share_x        = $opts['share_x'] ?? true;
    $share_linkedin = $opts['share_linkedin'] ?? true;

    ?>
    <table class="form-table">
        <tr>
            <th scope="row">Share Buttons</th>
            <td>
                <label>
                    <input type="checkbox" name="artpress_general[share_buttons]" value="1" <?php checked($share_buttons); ?>>
                    Show social share buttons on single posts
                </label>
                <fieldset style="margin-top: 10px; padding-left: 24px;">
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_general[share_facebook]" value="1" <?php checked($share_facebook); ?>>
                        Facebook
                    </label>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_general[share_x]" value="1" <?php checked($share_x); ?>>
                        X (Twitter)
                    </label>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_general[share_linkedin]" value="1" <?php checked($share_linkedin); ?>>
                        LinkedIn
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>
    <?php
}
