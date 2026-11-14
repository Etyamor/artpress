<?php

namespace ArtPress\Tests\Unit;

use ArtPress\Tests\TestCase;
use Brain\Monkey\Filters;
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

    private function readingTimeFor(string $content): string {
        Functions\expect('get_post_field')->with('post_content', null)->andReturn($content);
        Functions\when('wp_strip_all_tags')->alias('strip_tags');
        Functions\when('strip_shortcodes')->alias(static function ($c) {
            return preg_replace('/\[[^\]]*\]/', '', $c);
        });
        Functions\when('_n')->returnArg(1);

        return artpress_reading_time();
    }

    /**
     * @dataProvider readingTimeProvider
     */
    public function test_reading_time($words, $expected_minutes) {
        $content = '<p>' . str_repeat('word ', $words) . '</p>';

        $this->assertSame($expected_minutes . ' min', $this->readingTimeFor($content));
    }

    public function readingTimeProvider() {
        return [
            'empty post'  => [0, 1],
            'short post'  => [50, 1],
            'exactly 200' => [200, 1],
            'just over'   => [201, 2],
            'long post'   => [1500, 8],
        ];
    }

    public function test_reading_time_counts_cyrillic_words() {
        // 5 words × 300 = 1500 words → 8 min (was always "1 min").
        $content = '<!-- wp:paragraph --><p>' . str_repeat("Мистецтво завжди відображає п'ять епох. ", 300) . '</p><!-- /wp:paragraph -->';

        $this->assertSame('8 min', $this->readingTimeFor($content));
    }

    public function test_reading_time_ignores_markup_shortcodes_and_entities() {
        $content = '<figure><img src="a.jpg" alt="alt text here"></figure>[gallery ids="1,2,3"]'
            . '<p>' . str_repeat('word&nbsp;&mdash; ', 201) . '</p>';

        $this->assertSame('2 min', $this->readingTimeFor($content));
    }

    public function test_reading_speed_is_filterable() {
        Filters\expectApplied('artpress_reading_time_wpm')->andReturn(100);

        $this->assertSame('3 min', $this->readingTimeFor('<p>' . str_repeat('word ', 201) . '</p>'));
    }

    /**
     * @dataProvider wordCountProvider
     */
    public function test_count_words($text, $expected) {
        $this->assertSame($expected, artpress_count_words($text));
    }

    public function wordCountProvider() {
        return [
            'english'               => ['Art always reflects its era.', 5],
            'ukrainian'             => ['Мистецтво завжди відображає свою епоху.', 5],
            'apostrophes'           => ["п'ять пʼять п’ять don't", 4],
            'hyphenated'            => ['well-known будь-який', 2],
            'numbers'               => ['In 2026 there were 12 shows', 6],
            'punctuation only'      => [' — … ; ! ', 0],
            'empty'                 => ['', 0],
            'accents'               => ['Café naïve España', 3],
        ];
    }
}
