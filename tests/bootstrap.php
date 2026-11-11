<?php
/**
 * PHPUnit bootstrap.
 *
 * Unit tests run without WordPress: WP functions are mocked per test with
 * Brain Monkey, and the few WP classes the theme touches are replaced by the
 * lightweight fakes in tests/stubs.php.
 */

define('ARTPRESS_THEME_DIR', dirname(__DIR__));

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

require_once ARTPRESS_THEME_DIR . '/vendor/autoload.php';
require_once __DIR__ . '/stubs.php';
