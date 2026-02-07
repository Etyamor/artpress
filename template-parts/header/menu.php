<nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e('Primary Navigation', 'artpress'); ?>">
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'depth'          => 2,
        'container'      => false,
        'menu_class'     => 'header-menu',
        'fallback_cb'    => false,
    ]);
    ?>
</nav>
