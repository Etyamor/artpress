<?php get_header(); ?>

<main class="max-w-6xl mx-auto px-6 py-10">
    <?php
    $is_home = is_home();
    $h1_class = $is_home ? 'sr-only' : 'text-3xl font-bold text-foreground mb-8';

    $paged = get_query_var('paged', 0);
    $page_suffix = $paged > 1 ? sprintf(esc_html__(' — Page %d', 'artpress'), $paged) : '';
    ?>
    <h1 class="<?php echo $h1_class; ?>"><?php
        if (is_search()) {
            printf(esc_html__('Search results for: %s', 'artpress'), '<span class="text-accent">' . esc_html(get_search_query()) . '</span>');
            echo esc_html($page_suffix);
        } elseif (is_category()) {
            single_cat_title();
            echo esc_html($page_suffix);
        } elseif (is_tag()) {
            single_tag_title();
            echo esc_html($page_suffix);
        } elseif (is_author()) {
            the_author();
            echo esc_html($page_suffix);
        } elseif (is_archive()) {
            the_archive_title();
            echo esc_html($page_suffix);
        } else {
            esc_html_e('Latest Posts', 'artpress');
            echo esc_html($page_suffix);
        }
    ?></h1>

    <?php
    // Hero section — only on home page 1
    if ($is_home && !is_paged()) :
        get_template_part('template-parts/hero', null, [
            'query' => artpress_get_hero_query(),
        ]);
    endif;

    // Main posts — custom offset query for home, default query for archives
    if ($is_home) :
        $original_query = artpress_setup_home_query();
    endif;

    if (have_posts()) : ?>
        <div class="flex flex-col lg:flex-row lg:items-start gap-10">
            <!-- Posts -->
            <div class="flex-1 min-w-0">
                <?php if ($is_home) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php else : ?>
                    <div class="flex flex-col gap-6">
                <?php endif; ?>

                <?php while (have_posts()) : the_post(); ?>
                    <article class="bg-white border border-border rounded-lg overflow-hidden<?php echo !$is_home ? ' md:flex md:flex-row' : ''; ?>">
                        <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>" class="<?php echo has_post_thumbnail() ? 'block' : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500'; ?> aspect-<?php echo $is_home ? '16/9' : '4/3'; ?> overflow-hidden<?php echo !$is_home ? ' md:w-72 md:shrink-0' : ''; ?>">
                            <?php if (has_post_thumbnail()) :
                                the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105', 'loading' => 'lazy']);
                            endif; ?>
                        </a>
                        <div class="p-5 flex-1 min-w-0">
                            <div class="flex items-center gap-2 text-sm text-muted mb-2">
                                <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                                <?php if (has_category()) : ?>
                                    <span class="post-card-category"><?php the_category(', '); ?></span>
                                <?php endif; ?>
                                <span>&middot;</span>
                                <span><?php echo esc_html(artpress_reading_time()); ?></span>
                            </div>
                            <h2 class="text-lg font-semibold text-foreground mb-2 line-clamp-2">
                                <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
                            </h2>
                            <div class="text-foreground-muted text-sm leading-relaxed mb-3 line-clamp-3 [&_p]:m-0">
                                <?php the_excerpt(); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="text-sm font-medium text-accent no-underline hover:text-accent-hover transition-colors">
                                <?php esc_html_e('Read more', 'artpress'); ?><span class="sr-only">: <?php the_title(); ?></span> &rarr;
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>

                </div>
                <?php get_template_part('template-parts/pagination'); ?>
            </div>

            <!-- Sidebar -->
            <?php get_template_part('template-parts/sidebar'); ?>
        </div>

    <?php else : ?>
        <p class="text-muted"><?php esc_html_e('No posts found.', 'artpress'); ?></p>
    <?php endif;

    if ($is_home) :
        artpress_restore_query($original_query);
    endif;
    ?>
</main>

<?php get_footer(); ?>
