<?php
/**
 * ArtPress Tag Cloud Widget – Frontend Hook
 */

add_filter('render_block', function ($html, $block) {
    if ($block['blockName'] !== 'core/tag-cloud') {
        return $html;
    }

    $opts    = get_option('artpress_widgets', []);
    $limit   = $opts['tag_cloud_limit'] ?? '';
    $orderby = $opts['tag_cloud_orderby'] ?? 'name';

    $args = [
        'orderby' => $orderby,
        'order'   => $orderby === 'count' ? 'DESC' : 'ASC',
    ];

    if ($limit !== '' && $limit >= 1) {
        $args['number'] = (int) $limit;
    }

    $tags = get_tags($args);

    if (empty($tags)) {
        return $html;
    }

    $current_tag = is_tag() ? get_queried_object() : null;
    $current_id  = ($current_tag instanceof WP_Term) ? $current_tag->term_id : 0;

    $links = '';
    foreach ($tags as $tag) {
        $url     = esc_url(get_tag_link($tag));
        $name    = esc_html($tag->name);
        $classes = 'tag-cloud-link tag-link-' . $tag->term_id;

        if ($tag->term_id === $current_id) {
            $classes .= ' current-tag';
        }

        $links .= '<a href="' . $url . '" class="' . $classes . '">' . $name . '</a>';
    }

    return '<p class="wp-block-tag-cloud">' . $links . '</p>';
}, 10, 2);
