<?php
/**
 * Expects $args['first'] — whether this is the first item in the column.
 */
$is_first = $args['first'] ?? false;
?>
<article class="py-3 <?php echo $is_first ? 'pt-0' : ''; ?>">
    <?php artpress_post_meta(['class' => 'flex items-center gap-2 text-sm text-muted mb-1']); ?>
    <h2 class="text-sm font-semibold text-foreground leading-snug lg:line-clamp-2">
        <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
    </h2>
</article>
