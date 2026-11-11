<?php
/**
 * Post card — used by the home grid and every archive.
 *
 * Expects $args['layout']: one of the keys of artpress_category_layouts()
 * ('list', 'grid', 'list-text', 'grid-text'). Defaults to 'grid'.
 */
$layout     = artpress_sanitize_category_layout($args['layout'] ?? 'grid');
$has_images = artpress_layout_has_images($layout);
$is_grid    = artpress_layout_is_grid($layout);

if ('list-text' === $layout) :
    // Compact row inside a single bordered container (see artpress_layout_wrapper_class()).
    ?>
    <article class="py-5">
        <?php artpress_post_meta(['reading_time' => true]); ?>
        <h2 class="text-lg font-semibold text-foreground mb-2">
            <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
        </h2>
        <div class="text-foreground-muted text-sm leading-relaxed line-clamp-2 [&_p]:m-0">
            <?php the_excerpt(); ?>
        </div>
    </article>
    <?php
    return;
endif;

$card_class = 'bg-white border border-border rounded-lg overflow-hidden';
if ($has_images && !$is_grid) {
    $card_class .= ' md:flex md:flex-row';
}
?>
<article class="<?php echo esc_attr($card_class); ?>">
    <?php if ($has_images) :
        artpress_post_thumbnail([
            'aspect'      => $is_grid ? 'aspect-16/9' : 'aspect-4/3',
            'extra_class' => $is_grid ? '' : 'md:w-72 md:shrink-0',
            'lazy'        => true,
        ]);
    endif; ?>
    <div class="p-5 flex-1 min-w-0">
        <?php artpress_post_meta(['reading_time' => true]); ?>
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
