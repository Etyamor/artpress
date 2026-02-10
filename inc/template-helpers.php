<?php
/**
 * Reusable template helpers for post meta and thumbnails.
 */

/**
 * Render post meta: date + first category link + optional reading time.
 *
 * @param array $args {
 *     @type string $class        Wrapper div classes.  Default 'flex items-center gap-2 text-sm text-muted mb-2'.
 *     @type string $separator    'none' (gap only) or 'middot' (&middot; before category/reading time). Default 'none'.
 *     @type bool   $reading_time Show reading time.  Default false.
 * }
 */
function artpress_post_meta( $args = [] ) {
    $args = wp_parse_args( $args, [
        'class'        => 'flex items-center gap-2 text-sm text-muted mb-2',
        'separator'    => 'none',
        'reading_time' => false,
    ] );

    $middot = $args['separator'] === 'middot';
    $cats   = get_the_category();
    ?>
    <div class="<?php echo esc_attr( $args['class'] ); ?>">
        <time datetime="<?php echo get_the_date( 'c' ); ?>"><?php echo get_the_date(); ?></time>
        <?php if ( ! empty( $cats ) ) : ?>
            <?php if ( $middot ) : ?><span>&middot;</span><?php endif; ?>
            <a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>" class="text-accent hover:text-accent-hover transition-colors"><?php echo esc_html( $cats[0]->name ); ?></a>
        <?php endif; ?>
        <?php if ( $args['reading_time'] ) : ?>
            <?php if ( $middot ) : ?><span>&middot;</span><?php endif; ?>
            <span class="inline-flex items-center gap-1"><i class="fa-solid fa-clock text-xs"></i> <?php echo esc_html( artpress_reading_time() ); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render a linked post thumbnail with gradient fallback.
 *
 * @param array $args {
 *     @type string $size        WP image size.  Default 'medium_large'.
 *     @type string $aspect      Aspect-ratio class(es).  Default 'aspect-16/9'.
 *     @type string $extra_class Additional classes on the <a>.  Default ''.
 *     @type bool   $lazy        Add loading="lazy".  Default false.
 * }
 */
function artpress_post_thumbnail( $args = [] ) {
    $args = wp_parse_args( $args, [
        'size'        => 'medium_large',
        'aspect'      => 'aspect-16/9',
        'extra_class' => '',
        'lazy'        => false,
    ] );

    $has_thumb  = has_post_thumbnail();
    $base_class = $has_thumb
        ? 'block'
        : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500';

    $classes = trim( $base_class . ' ' . $args['aspect'] . ' overflow-hidden ' . $args['extra_class'] );

    $img_attrs = [ 'class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105' ];
    if ( $args['lazy'] ) {
        $img_attrs['loading'] = 'lazy';
    }
    ?>
    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>" class="<?php echo esc_attr( $classes ); ?>">
        <?php if ( $has_thumb ) :
            the_post_thumbnail( $args['size'], $img_attrs );
        endif; ?>
    </a>
    <?php
}
