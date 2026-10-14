<?php
declare(strict_types=1);

/**
 * Stubs de posts, consultas e Abilities API para os testes das Abilities (@since 3.5.0).
 * Tudo protegido por function_exists/class_exists: não interfere com os restantes testes.
 */

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public string $post_name = 'item-de-teste';
        public string $post_status = 'publish';
        public string $post_type = 'post';
        public string $post_title = '';
        public string $post_content = '';
        public string $post_modified_gmt = '2026-10-14 10:00:00';
        public int $post_author = 1;
        public function __construct(public int $ID = 0) {}
    }
}

if (! class_exists('WP_Query')) {
    class WP_Query
    {
        /** @var list<WP_Post> */
        public array $posts = [];
        public int $found_posts = 0;
        public int $max_num_pages = 0;

        public function __construct(array $args = [])
        {
            $GLOBALS['flashsite_test_last_query'] = $args;
            $statuses = (array) ($args['post_status'] ?? 'publish');
            $all = [];
            foreach ($GLOBALS['flashsite_test_posts'] ?? [] as $post) {
                if ($post->post_type !== ($args['post_type'] ?? '')) {
                    continue;
                }
                if (! in_array($post->post_status, $statuses, true)) {
                    continue;
                }
                if (isset($args['author']) && $post->post_author !== (int) $args['author']) {
                    continue;
                }
                if (! empty($args['s']) && stripos($post->post_title, (string) $args['s']) === false) {
                    continue;
                }
                $all[] = $post;
            }
            $per = max(1, (int) ($args['posts_per_page'] ?? 10));
            $page = max(1, (int) ($args['paged'] ?? 1));
            $this->found_posts = count($all);
            $this->max_num_pages = (int) ceil(count($all) / $per);
            $this->posts = array_slice($all, ($page - 1) * $per, $per);
        }
    }
}

function flashsite_reset_post_stubs(): void
{
    $GLOBALS['flashsite_test_posts'] = [];
    $GLOBALS['flashsite_test_post_seq'] = 1000;
    $GLOBALS['flashsite_test_user_id'] = 1;
    $GLOBALS['flashsite_test_last_query'] = [];
    $GLOBALS['flashsite_test_registered_abilities'] = [];
    $GLOBALS['flashsite_test_ability_categories'] = [];
}
flashsite_reset_post_stubs();

if (! function_exists('get_current_user_id')) {
    function get_current_user_id(): int { return (int) ($GLOBALS['flashsite_test_user_id'] ?? 1); }
}
if (! function_exists('get_post')) {
    function get_post(int $id = 0): ?WP_Post { return $GLOBALS['flashsite_test_posts'][$id] ?? null; }
}
if (! function_exists('get_post_type')) {
    function get_post_type(int $id = 0): string|false { return isset($GLOBALS['flashsite_test_posts'][$id]) ? $GLOBALS['flashsite_test_posts'][$id]->post_type : false; }
}
if (! function_exists('sanitize_title')) {
    function sanitize_title(string $title): string { return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-'); }
}
if (! function_exists('wp_insert_post')) {
    function wp_insert_post(array $postarr, bool $wpError = false): int|WP_Error
    {
        $GLOBALS['flashsite_test_post_seq']++;
        $post = new WP_Post($GLOBALS['flashsite_test_post_seq']);
        $post->post_status = 'draft';
        $post->post_name = '';
        foreach ($postarr as $key => $value) {
            if (property_exists($post, $key)) {
                $post->$key = $value;
            }
        }
        if ($post->post_name === '') {
            $post->post_name = sanitize_title($post->post_title);
        }
        $GLOBALS['flashsite_test_posts'][$post->ID] = $post;
        return $post->ID;
    }
}
if (! function_exists('wp_update_post')) {
    function wp_update_post(array $postarr, bool $wpError = false): int|WP_Error
    {
        $post = $GLOBALS['flashsite_test_posts'][(int) ($postarr['ID'] ?? 0)] ?? null;
        if ($post === null) {
            return 0;
        }
        foreach ($postarr as $key => $value) {
            if ($key !== 'ID' && property_exists($post, $key)) {
                $post->$key = $value;
            }
        }
        return $post->ID;
    }
}
if (! function_exists('wp_trash_post')) {
    function wp_trash_post(int $id): WP_Post|false|null
    {
        $post = $GLOBALS['flashsite_test_posts'][$id] ?? null;
        if ($post === null) {
            return false;
        }
        $post->post_status = 'trash';
        return $post;
    }
}

// Abilities API simulada (WordPress 6.9+). Guarda o que for registado para os testes inspecionarem.
function flashsite_enable_abilities_api_stub(): void
{
    if (! function_exists('wp_register_ability_category')) {
        eval('function wp_register_ability_category(string $slug, array $args) { $GLOBALS["flashsite_test_ability_categories"][$slug] = $args; return true; }');
        eval('function wp_register_ability(string $name, array $args) { $GLOBALS["flashsite_test_registered_abilities"][$name] = $args; return true; }');
    }
}
