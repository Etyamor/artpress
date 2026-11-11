<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Functions;

class QueriesAndSetupTest extends TestCase {

    protected function set_up() {
        parent::set_up();
        $this->requireTheme('inc/queries.php');
        $this->requireTheme('inc/setup.php');
    }

    protected function tear_down() {
        unset($GLOBALS['wp_query']);
        parent::tear_down();
    }

    public function test_hero_query_fetches_hero_count_ignoring_sticky() {
        artpress_get_hero_query();

        $this->assertSame(['posts_per_page' => 9, 'ignore_sticky_posts' => true], \WP_Query::$created[0]);
    }

    /**
     * @dataProvider homePageProvider
     */
    public function test_home_query_offsets_past_hero($paged, $per_page, $found, $offset, $max_pages) {
        $original           = new \stdClass();
        $GLOBALS['wp_query'] = $original;
        Functions\when('get_option')->justReturn($per_page);
        Functions\when('get_query_var')->justReturn($paged);
        \WP_Query::$queue[] = ['found_posts' => $found];

        $returned = artpress_setup_home_query();

        $this->assertSame($original, $returned, 'Must hand back the original query for restoring.');
        $args = \WP_Query::$created[0];
        $this->assertSame($offset, $args['offset']);
        $this->assertSame($per_page, $args['posts_per_page']);
        $this->assertSame(max(1, $paged), $args['paged']);
        $this->assertSame(max(0, $found - 9), $GLOBALS['wp_query']->found_posts);
        $this->assertEquals($max_pages, $GLOBALS['wp_query']->max_num_pages);
    }

    public function homePageProvider() {
        return [
            // paged, per_page, found, offset, max_pages
            'page 0 (front)' => [0, 10, 39, 9, 3],
            'page 1'         => [1, 10, 39, 9, 3],
            'page 3'         => [3, 10, 39, 29, 3],
            'fewer than hero' => [1, 10, 5, 9, 0],
        ];
    }

    public function test_restore_query_puts_original_back() {
        $original           = new \stdClass();
        $GLOBALS['wp_query'] = new \WP_Query();
        Functions\expect('wp_reset_postdata')->once();

        artpress_restore_query($original);

        $this->assertSame($original, $GLOBALS['wp_query']);
    }

    /**
     * @dataProvider readingTimeProvider
     */
    public function test_reading_time($words, $expected_minutes) {
        $content = '<p>' . str_repeat('word ', $words) . '</p>';
        Functions\expect('get_post_field')->with('post_content', null)->andReturn($content);
        Functions\when('wp_strip_all_tags')->alias('strip_tags');
        Functions\when('_n')->returnArg(1);

        $this->assertSame($expected_minutes . ' min', artpress_reading_time());
    }

    public function readingTimeProvider() {
        return [
            'empty post'  => [0, 1],
            'short post'  => [50, 1],
            'exactly 200' => [200, 1],
            'just over'   => [201, 2],
            'long read'   => [1000, 5],
        ];
    }
}
