<?php

namespace ArtPress\Tests;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Actions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Base test case: boots Brain Monkey and loads theme files on demand.
 */
abstract class TestCase extends PolyfillTestCase {

    protected function set_up() {
        parent::set_up();
        Monkey\setUp();
        \WP_Query::reset();
    }

    protected function tear_down() {
        Monkey\tearDown();
        parent::tear_down();
    }

    /**
     * Load a theme file that defines functions (once per process).
     */
    protected function requireTheme(string $relative): void {
        require_once ARTPRESS_THEME_DIR . '/' . $relative;
    }

    /**
     * Load a hook-only theme file and return the callback it registers.
     *
     * Hook files only add anonymous callbacks, so they're re-included for every
     * test and the closure is captured as it's added.
     *
     * @param string $relative Theme-relative path.
     * @param string $hook     Hook the file registers.
     * @param string $type     'filter' or 'action'.
     */
    protected function captureHook(string $relative, string $hook, string $type = 'filter'): callable {
        $captured = null;
        $grab     = function ($callback) use (&$captured) {
            $captured = $callback;
        };

        if ('action' === $type) {
            Actions\expectAdded($hook)->once()->whenHappen($grab);
        } else {
            Filters\expectAdded($hook)->once()->whenHappen($grab);
        }

        require ARTPRESS_THEME_DIR . '/' . $relative;

        $this->assertIsCallable($captured, "No callback registered on {$hook} by {$relative}.");

        return $captured;
    }
}
