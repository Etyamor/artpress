<?php
/**
 * Minimal stand-ins for WordPress core classes used by the theme.
 */

// phpcs:disable

class WP_Post {
    public $ID = 0;
    public $post_type = 'post';
    public $post_date = '2026-01-01 00:00:00';

    public function __construct(array $props = []) {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }
}

class WP_Term {
    public $term_id = 0;
    public $name = '';
    public $count = 0;

    public function __construct(array $props = []) {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }
}

/**
 * Records every query built and hands back queued results.
 */
class WP_Query {
    /** @var array<int, array> Args of every WP_Query constructed, in order. */
    public static $created = [];

    /** @var array<int, array{posts?: array, found_posts?: int}> Results for upcoming queries. */
    public static $queue = [];

    public $query_vars;
    public $posts = [];
    public $post_count = 0;
    public $found_posts = 0;
    public $max_num_pages = 0;

    public function __construct($args = []) {
        $this->query_vars = $args;
        self::$created[]  = $args;

        $result            = array_shift(self::$queue) ?? [];
        $this->posts       = $result['posts'] ?? [];
        $this->post_count  = count($this->posts);
        $this->found_posts = $result['found_posts'] ?? $this->post_count;
    }

    public static function reset(): void {
        self::$created = [];
        self::$queue   = [];
    }
}

/**
 * $wpdb double covering what the search filter uses.
 */
class Artpress_Fake_Wpdb {
    public $posts = 'wp_posts';

    public function esc_like($text) {
        return addcslashes($text, '_%\\');
    }

    public function prepare($query, ...$args) {
        $query = str_replace('%s', "'%s'", $query);

        return vsprintf($query, $args);
    }
}

/**
 * PHPMailer double: records what the SMTP hook configures.
 */
class Artpress_Fake_PHPMailer {
    public $Host = '';
    public $Port = 25;
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $From = 'wordpress@example.com';
    public $FromName = 'WordPress';
    public $isSmtp = false;
    public $replyTos = ['old@example.com'];

    public function isSMTP() {
        $this->isSmtp = true;
    }

    public function clearReplyTos() {
        $this->replyTos = [];
    }

    public function addReplyTo($address) {
        $this->replyTos[] = $address;
    }
}

/**
 * Thrown by the wp_send_json_* doubles so handlers stop like they would after die().
 */
class Artpress_Json_Response extends Exception {
    public $success;
    public $data;
    public $status;

    public function __construct($success, $data, $status = null) {
        parent::__construct('json response');
        $this->success = $success;
        $this->data    = $data;
        $this->status  = $status;
    }
}
