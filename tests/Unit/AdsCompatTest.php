<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

class AdsCompatTest extends TestCase {

    protected function set_up() {
        parent::set_up();
        $this->requireTheme('inc/ads-compat.php');
    }

    /**
     * @dataProvider insTagProvider
     */
    public function test_fix_adsense_ins_tag($input, $expected) {
        $this->assertSame($expected, artpress_fix_adsense_ins_tag($input));
    }

    public function insTagProvider() {
        return [
            'glued attribute is split'    => [
                '<insclass="adsbygoogle" data-ad-slot="1"></ins>',
                '<ins class="adsbygoogle" data-ad-slot="1"></ins>',
            ],
            'hyphenated attribute'        => [
                '<insdata-ad-client="ca-pub-1" class="adsbygoogle"></ins>',
                '<ins data-ad-client="ca-pub-1" class="adsbygoogle"></ins>',
            ],
            'every unit on the page'      => [
                '<insclass="a"></ins><p>x</p><insclass="b"></ins>',
                '<ins class="a"></ins><p>x</p><ins class="b"></ins>',
            ],
            'correct markup untouched'    => [
                '<ins class="adsbygoogle"></ins>',
                '<ins class="adsbygoogle"></ins>',
            ],
            'plain ins untouched'         => ['<ins>added</ins>', '<ins>added</ins>'],
            'no ins at all'               => ['<div>ad</div>', '<div>ad</div>'],
            'empty string'                => ['', ''],
            'non-string passes through'   => [null, null],
            'array passes through'        => [['x'], ['x']],
        ];
    }

    /**
     * Separate process so the file is loaded (and its hooks added) fresh.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hooks_are_registered() {
        $this->assertNotFalse(has_filter('advanced-ads-ad-output', 'artpress_fix_adsense_ins_tag'));
        $this->assertNotFalse(has_filter('advanced-ads-ads-txt-content', 'artpress_fix_ads_txt_content'));
    }

    /**
     * Separate process: once Brain Monkey defines wp_advads_get_setting it
     * exists for the rest of the run.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_ads_txt_passthrough_without_advanced_ads() {
        $this->assertFalse(function_exists('wp_advads_get_setting'));
        $this->assertSame('google.com, pub-1, DIRECT', artpress_fix_ads_txt_content('google.com, pub-1, DIRECT'));
    }

    public function test_ads_txt_uses_custom_records_when_content_empty() {
        Functions\expect('wp_advads_get_setting')->with('custom', 'ads-txt')->andReturn("google.com, pub-1, DIRECT\n");

        $this->assertSame("google.com, pub-1, DIRECT\n", artpress_fix_ads_txt_content(''));
    }

    public function test_ads_txt_prepends_custom_records_to_existing_content() {
        Functions\when('wp_advads_get_setting')->justReturn('custom.com, 1, DIRECT');

        $this->assertSame("custom.com, 1, DIRECT\ngoogle.com, 2, DIRECT", artpress_fix_ads_txt_content('google.com, 2, DIRECT'));
    }

    public function test_ads_txt_does_not_duplicate_records_already_present() {
        Functions\when('wp_advads_get_setting')->justReturn("  custom.com, 1, DIRECT \n");
        $content = "custom.com, 1, DIRECT\ngoogle.com, 2, DIRECT";

        $this->assertSame($content, artpress_fix_ads_txt_content($content));
    }

    /**
     * @dataProvider emptyCustomProvider
     */
    public function test_ads_txt_ignores_empty_custom_records($custom) {
        Functions\when('wp_advads_get_setting')->justReturn($custom);

        $this->assertSame('existing', artpress_fix_ads_txt_content('existing'));
    }

    public function emptyCustomProvider() {
        return [
            'empty string'    => [''],
            'whitespace only' => ["  \n "],
            'not a string'    => [false],
            'null'            => [null],
        ];
    }

    public function test_ads_txt_non_string_content_is_treated_as_empty() {
        Functions\when('wp_advads_get_setting')->justReturn('custom.com, 1, DIRECT');

        $this->assertSame('custom.com, 1, DIRECT', artpress_fix_ads_txt_content(null));
    }
}
