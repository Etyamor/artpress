<?php
/**
 * Hero grid — 1 big post + 2 image posts + 6 list posts.
 *
 * Expects $args['query'] to be a WP_Query with posts.
 */
$hero_query = $args['query'];
$hero_total = $hero_query->post_count;

if ($hero_total === 0) {
    return;
}

$hero_shown = 0;
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

    <?php if ($hero_shown < $hero_total) : $hero_query->the_post(); $hero_shown++; ?>
        <?php get_template_part('template-parts/hero/big-post'); ?>
    <?php endif; ?>

    <?php if ($hero_shown < $hero_total) : $hero_query->the_post(); $hero_shown++; ?>
        <?php get_template_part('template-parts/hero/image-post'); ?>
    <?php endif; ?>

    <?php if ($hero_shown < $hero_total) : $hero_query->the_post(); $hero_shown++; ?>
        <?php get_template_part('template-parts/hero/image-post'); ?>
    <?php endif; ?>

    <!-- Bottom row: two columns of post lists -->
    <?php for ($col = 0; $col < 2; $col++) : ?>
        <div class="divide-y divide-border-subtle">
            <?php for ($i = 0; $i < 3 && $hero_shown < $hero_total; $i++) : $hero_query->the_post(); $hero_shown++; ?>
                <?php get_template_part('template-parts/hero/list-post', null, ['first' => $i === 0]); ?>
            <?php endfor; ?>
        </div>
    <?php endfor; ?>

</div>
<?php wp_reset_postdata(); ?>
