<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * render_block overrides for the core Categories and Tag Cloud blocks.
 */
class WidgetHooksTest extends TestCase {

    protected function set_up() {
        parent::set_up();
        Functions\stubEscapeFunctions();
        Functions\when('get_category_link')->alias(static function ($term) {
            return 'https://example.com/category/' . $term->term_id . '/';
        });
        Functions\when('get_tag_link')->alias(static function ($term) {
            return 'https://example.com/tag/' . $term->term_id . '/';
        });
    }

    private function categoriesFilter(): callable {
        return $this->captureHook('inc/admin/widgets/categories/hooks.php', 'render_block');
    }

    private function tagCloudFilter(): callable {
        return $this->captureHook('inc/admin/widgets/tag-cloud/hooks.php', 'render_block');
    }

    // ----- Categories -----

    public function test_categories_ignores_other_blocks() {
        Functions\expect('get_option')->never();

        $this->assertSame('<p>x</p>', ($this->categoriesFilter())('<p>x</p>', ['blockName' => 'core/paragraph']));
    }

    public function test_categories_untouched_without_limit() {
        Functions\when('get_option')->justReturn(['category_limit' => '']);
        Functions\expect('get_categories')->never();

        $this->assertSame('ORIGINAL', ($this->categoriesFilter())('ORIGINAL', ['blockName' => 'core/categories']));
    }

    public function test_categories_renders_limited_list() {
        Functions\when('get_option')->justReturn(['category_limit' => 2, 'category_orderby' => 'count']);
        Functions\expect('get_categories')
            ->once()
            ->with(['orderby' => 'count', 'order' => 'DESC', 'number' => 2])
            ->andReturn([
                new \WP_Term(['term_id' => 3, 'name' => 'Art & <Design>', 'count' => 10]),
                new \WP_Term(['term_id' => 4, 'name' => 'News', 'count' => 4]),
            ]);
        Functions\when('get_queried_object')->justReturn(new \WP_Term(['term_id' => 4]));

        $html = ($this->categoriesFilter())('ORIGINAL', [
            'blockName' => 'core/categories',
            'attrs'     => ['showPostCounts' => true],
        ]);

        $this->assertStringStartsWith('<ul class="wp-block-categories-list wp-block-categories">', $html);
        $this->assertStringContainsString('Art &amp; &lt;Design&gt;', $html, 'Names must be escaped.');
        $this->assertStringContainsString('<li class="cat-item cat-item-4 current-cat">', $html);
        $this->assertStringContainsString('<span class="wp-block-categories__post-count">(10)</span>', $html);
        $this->assertStringContainsString('href="https://example.com/category/3/"', $html);
    }

    public function test_categories_falls_back_when_none_found() {
        Functions\when('get_option')->justReturn(['category_limit' => 5]);
        Functions\when('get_categories')->justReturn([]);

        $this->assertSame('ORIGINAL', ($this->categoriesFilter())('ORIGINAL', ['blockName' => 'core/categories']));
    }

    // ----- Tag cloud -----

    public function test_tag_cloud_ignores_other_blocks() {
        $this->assertSame('x', ($this->tagCloudFilter())('x', ['blockName' => 'core/categories']));
    }

    public function test_tag_cloud_without_limit_fetches_all() {
        Functions\when('get_option')->justReturn([]);
        Functions\when('is_tag')->justReturn(false);
        Functions\expect('get_tags')
            ->once()
            ->with(['orderby' => 'name', 'order' => 'ASC'])
            ->andReturn([new \WP_Term(['term_id' => 9, 'name' => 'Paint'])]);

        $this->assertSame(
            '<p class="wp-block-tag-cloud"><a href="https://example.com/tag/9/" class="tag-cloud-link tag-link-9">Paint</a></p>',
            ($this->tagCloudFilter())('ORIGINAL', ['blockName' => 'core/tag-cloud'])
        );
    }

    public function test_tag_cloud_with_limit_marks_current_tag() {
        Functions\when('get_option')->justReturn(['tag_cloud_limit' => 1, 'tag_cloud_orderby' => 'count']);
        Functions\when('is_tag')->justReturn(true);
        Functions\when('get_queried_object')->justReturn(new \WP_Term(['term_id' => 9]));
        Functions\expect('get_tags')
            ->once()
            ->with(['orderby' => 'count', 'order' => 'DESC', 'number' => 1])
            ->andReturn([new \WP_Term(['term_id' => 9, 'name' => 'Paint'])]);

        $html = ($this->tagCloudFilter())('ORIGINAL', ['blockName' => 'core/tag-cloud']);

        $this->assertStringContainsString('class="tag-cloud-link tag-link-9 current-tag"', $html);
    }

    public function test_tag_cloud_falls_back_when_empty() {
        Functions\when('get_option')->justReturn([]);
        Functions\when('get_tags')->justReturn([]);

        $this->assertSame('ORIGINAL', ($this->tagCloudFilter())('ORIGINAL', ['blockName' => 'core/tag-cloud']));
    }
}
