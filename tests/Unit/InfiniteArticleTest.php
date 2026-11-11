<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

class InfiniteArticleTest extends TestCase {

    /** Post IDs are unique per test because next-post lookups are cached statically. */
    private static $nextId = 1000;

    protected function set_up() {
        parent::set_up();
        $this->requireTheme('inc/infinite-article.php');
    }

    private function post(array $props = []): \WP_Post {
        $post = new \WP_Post($props + ['ID' => ++self::$nextId]);
        Functions\when('get_post')->justReturn($post);

        return $post;
    }

    /**
     * @dataProvider enabledProvider
     */
    public function test_infinite_enabled($option, $expected) {
        Functions\expect('get_option')->with('artpress_general', [])->andReturn($option);

        $this->assertSame($expected, artpress_infinite_enabled());
    }

    public function enabledProvider() {
        return [
            'option missing' => [[], false],
            'off'            => [['infinite_article' => false], false],
            'on'             => [['infinite_article' => true], true],
            'truthy string'  => [['infinite_article' => '1'], true],
        ];
    }

    public function test_query_var_is_registered() {
        $this->assertSame('artpress_infinite', ARTPRESS_INFINITE_QUERY_VAR);
    }

    public function test_next_post_id_is_zero_without_post() {
        Functions\when('get_post')->justReturn(null);

        $this->assertSame(0, artpress_infinite_next_post_id());
        $this->assertSame([], \WP_Query::$created);
    }

    public function test_next_post_id_is_zero_for_pages() {
        $this->post(['post_type' => 'page']);

        $this->assertSame(0, artpress_infinite_next_post_id());
        $this->assertSame([], \WP_Query::$created);
    }

    public function test_next_post_id_queries_the_next_older_post() {
        $post              = $this->post(['post_date' => '2026-05-01 10:00:00']);
        \WP_Query::$queue[] = ['posts' => [42]];

        $this->assertSame(42, artpress_infinite_next_post_id($post));

        $args = \WP_Query::$created[0];
        $this->assertSame('post', $args['post_type']);
        $this->assertSame('publish', $args['post_status']);
        $this->assertSame(1, $args['posts_per_page']);
        $this->assertSame([$post->ID], $args['post__not_in']);
        $this->assertSame('2026-05-01 10:00:00', $args['date_query'][0]['before']);
        $this->assertFalse($args['date_query'][0]['inclusive']);
        $this->assertSame('DESC', $args['order']);
        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['no_found_rows']);
    }

    public function test_next_post_id_is_zero_on_the_oldest_post() {
        $this->post();
        \WP_Query::$queue[] = ['posts' => []];

        $this->assertSame(0, artpress_infinite_next_post_id());
    }

    public function test_next_post_id_is_cached_per_post() {
        $post              = $this->post();
        \WP_Query::$queue[] = ['posts' => [7]];

        artpress_infinite_next_post_id($post);
        artpress_infinite_next_post_id($post);

        $this->assertCount(1, \WP_Query::$created);
    }

    public function test_next_post_id_is_filterable() {
        $post              = $this->post();
        \WP_Query::$queue[] = ['posts' => [7]];

        Filters\expectApplied('artpress_infinite_next_post_id')
            ->once()
            ->with(7, $post)
            ->andReturn('99');

        $this->assertSame(99, artpress_infinite_next_post_id($post));
    }

    public function test_next_url_is_empty_when_nothing_left() {
        $this->post();
        \WP_Query::$queue[] = ['posts' => []];
        Functions\expect('get_permalink')->never();

        $this->assertSame('', artpress_infinite_next_url());
    }

    public function test_next_url_is_permalink_of_next_post() {
        $this->post();
        \WP_Query::$queue[] = ['posts' => [5]];
        Functions\expect('get_permalink')->once()->with(5)->andReturn('https://example.com/next/');

        $this->assertSame('https://example.com/next/', artpress_infinite_next_url());
    }
}
