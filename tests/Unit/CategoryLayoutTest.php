<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

class CategoryLayoutTest extends TestCase {

    protected function set_up() {
        parent::set_up();
        Functions\stubTranslationFunctions();
        Functions\stubs([
            'wp_unslash'   => static function ($v) {
                return is_string($v) ? stripslashes($v) : $v;
            },
            'sanitize_key' => static function ($v) {
                return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $v));
            },
        ]);
        $this->requireTheme('inc/category-layout.php');
        $_POST = [];
    }

    protected function tear_down() {
        $_POST = [];
        parent::tear_down();
    }

    public function test_offers_four_layouts() {
        $this->assertSame(['list', 'grid', 'list-text', 'grid-text'], array_keys(artpress_category_layouts()));
    }

    /**
     * @dataProvider sanitizeProvider
     */
    public function test_sanitize_whitelists_layouts($input, $expected) {
        $this->assertSame($expected, artpress_sanitize_category_layout($input));
    }

    public function sanitizeProvider() {
        return [
            'list'          => ['list', 'list'],
            'grid'          => ['grid', 'grid'],
            'list-text'     => ['list-text', 'list-text'],
            'grid-text'     => ['grid-text', 'grid-text'],
            'empty meta'    => ['', 'list'],
            'unknown'       => ['masonry', 'list'],
            'wrong case'    => ['GRID', 'list'],
            'not a string'  => [['grid'], 'list'],
            'false'         => [false, 'list'],
        ];
    }

    /**
     * @dataProvider flagsProvider
     */
    public function test_layout_flags($layout, $images, $grid) {
        $this->assertSame($images, artpress_layout_has_images($layout));
        $this->assertSame($grid, artpress_layout_is_grid($layout));
    }

    public function flagsProvider() {
        return [
            ['list', true, false],
            ['grid', true, true],
            ['list-text', false, false],
            ['grid-text', false, true],
        ];
    }

    public function test_wrapper_classes() {
        $this->assertSame('flex flex-col gap-6', artpress_layout_wrapper_class('list'));
        $this->assertSame('grid grid-cols-1 md:grid-cols-2 gap-6', artpress_layout_wrapper_class('grid'));
        $this->assertSame('grid grid-cols-1 md:grid-cols-2 gap-6', artpress_layout_wrapper_class('grid-text'));
        $this->assertStringContainsString('divide-y', artpress_layout_wrapper_class('list-text'));
        $this->assertSame('flex flex-col gap-6', artpress_layout_wrapper_class('bogus'));
    }

    public function test_get_category_layout_reads_term_meta() {
        Functions\expect('get_term_meta')->once()->with(12, 'artpress_layout', true)->andReturn('grid-text');

        $this->assertSame('grid-text', artpress_get_category_layout('12'));
    }

    public function test_get_category_layout_falls_back_for_bad_meta() {
        Functions\when('get_term_meta')->justReturn('<script>');

        $this->assertSame('list', artpress_get_category_layout(12));
    }

    public function test_current_layout_on_category_archive() {
        Functions\when('is_category')->justReturn(true);
        Functions\when('get_queried_object')->justReturn(new \WP_Term(['term_id' => 7]));
        Functions\expect('get_term_meta')->once()->with(7, 'artpress_layout', true)->andReturn('list-text');

        $this->assertSame('list-text', artpress_current_archive_layout());
    }

    public function test_current_layout_defaults_outside_categories() {
        Functions\when('is_category')->justReturn(false);
        Functions\expect('get_term_meta')->never();

        $this->assertSame('list', artpress_current_archive_layout());
    }

    public function test_current_layout_is_filterable_and_resanitized() {
        Functions\when('is_category')->justReturn(false);
        Filters\expectApplied('artpress_archive_layout')->once()->with('list')->andReturn('grid');
        $this->assertSame('grid', artpress_current_archive_layout());
    }

    public function test_current_layout_rejects_bad_filter_value() {
        Functions\when('is_category')->justReturn(false);
        Filters\expectApplied('artpress_archive_layout')->andReturn('evil');
        $this->assertSame('list', artpress_current_archive_layout());
    }

    // ----- Saving -----

    private function post(string $layout, string $nonce = 'good'): void {
        $_POST['artpress_layout']                = $layout;
        $_POST['artpress_category_layout_nonce'] = $nonce;
    }

    public function test_save_ignores_requests_without_field() {
        Functions\expect('wp_verify_nonce')->never();
        Functions\expect('update_term_meta')->never();

        artpress_save_category_layout(5);
        $this->addToAssertionCount(1);
    }

    public function test_save_requires_valid_nonce() {
        $this->post('grid', 'bad');
        Functions\expect('wp_verify_nonce')->once()->with('bad', 'artpress_category_layout')->andReturn(false);
        Functions\expect('update_term_meta')->never();
        Functions\expect('delete_term_meta')->never();

        artpress_save_category_layout(5);
        $this->addToAssertionCount(1);
    }

    public function test_save_requires_capability() {
        $this->post('grid');
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('current_user_can')->once()->with('edit_term', 5)->andReturn(false);
        Functions\expect('update_term_meta')->never();

        artpress_save_category_layout(5);
        $this->addToAssertionCount(1);
    }

    public function test_save_stores_layout() {
        $this->post('grid-text');
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\expect('update_term_meta')->once()->with(5, 'artpress_layout', 'grid-text');

        artpress_save_category_layout(5);
        $this->addToAssertionCount(1);
    }

    public function test_save_deletes_meta_for_default_and_unknown_values() {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\expect('update_term_meta')->never();
        Functions\expect('delete_term_meta')->twice()->with(5, 'artpress_layout');

        $this->post('list');
        artpress_save_category_layout(5);
        $this->post('masonry');
        artpress_save_category_layout(5);
        $this->addToAssertionCount(1);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hooks_are_registered() {
        $this->assertNotFalse(has_action('created_category', 'artpress_save_category_layout'));
        $this->assertNotFalse(has_action('edited_category', 'artpress_save_category_layout'));
    }
}
