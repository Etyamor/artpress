<?php
/**
 * ArtPress Widgets Settings – Page Rendering
 */

function artpress_widgets_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $opts = get_option('artpress_widgets', []);

    ?>
    <div class="wrap">
        <h1>WordPress Widgets</h1>

        <form method="post" action="options.php">
            <?php settings_fields('artpress_widgets'); ?>

            <?php artpress_widgets_categories_fields($opts); ?>

            <hr>

            <?php artpress_widgets_tag_cloud_fields($opts); ?>

            <hr>

            <?php artpress_widgets_search_fields($opts); ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
