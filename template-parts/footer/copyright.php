<div class="border-t border-surface-dark-border">
    <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-on-dark-muted">
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'artpress'); ?></p>
        <nav aria-label="<?php esc_attr_e('Legal', 'artpress'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'footer_legal',
                'container'      => false,
                'menu_class'     => 'footer-legal-menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]);
            ?>
        </nav>
    </div>
</div>
