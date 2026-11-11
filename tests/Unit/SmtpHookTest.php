<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

class SmtpHookTest extends TestCase {

    /** @var callable */
    private $hook;

    protected function set_up() {
        parent::set_up();
        $this->hook = $this->captureHook('inc/admin/smtp/hooks.php', 'phpmailer_init', 'action');
    }

    private function run_with(array $opts): \Artpress_Fake_PHPMailer {
        Functions\when('get_option')->justReturn($opts);
        $mailer = new \Artpress_Fake_PHPMailer();
        ($this->hook)($mailer);

        return $mailer;
    }

    public function test_does_nothing_when_disabled() {
        $mailer = $this->run_with(['enabled' => false, 'host' => 'smtp.example.com']);

        $this->assertFalse($mailer->isSmtp);
        $this->assertSame('', $mailer->Host);
        $this->assertSame(['old@example.com'], $mailer->replyTos);
    }

    public function test_configures_smtp_with_auth() {
        $mailer = $this->run_with([
            'enabled'    => true,
            'host'       => 'smtp.example.com',
            'port'       => 465,
            'encryption' => 'ssl',
            'auth'       => true,
            'username'   => 'user',
            'password'   => 'secret',
            'from_email' => 'noreply@example.com',
            'from_name'  => 'Blog',
            'reply_to'   => 'reply@example.com',
        ]);

        $this->assertTrue($mailer->isSmtp);
        $this->assertSame('smtp.example.com', $mailer->Host);
        $this->assertSame(465, $mailer->Port);
        $this->assertSame('ssl', $mailer->SMTPSecure);
        $this->assertTrue($mailer->SMTPAuth);
        $this->assertSame('user', $mailer->Username);
        $this->assertSame('secret', $mailer->Password);
        $this->assertSame('noreply@example.com', $mailer->From);
        $this->assertSame('Blog', $mailer->FromName);
        $this->assertSame(['reply@example.com'], $mailer->replyTos);
    }

    public function test_no_encryption_and_no_auth() {
        $mailer = $this->run_with([
            'enabled'    => true,
            'host'       => 'localhost',
            'encryption' => 'none',
            'auth'       => false,
            'username'   => 'ignored',
        ]);

        $this->assertSame('', $mailer->SMTPSecure);
        $this->assertFalse($mailer->SMTPAuth);
        $this->assertSame('', $mailer->Username, 'Credentials must not be set without auth.');
        $this->assertSame(587, $mailer->Port);
    }

    public function test_keeps_wordpress_sender_when_from_is_empty() {
        $mailer = $this->run_with(['enabled' => true, 'from_email' => '', 'from_name' => '', 'reply_to' => '']);

        $this->assertSame('wordpress@example.com', $mailer->From);
        $this->assertSame('WordPress', $mailer->FromName);
        $this->assertSame(['old@example.com'], $mailer->replyTos);
    }
}
