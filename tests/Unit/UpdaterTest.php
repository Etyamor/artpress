<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

class UpdaterTest extends TestCase {

    private const PACKAGE = 'https://github.com/Etyamor/artpress/releases/download/v1.2.0/artpress-1.2.0.zip';

    protected function set_up() {
        parent::set_up();
        if (!defined('HOUR_IN_SECONDS')) {
            define('HOUR_IN_SECONDS', 3600);
        }
        Functions\stubTranslationFunctions();
        Functions\stubs([
            'admin_url'    => static function ($path = '') {
                return 'https://site.test/wp-admin/' . $path;
            },
            'is_wp_error'  => static function ($v) {
                return $v instanceof \WP_Error;
            },
            'wp_json_encode' => static function ($v) {
                return json_encode($v);
            },
            'wp_remote_retrieve_response_code' => static function ($r) {
                return $r['response']['code'] ?? '';
            },
            'wp_remote_retrieve_body' => static function ($r) {
                return $r['body'] ?? '';
            },
            'current_user_can' => true,
        ]);
        $this->requireTheme('inc/updater.php');
        $_GET = [];
    }

    protected function tear_down() {
        $_GET = [];
        parent::tear_down();
    }

    private function info(array $overrides = []): array {
        return array_merge([
            'version'      => '1.2.0',
            'package'      => self::PACKAGE,
            'url'          => 'https://github.com/Etyamor/artpress/releases/tag/v1.2.0',
            'requires'     => '6.1',
            'requires_php' => '8.0',
            'tested'       => '6.7',
            'changelog'    => ['Added updater'],
        ], $overrides);
    }

    private function transient(string $installed = '1.1.4'): object {
        return (object) [
            'last_checked' => 1,
            'checked'      => ['artpress' => $installed, 'twentytwentyfive' => '1.0'],
            'response'     => [],
            'no_update'    => [],
            'translations' => [],
        ];
    }

    // ----- info.json parsing -----

    public function test_info_url_follows_latest_release() {
        $this->assertSame(
            'https://github.com/Etyamor/artpress/releases/latest/download/info.json',
            artpress_update_info_url()
        );
    }

    public function test_parses_valid_info() {
        $this->assertSame($this->info(), artpress_parse_update_info($this->info()));
    }

    /**
     * @dataProvider invalidInfoProvider
     */
    public function test_rejects_invalid_info($data) {
        $this->assertNull(artpress_parse_update_info($data));
    }

    public function invalidInfoProvider() {
        $base = [
            'version' => '1.2.0',
            'package' => self::PACKAGE,
        ];
        return [
            'not an array'       => ['nope'],
            'missing version'    => [['package' => self::PACKAGE]],
            'bad version'        => [array_merge($base, ['version' => '1.2'])],
            'version with junk'  => [array_merge($base, ['version' => '1.2.0<script>'])],
            'other repo'         => [array_merge($base, ['package' => 'https://github.com/evil/artpress/releases/download/v1/artpress.zip'])],
            'other host'         => [array_merge($base, ['package' => 'https://evil.test/artpress.zip'])],
            'http'               => [array_merge($base, ['package' => 'http://github.com/Etyamor/artpress/releases/download/v1/a.zip'])],
            'not a zip'          => [array_merge($base, ['package' => 'https://github.com/Etyamor/artpress/releases/download/v1/a.exe'])],
        ];
    }

    public function test_sanitizes_optional_fields() {
        $info = artpress_parse_update_info([
            'version'      => '1.2.0',
            'package'      => self::PACKAGE,
            'url'          => 'https://evil.test/',
            'requires'     => '<b>6.1</b>',
            'changelog'    => ['  Fixed  ', '', 5, 'Added'],
        ]);

        $this->assertSame('', $info['url']);
        $this->assertSame('', $info['requires']);
        $this->assertSame('', $info['requires_php']);
        $this->assertSame(['Fixed', 'Added'], $info['changelog']);
    }

    public function test_repo_is_filterable() {
        Filters\expectApplied('artpress_update_repo')->andReturn('me/fork');

        $this->assertSame('https://github.com/me/fork/releases/latest/download/info.json', artpress_update_info_url());
        $this->assertNull(artpress_parse_update_info($this->info()));
    }

    // ----- Fetching -----

    public function test_uses_cached_info() {
        Functions\when('get_site_transient')->justReturn(['info' => $this->info()]);
        Functions\expect('wp_remote_get')->never();

        $this->assertSame('1.2.0', artpress_get_update_info()['version']);
    }

    public function test_cached_failure_is_respected() {
        Functions\when('get_site_transient')->justReturn(['info' => null]);
        Functions\expect('wp_remote_get')->never();

        $this->assertNull(artpress_get_update_info());
    }

    public function test_fetches_and_caches_for_12_hours() {
        Functions\when('get_site_transient')->justReturn(false);
        Functions\expect('wp_remote_get')
            ->once()
            ->with('https://github.com/Etyamor/artpress/releases/latest/download/info.json', \Mockery::type('array'))
            ->andReturn(['response' => ['code' => 200], 'body' => json_encode($this->info())]);
        Functions\expect('set_site_transient')
            ->once()
            ->with('artpress_update_info', ['info' => $this->info()], 12 * 3600);

        $this->assertSame($this->info(), artpress_get_update_info());
    }

    public function test_force_skips_cache() {
        Functions\expect('get_site_transient')->never();
        Functions\when('wp_remote_get')->justReturn(['response' => ['code' => 200], 'body' => json_encode($this->info())]);
        Functions\when('set_site_transient')->justReturn(true);

        $this->assertSame('1.2.0', artpress_get_update_info(true)['version']);
    }

    /**
     * @dataProvider failedResponseProvider
     */
    public function test_failures_are_cached_for_an_hour($response) {
        Functions\when('get_site_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn($response);
        Functions\expect('set_site_transient')->once()->with('artpress_update_info', ['info' => null], 3600);

        $this->assertNull(artpress_get_update_info());
    }

    public function failedResponseProvider() {
        return [
            'wp error'    => [new \WP_Error('http_request_failed', 'timeout')],
            'not found'   => [['response' => ['code' => 404], 'body' => 'Not Found']],
            'bad json'    => [['response' => ['code' => 200], 'body' => '{oops']],
            'bad package' => [['response' => ['code' => 200], 'body' => '{"version":"9.9.9","package":"https://evil.test/x.zip"}']],
        ];
    }

    // ----- update_themes transient -----

    private function withInfo(?array $info): void {
        Functions\when('get_site_transient')->justReturn(['info' => $info]);
    }

    public function test_offers_newer_version() {
        $this->withInfo($this->info());

        $out = artpress_inject_theme_update($this->transient('1.1.4'));

        $this->assertSame([
            'theme'        => 'artpress',
            'new_version'  => '1.2.0',
            'url'          => 'https://site.test/wp-admin/admin-ajax.php?action=artpress_update_details',
            'package'      => self::PACKAGE,
            'requires'     => '6.1',
            'requires_php' => '8.0',
        ], $out->response['artpress']);
        $this->assertArrayNotHasKey('artpress', $out->no_update);
    }

    public function test_up_to_date_goes_to_no_update() {
        $this->withInfo($this->info());

        foreach (['1.2.0', '1.10.0'] as $installed) {
            $out = artpress_inject_theme_update($this->transient($installed));
            $this->assertArrayNotHasKey('artpress', $out->response, $installed);
            $this->assertSame('1.2.0', $out->no_update['artpress']['new_version'], $installed);
        }
    }

    public function test_compares_versions_numerically() {
        $this->withInfo($this->info(['version' => '1.10.0']));

        $out = artpress_inject_theme_update($this->transient('1.9.0'));
        $this->assertSame('1.10.0', $out->response['artpress']['new_version']);
    }

    public function test_handles_transient_without_lists() {
        $this->withInfo($this->info());

        $out = artpress_inject_theme_update((object) ['checked' => ['artpress' => '1.0.0']]);
        $this->assertSame('1.2.0', $out->response['artpress']['new_version']);
    }

    public function test_leaves_transient_alone_without_info() {
        $this->withInfo(null);

        $this->assertEquals($this->transient(), artpress_inject_theme_update($this->transient()));
    }

    public function test_ignores_transient_when_theme_not_installed() {
        Functions\expect('get_site_transient')->never();

        $this->assertFalse(artpress_inject_theme_update(false));
        $transient = (object) ['checked' => ['other' => '1.0']];
        $this->assertSame($transient, artpress_inject_theme_update($transient));
    }

    public function test_check_again_forces_a_fetch() {
        $_GET['force-check'] = '1';
        Functions\expect('get_site_transient')->never();
        Functions\expect('wp_remote_get')->once()->andReturn(['response' => ['code' => 200], 'body' => json_encode($this->info())]);
        Functions\when('set_site_transient')->justReturn(true);

        $out = artpress_inject_theme_update($this->transient());
        $this->assertSame('1.2.0', $out->response['artpress']['new_version']);
    }

    // ----- wordpress.org request -----

    public function test_removes_theme_from_wporg_update_check() {
        $body = ['themes' => json_encode([
            'active' => 'artpress',
            'themes' => ['artpress' => ['Version' => '1.1.4'], 'twentytwentyfive' => ['Version' => '1.0']],
        ])];

        $args = artpress_exclude_from_wporg_update_check(['body' => $body], 'https://api.wordpress.org/themes/update-check/1.1/');

        $sent = json_decode($args['body']['themes'], true);
        $this->assertArrayNotHasKey('artpress', $sent['themes']);
        $this->assertArrayHasKey('twentytwentyfive', $sent['themes']);
    }

    public function test_other_requests_are_untouched() {
        $args = ['body' => ['themes' => '{"themes":{"artpress":{}}}']];

        $this->assertSame($args, artpress_exclude_from_wporg_update_check($args, 'https://api.wordpress.org/plugins/update-check/1.1/'));
        $this->assertSame(['timeout' => 5], artpress_exclude_from_wporg_update_check(['timeout' => 5], 'https://api.wordpress.org/themes/update-check/1.1/'));
    }

    // ----- Cache invalidation / details -----

    public function test_cache_cleared_after_theme_update() {
        Functions\expect('delete_site_transient')->once()->with('artpress_update_info');

        artpress_clear_update_cache(null, ['action' => 'update', 'type' => 'theme', 'themes' => ['artpress']]);
        artpress_clear_update_cache(null, ['action' => 'update', 'type' => 'theme', 'themes' => ['other']]);
        artpress_clear_update_cache(null, ['action' => 'update', 'type' => 'plugin', 'plugins' => ['x/x.php']]);
        $this->addToAssertionCount(1);
    }

    public function test_details_require_capability() {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')->once()->andThrow(new \RuntimeException('died'));

        $this->expectException(\RuntimeException::class);
        artpress_render_update_details();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hooks_are_registered() {
        $this->assertNotFalse(has_filter('pre_set_site_transient_update_themes', 'artpress_inject_theme_update'));
        $this->assertNotFalse(has_filter('http_request_args', 'artpress_exclude_from_wporg_update_check'));
        $this->assertNotFalse(has_action('wp_ajax_artpress_update_details', 'artpress_render_update_details'));
        $this->assertNotFalse(has_action('upgrader_process_complete', 'artpress_clear_update_cache'));
    }
}
