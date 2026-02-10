<article class="bg-white border border-border rounded-lg overflow-hidden md:col-span-2 md:row-span-2 flex flex-col">
    <?php artpress_post_thumbnail([
        'size'        => 'large',
        'aspect'      => 'aspect-16/9 lg:aspect-auto',
        'extra_class' => 'lg:flex-1',
    ]); ?>
    <div class="p-6">
        <?php artpress_post_meta(['class' => 'flex items-center gap-2 text-sm text-muted mb-3']); ?>
        <h2 class="text-2xl font-semibold text-foreground lg:line-clamp-2">
            <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
        </h2>
    </div>
</article>
