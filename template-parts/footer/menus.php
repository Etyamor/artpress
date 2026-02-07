<div class="max-w-6xl mx-auto px-6 py-12">
    <div class="flex flex-col md:flex-row justify-between gap-10">
        <!-- Logo & description -->
        <div class="md:max-w-xs">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="text-2xl font-bold text-white">
                ArtPress
            </a>
            <p class="mt-3 text-on-dark-muted leading-relaxed">
                A blog about art, design, and creative inspiration. Exploring ideas that shape the visual world.
            </p>
        </div>

        <!-- Navigation -->
        <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer menu', 'artpress'); ?>">
            <h4 class="text-sm font-semibold text-on-dark-heading uppercase tracking-wider mb-4">Menu</h4>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'footer-menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]);
            ?>
        </nav>

        <!-- Categories -->
        <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer categories', 'artpress'); ?>">
            <h4 class="text-sm font-semibold text-on-dark-heading uppercase tracking-wider mb-4">Categories</h4>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer_categories',
                'container'      => false,
                'menu_class'     => 'footer-menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]);
            ?>
        </nav>
    </div>
</div>
