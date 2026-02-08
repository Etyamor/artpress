<?php
/**
 * ArtPress Settings Page
 */

require_once get_template_directory() . '/inc/admin/general.php';
require_once get_template_directory() . '/inc/admin/smtp.php';

add_action('admin_menu', function () {
    add_menu_page(
        'ArtPress Settings',
        'ArtPress',
        'manage_options',
        'artpress-settings',
        'artpress_settings_page',
        'dashicons-art',
        59
    );
});

function artpress_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $tabs = [
        'general' => 'General',
        'smtp'    => 'SMTP',
    ];

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

    if (!array_key_exists($active_tab, $tabs)) {
        $active_tab = 'general';
    }

    ?>
    <div class="wrap">
        <h1>ArtPress Settings</h1>

        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $slug => $label) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $slug, admin_url('admin.php?page=artpress-settings'))); ?>"
                   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <?php if ($active_tab === 'general') : ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('artpress_general');
                artpress_general_render_fields();
                submit_button();
                ?>
            </form>
        <?php endif; ?>

        <?php if ($active_tab === 'smtp') : ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('artpress_smtp');
                artpress_smtp_render_fields();
                submit_button();
                ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
