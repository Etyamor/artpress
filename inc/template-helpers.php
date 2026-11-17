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
 *     @type bool   $author       Show the author byline.  Default false.
 *     @type bool   $updated      Show "Updated <date>" when the post was edited
 *                                well after publishing.  Default false.
 * }
 */
function artpress_post_meta( $args = [] ) {
    $args = wp_parse_args( $args, [
        'class'        => 'flex items-center gap-2 text-sm text-muted mb-2',
        'separator'    => 'none',
        'reading_time' => false,
        'author'       => false,
        'updated'      => false,
    ] );

    $middot = $args['separator'] === 'middot';
    $cats   = get_the_category();
    ?>
    <div class="<?php echo esc_attr( $args['class'] ); ?>">
        <?php if ( $args['author'] ) : ?>
            <?php artpress_post_author(); ?>
            <?php if ( $middot ) : ?><span>&middot;</span><?php endif; ?>
        <?php endif; ?>
        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
        <?php if ( $args['updated'] && artpress_show_updated_date( (int) get_the_time( 'U' ), (int) get_the_modified_time( 'U' ) ) ) : ?>
            <?php if ( $middot ) : ?><span>&middot;</span><?php endif; ?>
            <span>
                <?php
                printf(
                    /* translators: %s: date the post was last updated. */
                    esc_html__( 'Updated %s', 'artpress' ),
                    '<time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . esc_html( get_the_modified_date() ) . '</time>'
                );
                ?>
            </span>
        <?php endif; ?>
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
 * Whether "Updated <date>" is worth showing: only for real edits made at least
 * a day (filter `artpress_updated_threshold`, seconds) after publishing, not
 * for quick fixes right after the post went live.
 *
 * @param int $published Publish timestamp.
 * @param int $modified  Last-modified timestamp.
 */
function artpress_show_updated_date( int $published, int $modified ): bool {
    $threshold = (int) apply_filters( 'artpress_updated_threshold', DAY_IN_SECONDS );

    return $modified - $published >= max( 0, $threshold );
}

/**
 * Whether author archive pages exist (Rank Math can turn them off, which makes
 * /author/<name>/ a 404 — then the byline isn't linked).
 */
function artpress_author_archives_enabled(): bool {
    $enabled = true;

    if ( class_exists( '\\RankMath\\Helper' ) && method_exists( '\\RankMath\\Helper', 'get_settings' ) ) {
        // Normalized to bool by Rank Math, but older versions return 'on'/'off'.
        $disabled = \RankMath\Helper::get_settings( 'titles.disable_author_archives' );
        $enabled  = ! ( true === $disabled || 'on' === $disabled );
    }

    return (bool) apply_filters( 'artpress_author_archives_enabled', $enabled );
}

/**
 * Author byline of the current post, linked to the author archive when it exists.
 */
function artpress_post_author(): void {
    $name = get_the_author();
    if ( '' === $name ) {
        return;
    }

    echo '<span class="text-foreground-secondary">';
    if ( artpress_author_archives_enabled() ) {
        printf(
            '<a href="%1$s" rel="author" class="text-foreground-secondary hover:text-accent transition-colors">%2$s</a>',
            esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
            esc_html( $name )
        );
    } else {
        echo esc_html( $name );
    }
    echo '</span>';
}

/**
 * Show the SEO plugin's breadcrumbs (Rank Math or Yoast) when it has them enabled.
 *
 * Both plugins also output BreadcrumbList schema, so the theme adds markup only.
 *
 * @param string $class Wrapper classes.
 */
function artpress_breadcrumbs( string $class = 'mb-3' ): void {
    ob_start();
    if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
        rank_math_the_breadcrumbs();
    } elseif ( function_exists( 'yoast_breadcrumb' ) ) {
        yoast_breadcrumb( '<nav class="yoast-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumbs', 'artpress' ) . '">', '</nav>' );
    }
    $html = trim( (string) ob_get_clean() );

    if ( '' !== $html ) {
        // Markup comes from the SEO plugin, which escapes it.
        echo '<div class="artpress-breadcrumbs ' . esc_attr( $class ) . '">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin-generated breadcrumb HTML.
    }
}

/**
 * Render a linked post thumbnail with gradient fallback.
 *
 * @param array $args {
 *     @type string $size        WP image size.  Default 'medium_large'.
 *     @type string $aspect      Aspect-ratio class(es).  Default 'aspect-16/9'.
 *     @type string $extra_class Additional classes on the <a>.  Default ''.
 *     @type bool   $lazy        Add loading="lazy".  Default false.
 *     @type bool   $priority    Likely the largest image in the first screen
 *                               (LCP): load eagerly with fetchpriority="high".
 *                               Overrides $lazy.  Default false.
 * }
 */
function artpress_post_thumbnail( $args = [] ) {
    $args = wp_parse_args( $args, [
        'size'        => 'medium_large',
        'aspect'      => 'aspect-16/9',
        'extra_class' => '',
        'lazy'        => false,
        'priority'    => false,
    ] );

    $has_thumb  = has_post_thumbnail();
    $base_class = $has_thumb
        ? 'block'
        : 'hidden md:block bg-gradient-to-br from-accent via-accent-deep to-accent bg-[length:200%_200%] hover:bg-[position:100%_100%] transition-[background-position] duration-500';

    $classes = trim( $base_class . ' ' . $args['aspect'] . ' overflow-hidden ' . $args['extra_class'] );

    $img_attrs = [ 'class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105' ];
    if ( $args['priority'] ) {
        $img_attrs['loading']       = false;
        $img_attrs['fetchpriority'] = 'high';
    } elseif ( $args['lazy'] ) {
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
