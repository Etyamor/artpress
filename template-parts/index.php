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
                    <?php get_template_part('template-parts/post-card', null, ['is_home' => $is_home]); ?>
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
