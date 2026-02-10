<article class="bg-white border border-border rounded-lg overflow-hidden">
    <?php artpress_post_thumbnail(); ?>
    <div class="p-4">
        <?php artpress_post_meta(['class' => 'flex items-center gap-2 text-sm text-muted mb-1']); ?>
        <h2 class="text-base font-semibold text-foreground lg:line-clamp-2">
            <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
        </h2>
    </div>
</article>
