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
