<?php
/**
 * Ad plugin compatibility.
 */

/**
 * Put back the space Advanced Ads drops between <ins and its attributes.
 *
 * Advanced Ads 2.0.29 builds AdSense units as '<ins' . HTML::build_attributes()
 * and build_attributes() returns no leading space, so every unit renders as
 * <insclass="adsbygoogle" ...>. Browsers parse that as an unknown <insclass>
 * element, which means the page has no AdSense slot at all: nothing can be
 * filled, and every adsbygoogle.push() raises "All 'ins' elements in the DOM
 * with class=adsbygoogle already have ads in them".
 *
 * Only a tag whose attribute name is glued straight onto the tag name is
 * touched, so correct markup and the plain <ins> element are left alone.
 *
 * Drop this once the plugin ships a fix.
 *
 * @see modules/gadsense/types/class-ad-adsense.php in the plugin.
 *
 * @param string $output Ad markup.
 * @return string
 */
function artpress_fix_adsense_ins_tag($output) {
    if (!is_string($output) || strpos($output, '<ins') === false) {
        return $output;
    }

    return preg_replace('/<ins(?=[a-zA-Z][a-zA-Z0-9-]*=)/', '<ins ', $output);
}
add_filter('advanced-ads-ad-output', 'artpress_fix_adsense_ins_tag', 20);

/**
 * Feed ads.txt the records the settings screen actually saved.
 *
 * Advanced Ads 2.0.29 saves the "Additional records" textarea to the `custom`
 * key of the `ads-txt` store (option advanced_ads_ads_txt), but its frontend
 * reads `ads-txt.content` from the `main` store, which is never written. The
 * value is therefore always empty, get_frontend_output() returns nothing, and
 * /ads.txt falls through to a 404 no matter what is entered.
 *
 * Records already present are left alone, so this keeps working — and stays
 * harmless — once the plugin reads the right key again.
 *
 * @see modules/ads-txt/class-settings.php and modules/ads-txt/class-frontend.php in the plugin.
 *
 * @param string $content ads.txt content.
 * @return string
 */
function artpress_fix_ads_txt_content($content) {
    if (!function_exists('wp_advads_get_setting')) {
        return $content;
    }

    $custom  = wp_advads_get_setting('custom', 'ads-txt');
    $content = is_string($content) ? $content : '';

    if (!is_string($custom) || '' === trim($custom) || false !== strpos($content, trim($custom))) {
        return $content;
    }

    return '' === trim($content) ? $custom : $custom . "\n" . $content;
}
add_filter('advanced-ads-ads-txt-content', 'artpress_fix_ads_txt_content', 20);
