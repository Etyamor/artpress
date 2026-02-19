<?php
/**
 * ArtPress Categories Widget – Frontend Hook
 */

add_filter('render_block', function ($html, $block) {
    if ($block['blockName'] !== 'core/categories') {
        return $html;
    }

    $opts  = get_option('artpress_widgets', []);
    $limit = $opts['category_limit'] ?? '';

    if ($limit === '' || $limit < 1) {
        return $html;
    }

    $cats = get_categories([
        'orderby' => 'name',
        'order'   => 'ASC',
        'number'  => (int) $limit,
    ]);

    if (empty($cats)) {
        return $html;
    }

    $items = '';
    foreach ($cats as $cat) {
        $url   = esc_url(get_category_link($cat));
        $name  = esc_html($cat->name);
        $count = $cat->count;

        if (!empty($block['attrs']['showPostCounts'])) {
            $items .= '<li class="cat-item cat-item-' . $cat->term_id . '"><a href="' . $url . '">' . $name . '</a> <span class="wp-block-categories__post-count">(' . $count . ')</span></li>';
        } else {
            $items .= '<li class="cat-item cat-item-' . $cat->term_id . '"><a href="' . $url . '">' . $name . '</a></li>';
        }
    }

    return '<ul class="wp-block-categories-list wp-block-categories">' . $items . '</ul>';
}, 10, 2);
