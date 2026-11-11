<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Sanitize callbacks of the three settings groups. These guard everything
 * that ends up in wp_options from the admin screens.
 */
class SettingsSanitizeTest extends TestCase {

    protected function set_up() {
        parent::set_up();

        Functions\stubs([
            'absint'              => static function ($v) {
                return abs((int) $v);
            },
            'sanitize_text_field' => static function ($v) {
                return trim(strip_tags((string) $v));
            },
            'sanitize_email'      => static function ($v) {
                return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
            },
        ]);

        $this->requireTheme('inc/admin/general/options.php');
        $this->requireTheme('inc/admin/smtp/options.php');
        $this->requireTheme('inc/admin/widgets/options.php');
    }

    // ----- General -----

    public function test_general_empty_input_turns_everything_off() {
        $this->assertSame([
            'subscribe_section'      => false,
            'share_buttons'          => false,
            'share_facebook'         => false,
            'share_x'                => false,
            'share_linkedin'         => false,
            'infinite_article'       => false,
            'infinite_article_limit' => 5,
        ], artpress_general_sanitize([]));
    }

    public function test_general_checkboxes_become_booleans() {
        $clean = artpress_general_sanitize([
            'subscribe_section' => '1',
            'share_buttons'     => '1',
            'share_x'           => 'on',
            'infinite_article'  => '1',
        ]);

        $this->assertTrue($clean['subscribe_section']);
        $this->assertTrue($clean['share_buttons']);
        $this->assertTrue($clean['share_x']);
        $this->assertFalse($clean['share_facebook']);
        $this->assertTrue($clean['infinite_article']);
    }

    /**
     * @dataProvider infiniteLimitProvider
     */
    public function test_general_infinite_limit_is_clamped($input, $expected) {
        $clean = artpress_general_sanitize(['infinite_article_limit' => $input]);

        $this->assertSame($expected, $clean['infinite_article_limit']);
    }

    public function infiniteLimitProvider() {
        return [
            'in range'       => ['7', 7],
            'below minimum'  => ['0', 1],
            'negative'       => ['-4', 1],
            'above maximum'  => ['500', 20],
            'garbage'        => ['abc', 1],
            'upper boundary' => ['20', 20],
        ];
    }

    public function test_general_drops_unknown_keys() {
        $clean = artpress_general_sanitize(['evil' => '<script>', 'share_x' => '1']);

        $this->assertArrayNotHasKey('evil', $clean);
    }

    // ----- SMTP -----

    public function test_smtp_defaults() {
        $clean = artpress_smtp_sanitize([]);

        $this->assertFalse($clean['enabled']);
        $this->assertSame(587, $clean['port']);
        $this->assertSame('tls', $clean['encryption']);
        $this->assertSame('', $clean['host']);
    }

    public function test_smtp_sanitizes_fields() {
        $clean = artpress_smtp_sanitize([
            'enabled'    => '1',
            'host'       => ' <b>smtp.example.com</b> ',
            'port'       => '465',
            'encryption' => 'ssl',
            'auth'       => '1',
            'username'   => 'user',
            'password'   => 'p@ss <w0rd>',
            'from_email' => 'noreply@example.com',
            'from_name'  => 'Site <i>Name</i>',
            'reply_to'   => 'not-an-email',
        ]);

        $this->assertTrue($clean['enabled']);
        $this->assertSame('smtp.example.com', $clean['host']);
        $this->assertSame(465, $clean['port']);
        $this->assertSame('ssl', $clean['encryption']);
        $this->assertTrue($clean['auth']);
        $this->assertSame('p@ss <w0rd>', $clean['password'], 'Password must be stored verbatim.');
        $this->assertSame('noreply@example.com', $clean['from_email']);
        $this->assertSame('Site Name', $clean['from_name']);
        $this->assertSame('', $clean['reply_to']);
    }

    /**
     * @dataProvider encryptionProvider
     */
    public function test_smtp_encryption_whitelist($input, $expected) {
        $this->assertSame($expected, artpress_smtp_sanitize(['encryption' => $input])['encryption']);
    }

    public function encryptionProvider() {
        return [
            ['none', 'none'],
            ['ssl', 'ssl'],
            ['tls', 'tls'],
            ['starttls', 'tls'],
            ['TLS', 'tls'],
            ['', 'tls'],
        ];
    }

    /**
     * @dataProvider portProvider
     */
    public function test_smtp_port_range($input, $expected) {
        $this->assertSame($expected, artpress_smtp_sanitize(['port' => $input])['port']);
    }

    public function portProvider() {
        return [
            'valid'    => ['2525', 2525],
            'zero'     => ['0', 587],
            'too high' => ['70000', 587],
            'max'      => ['65535', 65535],
            'negative' => ['-25', 25],
        ];
    }

    // ----- Widgets -----

    public function test_widgets_defaults() {
        $this->assertSame([
            'category_limit'    => '',
            'category_orderby'  => 'name',
            'tag_cloud_limit'   => '',
            'tag_cloud_orderby' => 'name',
            'search_title'      => false,
            'search_content'    => false,
            'search_excerpt'    => false,
            'search_slug'       => false,
        ], artpress_widgets_sanitize([]));
    }

    public function test_widgets_limits_and_orderby() {
        $clean = artpress_widgets_sanitize([
            'category_limit'    => '5',
            'category_orderby'  => 'count',
            'tag_cloud_limit'   => '-3',
            'tag_cloud_orderby' => 'RAND()',
            'search_slug'       => '1',
        ]);

        $this->assertSame(5, $clean['category_limit']);
        $this->assertSame('count', $clean['category_orderby']);
        $this->assertSame(3, $clean['tag_cloud_limit']);
        $this->assertSame('name', $clean['tag_cloud_orderby'], 'Unknown orderby must fall back to name.');
        $this->assertTrue($clean['search_slug']);
    }
}
