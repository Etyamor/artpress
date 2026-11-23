<?php
/**
 * Ad slots between post cards on listing pages (home, archives, search).
 *
 * Configured under ArtPress → Ads (option `artpress_ads`, see
 * inc/admin/ads/options.php). template-parts/index.php calls
 * artpress_archive_ad() after every card.
 */

/**
 * Which ad context the current request is, or '' when it has none.
 *
 * @return string One of the keys of artpress_ads_contexts(), or ''.
 */
function artpress_archive_ad_context() {
    if (is_home()) {
        $context = 'home';
    } elseif (is_category()) {
        $context = 'category';
    } elseif (is_tag()) {
        $context = 'tag';
    } elseif (is_search()) {
        $context = 'search';
    } elseif (is_archive()) {
        $context = 'archive';
    } else {
        $context = '';
    }

    /**
     * Filters the ad context of the current listing page.
     *
     * @param string $context 'home', 'category', 'tag', 'search', 'archive' or ''.
     */
    return (string) apply_filters('artpress_archive_ad_context', $context);
}

/**
 * Slot number to insert after the given card, or 0 for none.
 *
 * @param int   $position Cards output so far on this page (1-based).
 * @param int   $total    Cards on this page.
 * @param array $opts     Ads options (artpress_ads_options()).
 * @return int 1-based slot number, or 0.
 */
function artpress_archive_ad_slot($position, $total, $opts) {
    $every = max(1, (int) ($opts['every'] ?? 4));
    $max   = max(1, (int) ($opts['max'] ?? 3));

    // Never after the last card: an ad right above the pagination is noise.
    if ($position < 1 || $position >= $total || 0 !== $position % $every) {
        return 0;
    }

    $slot = intdiv($position, $every);

    return $slot <= $max ? $slot : 0;
}

/**
 * Ad slot markup to place after a card, or '' when there is none.
 *
 * @param int    $position Cards output so far on this page (1-based).
 * @param int    $total    Cards on this page.
 * @param string $layout   Archive layout (see artpress_category_layouts()).
 * @return string
 */
function artpress_get_archive_ad($position, $total, $layout = 'grid') {
    $opts    = artpress_ads_options();
    $context = artpress_archive_ad_context();

    if (empty($opts['enabled']) || '' === $context || !in_array($context, (array) $opts['contexts'], true)) {
        return '';
    }

    $slot = artpress_archive_ad_slot((int) $position, (int) $total, $opts);
    if (!$slot) {
        return '';
    }

    /**
     * Filters the ad code of one slot before shortcodes run.
     *
     * Use it to serve a different ad per slot or per context.
     *
     * @param string $code    Code from ArtPress → Ads.
     * @param int    $slot    1-based slot number on this page.
     * @param string $context Ad context (see artpress_archive_ad_context()).
     */
    $code = (string) apply_filters('artpress_archive_ad_code', (string) $opts['code'], $slot, $context);
    $code = trim(do_shortcode($code));

    // Advanced Ads returns nothing when its conditions hide the ad: no empty box.
    if ('' === $code) {
        return '';
    }

    // Grid layouts: span the whole row. list-text sits in a divided list with
    // vertical padding on each row.
    $class = 'artpress-ad col-span-full flex justify-center overflow-hidden';
    if ('list-text' === $layout) {
        $class .= ' py-5';
    }

    return sprintf(
        '<div class="%1$s" data-artpress-ad-slot="%2$d">%3$s</div>',
        esc_attr($class),
        (int) $slot,
        $code
    );
}

/**
 * Print the ad slot that belongs after the current card of the main loop.
 *
 * @param string $layout Archive layout.
 */
function artpress_archive_ad($layout = 'grid') {
    global $wp_query;

    if (!isset($wp_query->current_post, $wp_query->post_count)) {
        return;
    }

    // Ad code set by an administrator (unfiltered_html) or kses-filtered on save.
    echo artpress_get_archive_ad($wp_query->current_post + 1, $wp_query->post_count, $layout); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see above.
}
