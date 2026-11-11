<?php
/**
 * Per-category archive layout.
 *
 * Each category can pick how its archive page lists posts: list or grid,
 * with or without thumbnails. The choice is stored as term meta and edited
 * on the category add/edit screens.
 */

define('ARTPRESS_LAYOUT_META', 'artpress_layout');
define('ARTPRESS_LAYOUT_DEFAULT', 'list');

/**
 * Available layouts, slug => label.
 *
 * @return array<string, string>
 */
function artpress_category_layouts() {
    return [
        'list'      => __('List with images', 'artpress'),
        'grid'      => __('Grid with images', 'artpress'),
        'list-text' => __('List without images', 'artpress'),
        'grid-text' => __('Grid without images', 'artpress'),
    ];
}

/**
 * Whitelist a layout slug, falling back to the default.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function artpress_sanitize_category_layout($value) {
    return is_string($value) && array_key_exists($value, artpress_category_layouts())
        ? $value
        : ARTPRESS_LAYOUT_DEFAULT;
}

/**
 * Layout chosen for a category.
 *
 * @param int $term_id Category ID.
 * @return string
 */
function artpress_get_category_layout($term_id) {
    return artpress_sanitize_category_layout(get_term_meta((int) $term_id, ARTPRESS_LAYOUT_META, true));
}

/**
 * Layout for the archive currently being displayed.
 *
 * Only category archives are configurable; every other archive (tags,
 * search, author, date) keeps the default list.
 *
 * @return string
 */
function artpress_current_archive_layout() {
    $layout = ARTPRESS_LAYOUT_DEFAULT;

    if (is_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $layout = artpress_get_category_layout($term->term_id);
        }
    }

    /**
     * Filter the layout used for the current archive page.
     *
     * @param string $layout One of the keys of artpress_category_layouts().
     */
    return artpress_sanitize_category_layout(apply_filters('artpress_archive_layout', $layout));
}

/**
 * Whether a layout shows post thumbnails.
 *
 * @param string $layout Layout slug.
 * @return bool
 */
function artpress_layout_has_images($layout) {
    return '-text' !== substr($layout, -5);
}

/**
 * Whether a layout is a grid.
 *
 * @param string $layout Layout slug.
 * @return bool
 */
function artpress_layout_is_grid($layout) {
    return 0 === strpos($layout, 'grid');
}

/**
 * Classes for the element wrapping the archive's post cards.
 *
 * @param string $layout Layout slug.
 * @return string
 */
function artpress_layout_wrapper_class($layout) {
    switch (artpress_sanitize_category_layout($layout)) {
        case 'grid':
        case 'grid-text':
            return 'grid grid-cols-1 md:grid-cols-2 gap-6';
        case 'list-text':
            return 'flex flex-col bg-white border border-border rounded-lg px-5 divide-y divide-border';
        default:
            return 'flex flex-col gap-6';
    }
}

add_action('init', function () {
    register_term_meta('category', ARTPRESS_LAYOUT_META, [
        'type'              => 'string',
        'single'            => true,
        'default'           => ARTPRESS_LAYOUT_DEFAULT,
        'sanitize_callback' => 'artpress_sanitize_category_layout',
        'show_in_rest'      => [
            'schema' => [
                'type' => 'string',
                'enum' => ['list', 'grid', 'list-text', 'grid-text'],
            ],
        ],
    ]);
});

/**
 * <select> + nonce shared by the add and edit forms.
 *
 * @param string $current Selected layout.
 */
function artpress_category_layout_select($current) {
    wp_nonce_field('artpress_category_layout', 'artpress_category_layout_nonce');
    ?>
    <select name="artpress_layout" id="artpress_layout">
        <?php foreach (artpress_category_layouts() as $slug => $label) : ?>
            <option value="<?php echo esc_attr($slug); ?>" <?php selected($current, $slug); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e('How posts are displayed on this category\'s archive page.', 'artpress'); ?></p>
    <?php
}

add_action('category_add_form_fields', function () {
    ?>
    <div class="form-field term-artpress-layout-wrap">
        <label for="artpress_layout"><?php esc_html_e('Posts layout', 'artpress'); ?></label>
        <?php artpress_category_layout_select(ARTPRESS_LAYOUT_DEFAULT); ?>
    </div>
    <?php
});

add_action('category_edit_form_fields', function ($term) {
    ?>
    <tr class="form-field term-artpress-layout-wrap">
        <th scope="row"><label for="artpress_layout"><?php esc_html_e('Posts layout', 'artpress'); ?></label></th>
        <td><?php artpress_category_layout_select(artpress_get_category_layout($term->term_id)); ?></td>
    </tr>
    <?php
});

/**
 * Save the layout from the category add/edit form.
 *
 * The default layout is stored as "no meta" so untouched categories don't
 * accumulate rows.
 *
 * @param int $term_id Category ID.
 */
function artpress_save_category_layout($term_id) {
    if (!isset($_POST['artpress_layout'], $_POST['artpress_category_layout_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['artpress_category_layout_nonce'])), 'artpress_category_layout')) {
        return;
    }

    if (!current_user_can('edit_term', $term_id)) {
        return;
    }

    $layout = artpress_sanitize_category_layout(sanitize_key(wp_unslash($_POST['artpress_layout'])));

    if (ARTPRESS_LAYOUT_DEFAULT === $layout) {
        delete_term_meta($term_id, ARTPRESS_LAYOUT_META);
    } else {
        update_term_meta($term_id, ARTPRESS_LAYOUT_META, $layout);
    }
}
add_action('created_category', 'artpress_save_category_layout');
add_action('edited_category', 'artpress_save_category_layout');

/**
 * "Layout" column on the Posts → Categories screen.
 */
add_filter('manage_edit-category_columns', function ($columns) {
    $columns['artpress_layout'] = __('Layout', 'artpress');

    return $columns;
});

add_filter('manage_category_custom_column', function ($content, $column, $term_id) {
    if ('artpress_layout' !== $column) {
        return $content;
    }

    $layouts = artpress_category_layouts();

    return esc_html($layouts[artpress_get_category_layout($term_id)]);
}, 10, 3);
