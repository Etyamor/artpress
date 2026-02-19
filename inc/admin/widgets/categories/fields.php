<?php
/**
 * ArtPress Categories Widget – Field Rendering
 */

function artpress_widgets_categories_fields($opts) {
    $category_limit = $opts['category_limit'] ?? '';

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
    </table>
    <?php
}
