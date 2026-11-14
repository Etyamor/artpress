<?php
/**
 * Theme updates from GitHub Releases.
 *
 * Every release (Actions → Build theme zip) carries two assets: the theme zip
 * and info.json (see scripts/release-info.mjs). WordPress's regular theme
 * update check is extended with the newest info.json, so "Update now" and
 * auto-updates work as for wordpress.org themes.
 *
 * info.json is read from /releases/latest/download/, a redirect that is not
 * subject to the GitHub REST API's 60 requests/hour limit.
 */

const ARTPRESS_THEME_SLUG       = 'artpress';
const ARTPRESS_UPDATE_REPO      = 'Etyamor/artpress';
const ARTPRESS_UPDATE_CACHE_KEY = 'artpress_update_info';

/**
 * GitHub repository (owner/name) that publishes the theme's releases.
 */
function artpress_update_repo(): string {
    return (string) apply_filters('artpress_update_repo', ARTPRESS_UPDATE_REPO);
}

/**
 * URL of the newest release's info.json.
 */
function artpress_update_info_url(): string {
    return 'https://github.com/' . artpress_update_repo() . '/releases/latest/download/info.json';
}

/**
 * Validate a decoded info.json.
 *
 * The package must be a zip from the same repository's release downloads, so a
 * tampered manifest can't point sites at an arbitrary file.
 *
 * @param mixed $data Decoded JSON.
 * @return array|null Normalized info, or null when invalid.
 */
function artpress_parse_update_info($data): ?array {
    if (!is_array($data)) {
        return null;
    }

    $version = $data['version'] ?? '';
    $package = $data['package'] ?? '';

    if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        return null;
    }

    $prefix = 'https://github.com/' . artpress_update_repo() . '/releases/download/';
    if (!is_string($package) || 0 !== strpos($package, $prefix) || '.zip' !== substr($package, -4)) {
        return null;
    }

    $text = static function ($value): string {
        return is_string($value) && preg_match('/^[\w.\- ]{0,20}$/', $value) ? $value : '';
    };

    $changelog = [];
    foreach ((array) ($data['changelog'] ?? []) as $entry) {
        if (is_string($entry) && '' !== trim($entry)) {
            $changelog[] = trim($entry);
        }
    }

    $url = $data['url'] ?? '';

    return [
        'version'      => $version,
        'package'      => $package,
        'url'          => is_string($url) && 0 === strpos($url, 'https://github.com/' . artpress_update_repo() . '/') ? $url : '',
        'requires'     => $text($data['requires'] ?? ''),
        'requires_php' => $text($data['requires_php'] ?? ''),
        'tested'       => $text($data['tested'] ?? ''),
        'changelog'    => $changelog,
    ];
}

/**
 * Newest release info, cached for 12 hours (1 hour after a failed request).
 *
 * @param bool $force Skip the cache (the "Check again" button).
 * @return array|null
 */
function artpress_get_update_info(bool $force = false): ?array {
    if (!$force) {
        $cached = get_site_transient(ARTPRESS_UPDATE_CACHE_KEY);
        if (is_array($cached)) {
            return $cached['info'] ?? null;
        }
    }

    $info     = null;
    $response = wp_remote_get(artpress_update_info_url(), [
        'timeout' => 10,
        'headers' => ['Accept' => 'application/json'],
    ]);

    if (!is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response)) {
        $info = artpress_parse_update_info(json_decode(wp_remote_retrieve_body($response), true));
    }

    set_site_transient(ARTPRESS_UPDATE_CACHE_KEY, ['info' => $info], $info ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);

    return $info;
}

/**
 * Entry for the update_themes transient's `response` / `no_update` lists.
 *
 * @param array $info Parsed info.json.
 */
function artpress_update_item(array $info): array {
    return [
        'theme'        => ARTPRESS_THEME_SLUG,
        'new_version'  => $info['version'],
        // Opened in a thickbox iframe by WordPress — GitHub can't be framed,
        // so release notes are rendered by artpress_render_update_details().
        'url'          => admin_url('admin-ajax.php?action=artpress_update_details'),
        'package'      => $info['package'],
        'requires'     => $info['requires'],
        'requires_php' => $info['requires_php'],
    ];
}

/**
 * Add the theme to WordPress's update data.
 *
 * Also adds a `no_update` entry when up to date — without it WordPress hides
 * the auto-update toggle for themes not hosted on wordpress.org.
 *
 * @param mixed $transient update_themes transient value.
 * @return mixed
 */
function artpress_inject_theme_update($transient) {
    if (!is_object($transient) || empty($transient->checked[ARTPRESS_THEME_SLUG])) {
        return $transient;
    }

    // "Check again" on Dashboard → Updates bypasses our cache as well.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, only forces a re-fetch.
    $force = !empty($_GET['force-check']) && current_user_can('update_themes');
    $info  = artpress_get_update_info($force);
    if (!$info) {
        return $transient;
    }

    $item = artpress_update_item($info);

    if (version_compare($info['version'], (string) $transient->checked[ARTPRESS_THEME_SLUG], '>')) {
        $transient->response = (array) ($transient->response ?? []);
        $transient->response[ARTPRESS_THEME_SLUG] = $item;
        unset($transient->no_update[ARTPRESS_THEME_SLUG]);
    } else {
        $transient->no_update = (array) ($transient->no_update ?? []);
        $transient->no_update[ARTPRESS_THEME_SLUG] = $item;
        unset($transient->response[ARTPRESS_THEME_SLUG]);
    }

    return $transient;
}
add_filter('pre_set_site_transient_update_themes', 'artpress_inject_theme_update');

/**
 * Don't ask wordpress.org about this theme: a different theme published there
 * under the slug "artpress" would otherwise be offered as an update.
 *
 * @param array  $args HTTP request args.
 * @param string $url  Request URL.
 * @return array
 */
function artpress_exclude_from_wporg_update_check($args, $url) {
    if (!is_string($url) || false === strpos($url, '://api.wordpress.org/themes/update-check/')) {
        return $args;
    }

    if (!isset($args['body']['themes']) || !is_string($args['body']['themes'])) {
        return $args;
    }

    $themes = json_decode($args['body']['themes'], true);
    if (is_array($themes) && isset($themes['themes'][ARTPRESS_THEME_SLUG])) {
        unset($themes['themes'][ARTPRESS_THEME_SLUG]);
        $args['body']['themes'] = wp_json_encode($themes);
    }

    return $args;
}
add_filter('http_request_args', 'artpress_exclude_from_wporg_update_check', 10, 2);

/**
 * Release notes shown by the "View version X details" link.
 */
function artpress_render_update_details(): void {
    if (!current_user_can('update_themes')) {
        wp_die(esc_html__('Sorry, you are not allowed to update themes.', 'artpress'), 403);
    }

    $info = artpress_get_update_info();

    echo '<!doctype html><html><head><meta charset="utf-8"><title>ArtPress</title>';
    echo '<style>body{font:14px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:24px;color:#1d2327}</style>';
    echo '</head><body>';

    if (!$info) {
        echo '<p>' . esc_html__('Release information is not available right now.', 'artpress') . '</p>';
    } else {
        /* translators: %s: theme version. */
        echo '<h1>' . esc_html(sprintf(__('ArtPress %s', 'artpress'), $info['version'])) . '</h1>';

        if ($info['changelog']) {
            echo '<ul>';
            foreach ($info['changelog'] as $entry) {
                echo '<li>' . esc_html($entry) . '</li>';
            }
            echo '</ul>';
        }

        if ($info['url']) {
            echo '<p><a href="' . esc_url($info['url']) . '" target="_blank" rel="noopener noreferrer">'
                . esc_html__('View the release on GitHub', 'artpress') . '</a></p>';
        }
    }

    echo '</body></html>';
    exit;
}
add_action('wp_ajax_artpress_update_details', 'artpress_render_update_details');

/**
 * Forget cached release info once the theme itself has been updated.
 *
 * @param mixed $upgrader Upgrader instance.
 * @param array $options  Upgrade details.
 */
function artpress_clear_update_cache($upgrader, $options): void {
    if (
        is_array($options)
        && 'update' === ($options['action'] ?? '')
        && 'theme' === ($options['type'] ?? '')
        && in_array(ARTPRESS_THEME_SLUG, (array) ($options['themes'] ?? []), true)
    ) {
        delete_site_transient(ARTPRESS_UPDATE_CACHE_KEY);
    }
}
add_action('upgrader_process_complete', 'artpress_clear_update_cache', 10, 2);
