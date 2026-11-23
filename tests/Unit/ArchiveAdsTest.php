<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Ad slots between post cards (inc/archive-ads.php) and their settings
 * (inc/admin/ads/options.php).
 */
class ArchiveAdsTest extends TestCase {

    protected function set_up() {
        parent::set_up();

        Functions\stubEscapeFunctions();
        Functions\stubs([
            'wp_kses_post' => static function ($v) {
                return preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $v);
            },
        ]);

        $this->requireTheme('inc/admin/ads/options.php');
        $this->requireTheme('inc/archive-ads.php');
    }

    /**
     * Stub the conditional tags so the request looks like the given context.
     */
    private function setContext(string $context): void {
        foreach (['home' => 'is_home', 'category' => 'is_category', 'tag' => 'is_tag', 'search' => 'is_search', 'archive' => 'is_archive'] as $name => $fn) {
            Functions\when($fn)->justReturn($name === $context);
        }
    }

    private function setOptions(array $opts): void {
        Functions\when('get_option')->alias(static function ($name, $default = false) use ($opts) {
            return 'artpress_ads' === $name ? $opts : $default;
        });
    }

    // ----- Sanitize -----

    public function test_sanitize_empty_input() {
        Functions\when('current_user_can')->justReturn(true);

        $this->assertSame([
            'enabled'  => false,
            'contexts' => [],
            'every'    => 4,
            'max'      => 3,
            'code'     => '',
        ], artpress_ads_sanitize([]));
    }

    public function test_sanitize_whitelists_contexts_and_clamps_numbers() {
        Functions\when('current_user_can')->justReturn(true);

        $clean = artpress_ads_sanitize([
            'enabled'  => '1',
            'contexts' => ['tag', 'bogus', 'home', ['x']],
            'every'    => '0',
            'max'      => '99',
        ]);

        $this->assertTrue($clean['enabled']);
        $this->assertSame(['home', 'tag'], $clean['contexts']);
        $this->assertSame(1, $clean['every']);
        $this->assertSame(10, $clean['max']);
    }

    public function test_sanitize_keeps_scripts_only_for_unfiltered_html() {
        $code = '<ins class="adsbygoogle"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>';

        Functions\when('current_user_can')->justReturn(true);
        $this->assertSame($code, artpress_ads_sanitize(['code' => "  $code\n"])['code']);

        Functions\when('current_user_can')->justReturn(false);
        $this->assertSame('<ins class="adsbygoogle"></ins>', artpress_ads_sanitize(['code' => $code])['code']);
    }

    public function test_options_fall_back_to_defaults() {
        Functions\when('get_option')->justReturn(false);
        $this->assertSame(artpress_ads_defaults(), artpress_ads_options());

        $this->setOptions(['enabled' => true]);
        $opts = artpress_ads_options();
        $this->assertTrue($opts['enabled']);
        $this->assertSame(4, $opts['every']);
    }

    // ----- Slot positions -----

    /**
     * @dataProvider slotProvider
     */
    public function test_slot_positions($position, $total, $every, $max, $expected) {
        $this->assertSame($expected, artpress_archive_ad_slot($position, $total, ['every' => $every, 'max' => $max]));
    }

    public function slotProvider() {
        return [
            'after 4th of 10'        => [4, 10, 4, 3, 1],
            'after 8th of 10'        => [8, 10, 4, 3, 2],
            'not after 3rd'          => [3, 10, 4, 3, 0],
            'never after last card'  => [8, 8, 4, 3, 0],
            'max reached'            => [12, 20, 3, 3, 0],
            'last allowed slot'      => [9, 20, 3, 3, 3],
            'every card'             => [1, 5, 1, 10, 1],
            'zero position'          => [0, 5, 1, 10, 0],
        ];
    }

    // ----- Context -----

    public function test_context_detection() {
        Functions\when('apply_filters')->returnArg(2);

        foreach (['home', 'category', 'tag', 'search', 'archive'] as $context) {
            $this->setContext($context);
            $this->assertSame($context, artpress_archive_ad_context());
        }

        $this->setContext('none');
        $this->assertSame('', artpress_archive_ad_context());
    }

    // ----- Markup -----

    public function test_markup_in_enabled_context() {
        $this->setContext('category');
        $this->setOptions(['enabled' => true, 'contexts' => ['category'], 'every' => 2, 'max' => 2, 'code' => '[the_ad_placement id="feed"]']);
        Functions\expect('do_shortcode')->once()->with('[the_ad_placement id="feed"]')->andReturn('<div class="ad">AD</div>');

        $html = artpress_get_archive_ad(2, 6, 'grid');

        $this->assertStringContainsString('col-span-full', $html);
        $this->assertStringContainsString('data-artpress-ad-slot="1"', $html);
        $this->assertStringContainsString('<div class="ad">AD</div>', $html);
        $this->assertStringNotContainsString('py-5', $html);
        // Flex would shrink the ad container to 0px and break responsive AdSense units.
        $this->assertStringNotContainsString('flex', $html);
        $this->assertStringContainsString('w-full', $html);
    }

    public function test_list_text_layout_gets_row_padding() {
        $this->setContext('tag');
        $this->setOptions(['enabled' => true, 'contexts' => ['tag'], 'every' => 1, 'code' => 'AD']);
        Functions\when('do_shortcode')->returnArg();

        $this->assertStringContainsString('py-5', artpress_get_archive_ad(1, 3, 'list-text'));
    }

    public function test_code_filter_receives_slot_and_context() {
        $this->setContext('home');
        $this->setOptions(['enabled' => true, 'contexts' => ['home'], 'every' => 3, 'code' => 'AD']);
        Functions\when('do_shortcode')->returnArg();

        \Brain\Monkey\Filters\expectApplied('artpress_archive_ad_code')
            ->once()
            ->with('AD', 2, 'home')
            ->andReturn('SECOND');

        $this->assertStringContainsString('SECOND', artpress_get_archive_ad(6, 9));
    }

    /**
     * @dataProvider noAdProvider
     */
    public function test_no_markup($opts, $context, $rendered) {
        $this->setContext($context);
        $this->setOptions($opts);
        Functions\when('do_shortcode')->justReturn($rendered);

        $this->assertSame('', artpress_get_archive_ad(4, 10));
    }

    public function noAdProvider() {
        $on = ['enabled' => true, 'contexts' => ['home', 'category', 'tag'], 'every' => 4, 'code' => 'AD'];

        return [
            'disabled'                      => [['enabled' => false] + $on, 'home', 'AD'],
            'context not selected'          => [$on, 'search', 'AD'],
            'not a listing page'            => [$on, 'none', 'AD'],
            'ad plugin rendered nothing'    => [$on, 'home', "  \n"],
            'never saved (disabled default)' => [[], 'home', 'AD'],
        ];
    }

    public function test_archive_ad_uses_main_loop_position() {
        $this->setContext('home');
        $this->setOptions(['enabled' => true, 'contexts' => ['home'], 'every' => 2, 'code' => 'AD']);
        Functions\when('do_shortcode')->returnArg();

        $GLOBALS['wp_query'] = (object) ['current_post' => 1, 'post_count' => 5];

        ob_start();
        artpress_archive_ad('grid');
        $html = ob_get_clean();

        unset($GLOBALS['wp_query']);

        $this->assertStringContainsString('data-artpress-ad-slot="1"', $html);
    }
}
