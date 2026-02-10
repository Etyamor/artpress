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
    <!-- Big post (left, 2 cols wide, full height) -->
    <article class="bg-white border border-border rounded-lg overflow-hidden md:col-span-2 md:row-span-2 flex flex-col">
        <a href="<?php the_permalink(); ?>" class="<?php echo has_post_thumbnail() ? 'block' : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500'; ?> aspect-16/9 lg:aspect-auto lg:flex-1 overflow-hidden">
            <?php if (has_post_thumbnail()) :
                the_post_thumbnail('large', ['class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105']);
            endif; ?>
        </a>
        <div class="p-6">
            <div class="flex items-center gap-2 text-sm text-muted mb-3">
                <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                <?php $cats = get_the_category(); if (!empty($cats)) : ?>
                    <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="text-accent hover:text-accent-hover transition-colors py-1"><?php echo esc_html($cats[0]->name); ?></a>
                <?php endif; ?>
            </div>
            <h2 class="text-2xl font-semibold text-foreground lg:line-clamp-2">
                <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
            </h2>
        </div>
    </article>

    <?php $hero_query->the_post(); ?>
    <!-- Middle top: image post -->
    <article class="bg-white border border-border rounded-lg overflow-hidden">
        <a href="<?php the_permalink(); ?>" class="<?php echo has_post_thumbnail() ? 'block' : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500'; ?> aspect-16/9 overflow-hidden">
            <?php if (has_post_thumbnail()) :
                the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105']);
            endif; ?>
        </a>
        <div class="p-4">
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                <?php $cats = get_the_category(); if (!empty($cats)) : ?>
                    <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="text-accent hover:text-accent-hover transition-colors py-1"><?php echo esc_html($cats[0]->name); ?></a>
                <?php endif; ?>
            </div>
            <h2 class="text-base font-semibold text-foreground lg:line-clamp-2">
                <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
            </h2>
        </div>
    </article>

    <?php $hero_query->the_post(); ?>
    <!-- Right top: image post -->
    <article class="bg-white border border-border rounded-lg overflow-hidden">
        <a href="<?php the_permalink(); ?>" class="<?php echo has_post_thumbnail() ? 'block' : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500'; ?> aspect-16/9 overflow-hidden">
            <?php if (has_post_thumbnail()) :
                the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105']);
            endif; ?>
        </a>
        <div class="p-4">
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                <?php $cats = get_the_category(); if (!empty($cats)) : ?>
                    <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="text-accent hover:text-accent-hover transition-colors py-1"><?php echo esc_html($cats[0]->name); ?></a>
                <?php endif; ?>
            </div>
            <h2 class="text-base font-semibold text-foreground lg:line-clamp-2">
                <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
            </h2>
        </div>
    </article>

    <!-- Bottom row: two columns of post lists -->
    <?php for ($col = 0; $col < 2; $col++) : ?>
        <div class="divide-y divide-border-subtle">
            <?php for ($i = 0; $i < 3 && $hero_query->have_posts(); $i++) : $hero_query->the_post(); ?>
                <article class="py-3 <?php echo $i === 0 ? 'pt-0' : ''; ?>">
                    <div class="flex items-center gap-2 text-xs text-muted mb-1">
                        <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
                        <?php $cats = get_the_category(); if (!empty($cats)) : ?>
                            <a href="<?php echo esc_url(get_category_link($cats[0]->term_id)); ?>" class="text-accent hover:text-accent-hover transition-colors py-1"><?php echo esc_html($cats[0]->name); ?></a>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-sm font-semibold text-foreground leading-snug lg:line-clamp-2">
                        <a href="<?php the_permalink(); ?>" class="no-underline text-foreground hover:text-accent transition-colors"><?php the_title(); ?></a>
                    </h2>
                </article>
            <?php endfor; ?>
        </div>
    <?php endfor; ?>

</div>
<?php wp_reset_postdata(); ?>
