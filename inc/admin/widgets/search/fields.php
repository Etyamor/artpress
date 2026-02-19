<?php
/**
 * ArtPress Search Widget – Field Rendering
 */

function artpress_widgets_search_fields($opts) {
    $search_title   = !empty($opts['search_title']);
    $search_content = !empty($opts['search_content']);
    $search_slug    = !empty($opts['search_slug']);

    ?>
    <h2>Search Widget</h2>
    <table class="form-table">
        <tr>
            <th scope="row">Search by</th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text">Search by</legend>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_widgets[search_title]" value="1" <?php checked($search_title); ?>>
                        Title
                    </label>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_widgets[search_content]" value="1" <?php checked($search_content); ?>>
                        Content
                    </label>
                    <label style="display: block; margin-bottom: 4px;">
                        <input type="checkbox" name="artpress_widgets[search_slug]" value="1" <?php checked($search_slug); ?>>
                        Slug
                    </label>
                    <p class="description">If none selected, uses default WordPress search (title + content).</p>
                </fieldset>
            </td>
        </tr>
    </table>
    <?php
}
