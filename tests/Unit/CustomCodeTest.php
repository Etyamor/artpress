<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Custom code snippets (inc/custom-code.php) and their settings
 * (inc/admin/custom-code/options.php).
 */
class CustomCodeTest extends TestCase {

    protected function set_up() {
        parent::set_up();

        Functions\stubs([
            'sanitize_text_field' => static function ($v) {
                return trim(strip_tags((string) $v));
            },
        ]);

        $this->requireTheme('inc/admin/custom-code/options.php');
        $this->requireTheme('inc/custom-code.php');
    }

    private function setSnippets(array $snippets): void {
        Functions\when('get_option')->alias(static function ($name, $default = false) use ($snippets) {
            return 'artpress_custom_code' === $name ? ['snippets' => $snippets] : $default;
        });
    }

    private function snippet(array $over = []): array {
        return array_merge([
            'label'    => 'GA',
            'location' => 'head_end',
            'type'     => 'html',
            'enabled'  => true,
            'code'     => '<script>ga()</script>',
        ], $over);
    }

    // ----- Sanitize -----

    public function test_sanitize_keeps_code_verbatim_and_normalizes_fields() {
        Functions\when('current_user_can')->justReturn(true);

        $clean = artpress_custom_code_sanitize([
            'snippets' => [
                '3' => ['label' => ' <b>GA</b> ', 'location' => 'body_start', 'type' => 'js', 'enabled' => '1', 'code' => "  gtag('js');\n"],
                '7' => ['label' => 'Fix', 'location' => 'nowhere', 'type' => 'php', 'code' => '<style>a{}</style>'],
            ],
        ]);

        $this->assertSame(['snippets' => [
            ['label' => 'GA', 'location' => 'body_start', 'type' => 'js', 'enabled' => true, 'code' => "gtag('js');"],
            ['label' => 'Fix', 'location' => 'head_end', 'type' => 'html', 'enabled' => false, 'code' => '<style>a{}</style>'],
        ]], $clean);
    }

    public function test_sanitize_drops_empty_and_malformed_rows() {
        Functions\when('current_user_can')->justReturn(true);

        $clean = artpress_custom_code_sanitize([
            'snippets' => [
                ['label' => '', 'code' => '   '],
                'junk',
                ['code' => ['array']],
                ['label' => 'Draft'],
            ],
        ]);

        $this->assertCount(1, $clean['snippets']);
        $this->assertSame('Draft', $clean['snippets'][0]['label']);
        $this->assertSame(['snippets' => []], artpress_custom_code_sanitize('nope'));
    }

    public function test_sanitize_caps_number_of_snippets() {
        Functions\when('current_user_can')->justReturn(true);

        $rows  = array_fill(0, ARTPRESS_CUSTOM_CODE_MAX + 5, ['code' => 'x']);
        $clean = artpress_custom_code_sanitize(['snippets' => $rows]);

        $this->assertCount(ARTPRESS_CUSTOM_CODE_MAX, $clean['snippets']);
    }

    public function test_sanitize_without_unfiltered_html_keeps_saved_value() {
        $saved = ['snippets' => [$this->snippet()]];

        Functions\when('current_user_can')->justReturn(false);
        Functions\when('get_option')->justReturn($saved);
        Functions\expect('add_settings_error')->once();

        $this->assertSame($saved, artpress_custom_code_sanitize(['snippets' => [['code' => '<script>evil()</script>']]]));
    }

    // ----- Output -----

    /**
     * @dataProvider wrapProvider
     */
    public function test_snippet_wrapping($type, $code, $expected) {
        $html = artpress_custom_code_snippet_html($this->snippet(['type' => $type, 'code' => $code]));

        $this->assertStringContainsString($expected, $html);
        $this->assertStringContainsString('<!-- ArtPress custom code: GA -->', $html);
    }

    public function wrapProvider() {
        return [
            'html as-is'               => ['html', '<div id="x"></div>', "-->\n<div id=\"x\"></div>\n"],
            'js wrapped'               => ['js', 'console.log(1);', "<script>\nconsole.log(1);\n</script>"],
            'js already has tag'       => ['js', '<script async src="a.js"></script>', "-->\n<script async src=\"a.js\"></script>\n"],
            'css wrapped'              => ['css', 'a{color:red}', "<style>\na{color:red}\n</style>"],
            'css already has tag'      => ['css', '<STYLE>a{}</STYLE>', "-->\n<STYLE>a{}</STYLE>\n"],
        ];
    }

    public function test_label_cannot_close_the_comment() {
        $html = artpress_custom_code_snippet_html($this->snippet(['label' => 'a -- b -->']));

        $this->assertSame(1, substr_count($html, '-->'));
    }

    public function test_empty_code_prints_nothing() {
        $this->assertSame('', artpress_custom_code_snippet_html($this->snippet(['code' => '  '])));
    }

    public function test_only_enabled_snippets_for_the_location_in_order() {
        Functions\when('apply_filters')->returnArg(2);
        $this->setSnippets([
            $this->snippet(['label' => 'one', 'code' => 'ONE']),
            $this->snippet(['label' => 'off', 'code' => 'OFF', 'enabled' => false]),
            $this->snippet(['label' => 'body', 'code' => 'BODY', 'location' => 'body_end']),
            $this->snippet(['label' => 'two', 'code' => 'TWO']),
        ]);

        $html = artpress_custom_code_for('head_end');

        $this->assertStringContainsString('ONE', $html);
        $this->assertStringContainsString('TWO', $html);
        $this->assertLessThan(strpos($html, 'TWO'), strpos($html, 'ONE'));
        $this->assertStringNotContainsString('OFF', $html);
        $this->assertStringNotContainsString('BODY', $html);

        $this->assertStringContainsString('BODY', artpress_custom_code_for('body_end'));
        $this->assertSame('', artpress_custom_code_for('head_start'));
    }

    public function test_nothing_saved_prints_nothing() {
        Functions\when('get_option')->justReturn(false);
        Functions\when('apply_filters')->returnArg(2);

        $this->assertSame('', artpress_custom_code_for('head_end'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hooks_are_registered() {
        $this->assertNotFalse(has_action('artpress_head_start', 'artpress_custom_code_head_start'));
        $this->assertSame(PHP_INT_MAX, has_action('wp_head', 'artpress_custom_code_head_end'));
        $this->assertSame(1, has_action('wp_body_open', 'artpress_custom_code_body_start'));
        $this->assertSame(PHP_INT_MAX, has_action('wp_footer', 'artpress_custom_code_body_end'));
    }
}
