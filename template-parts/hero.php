<?php
/**
 * Hero grid — 1 big post + 2 image posts + 6 list posts.
 *
 * Expects $args['query'] to be a WP_Query with posts.
 */
$hero_query = $args['query'];

if (! $hero_query->have_posts()) {
    return;
}
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

    <?php $hero_query->the_post(); ?>
    <?php get_template_part('template-parts/hero/big-post'); ?>

    <?php $hero_query->the_post(); ?>
    <?php get_template_part('template-parts/hero/image-post'); ?>

    <?php $hero_query->the_post(); ?>
    <?php get_template_part('template-parts/hero/image-post'); ?>

    <!-- Bottom row: two columns of post lists -->
    <?php for ($col = 0; $col < 2; $col++) : ?>
        <div class="divide-y divide-border-subtle">
            <?php for ($i = 0; $i < 3 && $hero_query->have_posts(); $i++) : $hero_query->the_post(); ?>
                <?php get_template_part('template-parts/hero/list-post', null, ['first' => $i === 0]); ?>
            <?php endfor; ?>
        </div>
    <?php endfor; ?>

</div>
<?php wp_reset_postdata(); ?>
