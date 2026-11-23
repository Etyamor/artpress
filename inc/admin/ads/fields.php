<?php
/**
 * ArtPress Ads Settings – Field Rendering
 */

function artpress_ads_render_fields() {
    $opts = artpress_ads_options();

    ?>
    <p>
        Insert an ad slot between post cards on listing pages. Works with any ad code.
        With <strong>Advanced Ads</strong>: create a placement of type <em>Manual Placement</em>,
        assign an ad or group to it and paste its shortcode (e.g. <code>[the_ad_placement id="archive-feed"]</code>) below.
        Ads, groups, rotation, visitor conditions and consent are then managed in Advanced Ads.
    </p>
    <table class="form-table">
        <tr>
            <th scope="row">Ads Between Posts</th>
            <td>
                <label>
                    <input type="checkbox" name="artpress_ads[enabled]" value="1" <?php checked($opts['enabled']); ?>>
                    Show ad slots between posts
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Show On</th>
            <td>
                <fieldset>
                    <?php foreach (artpress_ads_contexts() as $slug => $label) : ?>
                        <label style="display: block; margin-bottom: 4px;">
                            <input type="checkbox" name="artpress_ads[contexts][]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, (array) $opts['contexts'], true)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row">Position</th>
            <td>
                <label style="display: block; margin-bottom: 8px;">
                    After every
                    <input type="number" name="artpress_ads[every]" value="<?php echo esc_attr($opts['every']); ?>" min="1" max="20" style="width: 60px;">
                    posts
                </label>
                <label style="display: block;">
                    At most
                    <input type="number" name="artpress_ads[max]" value="<?php echo esc_attr($opts['max']); ?>" min="1" max="10" style="width: 60px;">
                    ad slots per page
                </label>
                <p class="description">No slot is added after the last post of a page.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress-ads-code">Ad Code</label></th>
            <td>
                <textarea id="artpress-ads-code" name="artpress_ads[code]" rows="8" class="large-text code"><?php echo esc_textarea($opts['code']); ?></textarea>
                <p class="description">
                    Shortcodes, HTML and scripts are allowed. The same code is output in every slot.
                    <?php if (!current_user_can('unfiltered_html')) : ?>
                        <br><strong>Your account cannot save scripts; they will be stripped.</strong>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
