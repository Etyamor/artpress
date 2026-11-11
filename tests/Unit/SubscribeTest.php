<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

class SubscribeTest extends TestCase {

    protected function set_up() {
        parent::set_up();

        Functions\stubTranslationFunctions();
        Functions\stubs([
            'wp_unslash'     => static function ($v) {
                return is_string($v) ? stripslashes($v) : $v;
            },
            'sanitize_email' => static function ($v) {
                return trim($v);
            },
            'is_email'       => static function ($v) {
                return (bool) filter_var($v, FILTER_VALIDATE_EMAIL);
            },
            'get_option'     => 'admin@example.com',
            'get_bloginfo'   => 'My Blog',
            'current_time'   => '2026-11-11 10:00:00',
            'wp_send_json_success' => static function ($data = null, $status = null) {
                throw new \Artpress_Json_Response(true, $data, $status);
            },
            'wp_send_json_error'   => static function ($data = null, $status = null) {
                throw new \Artpress_Json_Response(false, $data, $status);
            },
        ]);

        $this->requireTheme('inc/subscribe.php');
        $_POST = [];
    }

    protected function tear_down() {
        $_POST = [];
        parent::tear_down();
    }

    private function respond(): \Artpress_Json_Response {
        try {
            artpress_handle_subscribe();
        } catch (\Artpress_Json_Response $response) {
            return $response;
        }

        $this->fail('Handler returned without sending a JSON response.');
    }

    /**
     * Separate process so the file is loaded (and its hooks added) fresh.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hooks_are_registered_for_guests_and_users() {
        $this->assertNotFalse(has_action('wp_ajax_artpress_subscribe', 'artpress_handle_subscribe'));
        $this->assertNotFalse(has_action('wp_ajax_nopriv_artpress_subscribe', 'artpress_handle_subscribe'));
    }

    public function test_rejects_invalid_nonce_with_403() {
        Functions\expect('check_ajax_referer')->once()->with('artpress_subscribe', 'nonce', false)->andReturn(false);
        Functions\expect('wp_mail')->never();

        $response = $this->respond();

        $this->assertFalse($response->success);
        $this->assertSame(403, $response->status);
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function test_rejects_invalid_email($email) {
        Functions\when('check_ajax_referer')->justReturn(1);
        Functions\expect('wp_mail')->never();
        if (null !== $email) {
            $_POST['email'] = $email;
        }

        $response = $this->respond();

        $this->assertFalse($response->success);
        $this->assertSame('Please enter a valid email address.', $response->data['message']);
    }

    public function invalidEmailProvider() {
        return [
            'missing'  => [null],
            'empty'    => [''],
            'no at'    => ['nope'],
            'injected' => ["a@b.com\r\nBcc: x@y.com"],
        ];
    }

    public function test_sends_notification_to_admin() {
        Functions\when('check_ajax_referer')->justReturn(1);
        $_POST['email'] = 'reader@example.com';

        Functions\expect('wp_mail')
            ->once()
            ->with(
                'admin@example.com',
                '[My Blog] New newsletter subscriber',
                "New subscriber: reader@example.com\nDate: 2026-11-11 10:00:00"
            )
            ->andReturn(true);

        $response = $this->respond();

        $this->assertTrue($response->success);
        $this->assertSame('Thank you for subscribing!', $response->data['message']);
    }

    public function test_reports_mail_failure() {
        Functions\when('check_ajax_referer')->justReturn(1);
        Functions\when('wp_mail')->justReturn(false);
        $_POST['email'] = 'reader@example.com';

        $response = $this->respond();

        $this->assertFalse($response->success);
        $this->assertNull($response->status);
    }
}
