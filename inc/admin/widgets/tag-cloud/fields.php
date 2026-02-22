<?php
/**
 * ArtPress Tag Cloud Widget – Field Rendering
 */

function artpress_widgets_tag_cloud_fields($opts) {
    $tag_cloud_limit   = $opts['tag_cloud_limit'] ?? '';
    $tag_cloud_orderby = $opts['tag_cloud_orderby'] ?? 'name';

    ?>
    <h2>Tag Cloud Widget</h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="artpress_widgets_tag_cloud_limit">Tag limit</label></th>
            <td>
                <input type="number" id="artpress_widgets_tag_cloud_limit"
                       name="artpress_widgets[tag_cloud_limit]"
                       value="<?php echo esc_attr($tag_cloud_limit); ?>"
                       class="small-text" min="1">
                <p class="description">Maximum number of tags to display. Leave empty for unlimited.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_widgets_tag_cloud_orderby">Sort by</label></th>
            <td>
                <select id="artpress_widgets_tag_cloud_orderby" name="artpress_widgets[tag_cloud_orderby]">
                    <option value="name" <?php selected($tag_cloud_orderby, 'name'); ?>>Name</option>
                    <option value="count" <?php selected($tag_cloud_orderby, 'count'); ?>>Post count</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
