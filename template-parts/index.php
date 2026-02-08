<?php get_header(); ?>

<main class="max-w-6xl mx-auto px-6 py-10">
    <h1 class="text-3xl font-bold text-foreground mb-8"><?php esc_html_e('Latest Posts', 'artpress'); ?></h1>
    <div class="flex flex-col lg:flex-row lg:items-start gap-10">
        <!-- Posts -->
        <div class="flex-1 min-w-0 flex flex-col gap-6">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <article class="bg-white border border-border rounded-lg overflow-hidden md:flex md:flex-row">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>" class="block aspect-4/3 overflow-hidden md:w-72 md:shrink-0">
                            <?php the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover']); ?>
                        </a>
                    <?php endif; ?>
                    <div class="p-5 flex-1 min-w-0">
                        <div class="flex items-center gap-2 text-sm text-muted mb-2">
                            <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                            <?php if (has_category()) : ?>
                                <span class="post-card-category"><?php the_category(', '); ?></span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-xl font-semibold text-foreground mb-2">
                            <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
                        </h2>
                        <div class="text-foreground-muted text-sm leading-relaxed mb-3 [&_p]:m-0">
                            <?php the_excerpt(); ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="text-sm font-medium text-accent no-underline hover:text-accent-hover transition-colors">
                            <?php esc_html_e('Read more', 'artpress'); ?><span class="sr-only">: <?php the_title(); ?></span> &rarr;
                        </a>
                    </div>
                </article>
            <?php endwhile;
                get_template_part('template-parts/pagination');
            else : ?>
                <p class="text-muted"><?php esc_html_e('No posts found.', 'artpress'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <?php get_template_part('template-parts/sidebar'); ?>
    </div>
</main>

<?php get_footer(); ?>
