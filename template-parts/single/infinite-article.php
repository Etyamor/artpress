<?php
/**
 * Infinite Article — Partial for a single AJAX-loaded post.
 * Mirrors the markup in singular.php but without header/footer.
 */
?>
<div class="infinite-article" data-post-id="<?php echo esc_attr(get_the_ID()); ?>" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title() . ' - ' . get_bloginfo('name')); ?>">
    <hr class="max-w-6xl mx-auto border-border" />
    <main class="max-w-6xl mx-auto px-6 py-10">
        <div class="flex flex-col lg:flex-row lg:items-start gap-10">
            <article class="flex-1 min-w-0">
                <!-- Header -->
                <header class="mb-8">
                    <?php artpress_post_meta([
                        'class'        => 'flex items-center gap-2 text-sm text-muted mb-3',
                        'separator'    => 'middot',
                        'reading_time' => true,
                    ]); ?>
                    <h1 class="text-3xl font-bold text-foreground mb-4"><?php the_title(); ?></h1>
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="rounded-lg overflow-hidden mb-6">
                            <?php the_post_thumbnail('large', ['class' => 'w-full h-auto']); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Content -->
                <div class="prose prose-lg max-w-none">
                    <?php the_content(); ?>
                </div>

                <!-- Post footer -->
                <footer class="mt-8 pt-6 border-t border-border flex flex-col gap-4">
                    <?php
                    $general_opts = get_option('artpress_general', []);
                    if ($general_opts['share_buttons'] ?? true) {
                        get_template_part('template-parts/single/share-buttons');
                    }
                    ?>
                    <?php if (has_tag()) : ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-foreground-secondary"><?php esc_html_e('Tags:', 'artpress'); ?></span>
                            <?php the_tags('', '', ''); ?>
                        </div>
                    <?php endif; ?>
                </footer>
            </article>

            <!-- Sidebar -->
            <?php get_template_part('template-parts/sidebar'); ?>
        </div>
    </main>

    <?php get_template_part('template-parts/single/related-posts'); ?>
</div>
