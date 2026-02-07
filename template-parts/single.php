<?php get_header(); ?>

<main class="max-w-6xl mx-auto px-6 py-10">
    <div class="flex flex-col lg:flex-row lg:items-start gap-10">
        <article class="flex-1 min-w-0">
            <?php while (have_posts()) : the_post(); ?>

                <!-- Header -->
                <header class="mb-8">
                    <div class="flex items-center gap-2 text-sm text-muted mb-3">
                        <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                        <?php if (has_category()) : ?>
                            <span>&middot;</span>
                            <span class="post-card-category"><?php the_category(', '); ?></span>
                        <?php endif; ?>
                    </div>
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

                <!-- Tags -->
                <?php if (has_tag()) : ?>
                    <footer class="mt-8 pt-6 border-t border-border">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-foreground-secondary"><?php esc_html_e('Tags:', 'artpress'); ?></span>
                            <?php the_tags('', '', ''); ?>
                        </div>
                    </footer>
                <?php endif; ?>

            <?php endwhile; ?>
        </article>

        <!-- Sidebar -->
        <?php get_template_part('template-parts/sidebar'); ?>
    </div>
</main>

<?php get_footer(); ?>
