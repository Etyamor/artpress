<?php
/**
 * ArtPress Custom Code Settings – Field Rendering
 */

/**
 * Render one snippet row. `$index` is a number, or '__i__' for the JS template.
 *
 * @param int|string $index   Row index.
 * @param array      $snippet Snippet values.
 */
function artpress_custom_code_render_row($index, array $snippet) {
    $name = 'artpress_custom_code[snippets][' . $index . ']';
    $id   = 'artpress-code-' . $index;
    ?>
    <div class="artpress-snippet" style="background: #fff; border: 1px solid #c3c4c7; padding: 12px 16px; margin-bottom: 12px;">
        <p style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 0;">
            <label>
                <input type="checkbox" name="<?php echo esc_attr($name); ?>[enabled]" value="1" <?php checked($snippet['enabled']); ?>>
                Enabled
            </label>
            <input type="text" name="<?php echo esc_attr($name); ?>[label]" value="<?php echo esc_attr($snippet['label']); ?>" placeholder="Label, e.g. Google Analytics" class="regular-text">
            <select name="<?php echo esc_attr($name); ?>[location]" aria-label="Location">
                <?php foreach (artpress_custom_code_locations() as $slug => $label) : ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($snippet['location'], $slug); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="<?php echo esc_attr($name); ?>[type]" aria-label="Type">
                <?php foreach (artpress_custom_code_types() as $slug => $label) : ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($snippet['type'], $slug); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button-link button-link-delete artpress-snippet-remove" style="margin-left: auto;">Remove</button>
        </p>
        <label class="screen-reader-text" for="<?php echo esc_attr($id); ?>">Code</label>
        <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>[code]" rows="8" class="large-text code" spellcheck="false"><?php echo esc_textarea($snippet['code']); ?></textarea>
    </div>
    <?php
}

function artpress_custom_code_render_fields() {
    $snippets = artpress_custom_code_snippets();
    $empty    = [
        'label'    => '',
        'location' => 'head_end',
        'type'     => 'html',
        'enabled'  => true,
        'code'     => '',
    ];

    ?>
    <p>
        Add code to every front-end page: analytics (Google Analytics, Matomo), quick CSS/JS fixes, tests.
        Each snippet is printed separately, in the order listed, at the location you pick. Disabled snippets are kept but not printed.
    </p>
    <p class="description">
        <strong>HTML</strong>: paste the snippet exactly as the service gives it, including <code>&lt;script&gt;</code> tags.
        <strong>JavaScript</strong> / <strong>CSS</strong>: code only. It is wrapped in <code>&lt;script&gt;</code> / <code>&lt;style&gt;</code> unless it already starts with that tag.
        Code is output as-is, so a broken snippet can break the site — disable it here to roll back.
    </p>

    <?php if (!current_user_can('unfiltered_html')) : ?>
        <div class="notice notice-warning inline"><p>Your account is not allowed to add unfiltered HTML, so changes here will not be saved.</p></div>
    <?php endif; ?>

    <div id="artpress-snippets" style="max-width: 1000px; margin-top: 16px;">
        <?php
        foreach ($snippets as $index => $snippet) {
            artpress_custom_code_render_row($index, $snippet);
        }
        ?>
    </div>

    <p>
        <button type="button" class="button" id="artpress-snippet-add">Add snippet</button>
        <span class="description">Up to <?php echo (int) ARTPRESS_CUSTOM_CODE_MAX; ?> snippets.</span>
    </p>

    <template id="artpress-snippet-template">
        <?php artpress_custom_code_render_row('__i__', $empty); ?>
    </template>

    <script>
    (function () {
        var list = document.getElementById('artpress-snippets');
        var tpl = document.getElementById('artpress-snippet-template');
        var next = list.children.length;

        document.getElementById('artpress-snippet-add').addEventListener('click', function () {
            var html = tpl.innerHTML.split('__i__').join(String(next++));
            list.insertAdjacentHTML('beforeend', html);
            list.lastElementChild.querySelector('textarea').focus();
        });

        list.addEventListener('click', function (e) {
            if (!e.target.classList.contains('artpress-snippet-remove')) {
                return;
            }
            var row = e.target.closest('.artpress-snippet');
            var code = row.querySelector('textarea').value.trim();
            if (!code || window.confirm('Remove this snippet? It is deleted when you save.')) {
                row.remove();
            }
        });
    })();
    </script>
    <?php
}
