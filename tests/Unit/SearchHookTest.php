<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

class SearchHookTest extends TestCase {

    /** @var callable */
    private $filter;

    protected function set_up() {
        parent::set_up();
        $GLOBALS['wpdb'] = new \Artpress_Fake_Wpdb();
        Functions\when('is_admin')->justReturn(false);
        $this->filter = $this->captureHook('inc/admin/widgets/search/hooks.php', 'posts_search');
    }

    protected function tear_down() {
        unset($GLOBALS['wpdb']);
        parent::tear_down();
    }

    private function query(bool $is_search, string $term = 'art') {
        $query = Mockery::mock();
        $query->shouldReceive('is_search')->andReturn($is_search);
        $query->shouldReceive('get')->with('s')->andReturn($term);

        return $query;
    }

    private function options(array $opts): void {
        Functions\when('get_option')->justReturn($opts);
    }

    public function test_leaves_non_search_queries_alone() {
        $this->options(['search_title' => true]);

        $this->assertSame('ORIGINAL', ($this->filter)('ORIGINAL', $this->query(false)));
    }

    public function test_leaves_admin_searches_alone() {
        Functions\when('is_admin')->justReturn(true);
        $this->options(['search_title' => true]);

        $this->assertSame('ORIGINAL', ($this->filter)('ORIGINAL', $this->query(true)));
    }

    public function test_uses_default_search_when_no_column_selected() {
        $this->options([]);

        $this->assertSame('ORIGINAL', ($this->filter)('ORIGINAL', $this->query(true)));
    }

    public function test_empty_term_falls_back_to_default() {
        $this->options(['search_title' => true]);

        $this->assertSame('ORIGINAL', ($this->filter)('ORIGINAL', $this->query(true, '')));
    }

    public function test_builds_like_clause_for_selected_columns() {
        $this->options(['search_title' => true, 'search_slug' => true, 'search_excerpt' => true]);

        $this->assertSame(
            " AND (wp_posts.post_title LIKE '%art%' OR wp_posts.post_name LIKE '%art%') ",
            ($this->filter)('ORIGINAL', $this->query(true))
        );
    }

    public function test_escapes_like_wildcards_in_term() {
        $this->options(['search_content' => true]);

        $this->assertSame(
            " AND (wp_posts.post_content LIKE '%100\\%\\_off%') ",
            ($this->filter)('ORIGINAL', $this->query(true, '100%_off'))
        );
    }
}
