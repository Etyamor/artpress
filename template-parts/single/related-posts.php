<?php
/**
 * Related Posts — 3 posts from the same category, shown after the main content.
 */

if ( ! is_singular( 'post' ) ) {
    return;
}

$cats = get_the_category();
if ( empty( $cats ) ) {
    return;
}

$related = new WP_Query( [
    'category__in'        => [ $cats[0]->term_id ],
    'post__not_in'        => [ get_the_ID() ],
    'posts_per_page'      => 3,
    'ignore_sticky_posts' => true,
] );

if ( ! $related->have_posts() ) {
    wp_reset_postdata();
    return;
}
?>
<section class="max-w-6xl mx-auto px-6 pb-10">
    <h2 class="text-2xl font-bold text-foreground mb-6"><?php esc_html_e( 'Related Posts', 'artpress' ); ?></h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php while ( $related->have_posts() ) : $related->the_post(); ?>
            <article class="bg-white border border-border rounded-lg overflow-hidden">
                <?php artpress_post_thumbnail( [
                    'aspect' => 'aspect-16/9',
                    'lazy'   => true,
                ] ); ?>
                <div class="p-5">
                    <?php artpress_post_meta(); ?>
                    <h3 class="text-lg font-semibold text-foreground mb-2 line-clamp-2">
                        <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
                    </h3>
                    <div class="text-foreground-muted text-sm leading-relaxed line-clamp-3 [&_p]:m-0">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php wp_reset_postdata(); ?>
