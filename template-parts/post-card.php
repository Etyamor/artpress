<?php
/**
 * Post card — used in both the home grid and archive list.
 *
 * Expects $args['is_home'] to toggle between grid card and horizontal list layout.
 */
$is_home = $args['is_home'] ?? true;
?>
<article class="bg-white border border-border rounded-lg overflow-hidden<?php echo !$is_home ? ' md:flex md:flex-row' : ''; ?>">
    <?php artpress_post_thumbnail([
        'aspect'      => $is_home ? 'aspect-16/9' : 'aspect-4/3',
        'extra_class' => !$is_home ? 'md:w-72 md:shrink-0' : '',
        'lazy'        => true,
    ]); ?>
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
