<nav id="site-nav" class="site-nav fixed inset-0 top-16 z-40 bg-white overflow-y-auto opacity-0 invisible transition-all duration-200 ease-in-out lg:static lg:opacity-100 lg:visible lg:overflow-visible lg:bg-transparent lg:transition-none" aria-label="<?php esc_attr_e('Primary Navigation', 'artpress'); ?>">
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
