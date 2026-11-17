<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

class TemplateHelpersTest extends TestCase {

    protected function set_up() {
        parent::set_up();
        if (!defined('DAY_IN_SECONDS')) {
            define('DAY_IN_SECONDS', 86400);
        }
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('wp_parse_args')->alias(static function ($args, $defaults) {
            return array_merge($defaults, $args);
        });
        $this->requireTheme('inc/template-helpers.php');
    }

    // ----- "Updated" date -----

    /**
     * @dataProvider updatedProvider
     */
    public function test_show_updated_date($delta, $expected) {
        $this->assertSame($expected, artpress_show_updated_date(1000, 1000 + $delta));
    }

    public function updatedProvider() {
        return [
            'never edited'        => [0, false],
            'quick fix'           => [600, false],
            'just under a day'    => [86399, false],
            'a day later'         => [86400, true],
            'a week later'        => [7 * 86400, true],
            'modified before pub' => [-3600, false],
        ];
    }

    public function test_updated_threshold_is_filterable() {
        Filters\expectApplied('artpress_updated_threshold')->andReturn(3600);

        $this->assertTrue(artpress_show_updated_date(0, 3600));
    }

    // ----- Author byline -----

    public function test_author_archives_enabled_without_rank_math() {
        $this->assertTrue(artpress_author_archives_enabled());
    }

    public function test_author_archives_filterable() {
        Filters\expectApplied('artpress_author_archives_enabled')->with(true)->andReturn(false);

        $this->assertFalse(artpress_author_archives_enabled());
    }

    private function stubAuthor(string $name): void {
        Functions\when('get_the_author')->justReturn($name);
        Functions\when('get_the_author_meta')->justReturn(7);
        Functions\when('get_author_posts_url')->justReturn('https://site.test/author/editor/');
    }

    public function test_byline_links_to_author_archive() {
        $this->stubAuthor('Редакція <WorldEcho>');

        ob_start();
        artpress_post_author();
        $html = ob_get_clean();

        $this->assertStringContainsString('href="https://site.test/author/editor/" rel="author"', $html);
        $this->assertStringContainsString('Редакція &lt;WorldEcho&gt;', $html);
    }

    public function test_byline_unlinked_when_archives_disabled() {
        $this->stubAuthor('Editor');
        Filters\expectApplied('artpress_author_archives_enabled')->andReturn(false);

        ob_start();
        artpress_post_author();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('Editor', $html);
    }

    public function test_no_byline_without_author() {
        $this->stubAuthor('');

        ob_start();
        artpress_post_author();
        $this->assertSame('', ob_get_clean());
    }

    // ----- Breadcrumbs -----

    public function test_no_breadcrumbs_without_seo_plugin() {
        ob_start();
        artpress_breadcrumbs();
        $this->assertSame('', ob_get_clean());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_wraps_rank_math_breadcrumbs() {
        Functions\when('rank_math_the_breadcrumbs')->alias(static function () {
            echo '<nav aria-label="breadcrumbs" class="rank-math-breadcrumb"><p><a href="/">Головна</a></p></nav>';
        });

        ob_start();
        artpress_breadcrumbs('mb-4');
        $html = ob_get_clean();

        $this->assertStringStartsWith('<div class="artpress-breadcrumbs mb-4"><nav aria-label="breadcrumbs"', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_disabled_rank_math_breadcrumbs_leave_no_wrapper() {
        Functions\when('rank_math_the_breadcrumbs')->justReturn(null);

        ob_start();
        artpress_breadcrumbs();
        $this->assertSame('', ob_get_clean());
    }

    // ----- Thumbnail loading priority -----

    private function thumbnailAttrs(array $args): array {
        $captured = null;
        Functions\when('has_post_thumbnail')->justReturn(true);
        Functions\when('the_permalink')->justReturn(null);
        Functions\when('the_title_attribute')->justReturn(null);
        Functions\when('the_post_thumbnail')->alias(static function ($size, $attrs) use (&$captured) {
            $captured = $attrs;
        });

        ob_start();
        artpress_post_thumbnail($args);
        ob_end_clean();

        return $captured;
    }

    public function test_lazy_thumbnail() {
        $attrs = $this->thumbnailAttrs(['lazy' => true]);

        $this->assertSame('lazy', $attrs['loading']);
        $this->assertArrayNotHasKey('fetchpriority', $attrs);
    }

    public function test_priority_thumbnail_is_eager_and_high_priority() {
        $attrs = $this->thumbnailAttrs(['lazy' => true, 'priority' => true]);

        $this->assertFalse($attrs['loading']);
        $this->assertSame('high', $attrs['fetchpriority']);
    }
}
