<?php
/**
 * ArtPress Categories Widget – Field Rendering
 */

function artpress_widgets_categories_fields($opts) {
    $category_limit   = $opts['category_limit'] ?? '';
    $category_orderby = $opts['category_orderby'] ?? 'name';

    ?>
    <h2>Categories Widget</h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="artpress_widgets_category_limit">Category limit</label></th>
            <td>
                <input type="number" id="artpress_widgets_category_limit"
                       name="artpress_widgets[category_limit]"
                       value="<?php echo esc_attr($category_limit); ?>"
                       class="small-text" min="1">
                <p class="description">Maximum number of categories to display. Leave empty for unlimited.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="artpress_widgets_category_orderby">Sort by</label></th>
            <td>
                <select id="artpress_widgets_category_orderby" name="artpress_widgets[category_orderby]">
                    <option value="name" <?php selected($category_orderby, 'name'); ?>>Name</option>
                    <option value="count" <?php selected($category_orderby, 'count'); ?>>Post count</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
