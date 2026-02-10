<?php get_header(); ?>

<div id="reading-progress" class="fixed top-0 left-0 h-1 bg-accent z-50 transition-[width] duration-150 ease-out" style="width: 0%"></div>

<main class="max-w-6xl mx-auto px-6 py-10">
    <div class="flex flex-col lg:flex-row lg:items-start gap-10">
        <article class="flex-1 min-w-0">
            <?php while (have_posts()) : the_post(); ?>

                <!-- Header -->
                <header class="mb-8">
                    <div class="flex items-center gap-2 text-sm text-muted mb-3">
                        <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                        <?php $cats = get_the_category(); if (!empty($cats)) : ?>
                            <span>&middot;</span>
                            <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="text-accent hover:text-accent-hover transition-colors"><?php echo esc_html($cats[0]->name); ?></a>
                        <?php endif; ?>
                        <span>&middot;</span>
                        <span class="inline-flex items-center gap-1"><i class="fa-solid fa-clock text-xs"></i> <?php echo esc_html(artpress_reading_time()); ?></span>
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

            <?php endwhile; ?>
        </article>

        <!-- Sidebar -->
        <?php get_template_part('template-parts/sidebar'); ?>
    </div>
</main>

<?php get_footer(); ?>
