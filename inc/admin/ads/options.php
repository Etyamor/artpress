<?php
/**
 * ArtPress Ads Settings – Registration & Sanitization
 *
 * Ad slots between post cards on the home page, archives and search results.
 * The slot content is free-form: an Advanced Ads shortcode (e.g.
 * [the_ad_placement id="..."]), an AdSense snippet, or any HTML/JS.
 */

add_action('admin_init', function () {
    register_setting('artpress_ads', 'artpress_ads', [
        'type'              => 'array',
        'sanitize_callback' => 'artpress_ads_sanitize',
    ]);
});

/**
 * Page types that can show ads between posts, keyed by slug.
 *
 * @return array<string, string> Slug => admin label.
 */
function artpress_ads_contexts() {
    return [
        'home'     => 'Home page (incl. paginated pages)',
        'category' => 'Category archives',
        'tag'      => 'Tag archives',
        'archive'  => 'Other archives (author, date)',
        'search'   => 'Search results',
    ];
}

/**
 * Defaults used when the option has never been saved.
 *
 * @return array
 */
function artpress_ads_defaults() {
    return [
        'enabled'  => false,
        'contexts' => ['home', 'category', 'tag'],
        'every'    => 4,
        'max'      => 3,
        'code'     => '',
    ];
}

/**
 * Saved options merged over the defaults.
 *
 * @return array
 */
function artpress_ads_options() {
    $saved = get_option('artpress_ads', []);

    return array_merge(artpress_ads_defaults(), is_array($saved) ? $saved : []);
}

/**
 * Sanitize the Ads settings.
 *
 * The ad code is kept verbatim only for users who may post unfiltered HTML
 * (administrators on single sites) — scripts are the whole point of an ad
 * snippet. Everyone else gets it run through wp_kses_post(), like the Custom
 * HTML widget does.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function artpress_ads_sanitize($input) {
    $input    = is_array($input) ? $input : [];
    $defaults = artpress_ads_defaults();
    $clean    = [];

    $clean['enabled'] = !empty($input['enabled']);

    $contexts          = isset($input['contexts']) && is_array($input['contexts']) ? $input['contexts'] : [];
    $contexts          = array_map('strval', array_filter($contexts, 'is_scalar'));
    $clean['contexts'] = array_values(array_intersect(array_keys(artpress_ads_contexts()), $contexts));

    $clean['every'] = isset($input['every']) ? max(1, min(20, intval($input['every']))) : $defaults['every'];
    $clean['max']   = isset($input['max']) ? max(1, min(10, intval($input['max']))) : $defaults['max'];

    $code          = isset($input['code']) && is_string($input['code']) ? trim($input['code']) : '';
    $clean['code'] = current_user_can('unfiltered_html') ? $code : wp_kses_post($code);

    return $clean;
}
