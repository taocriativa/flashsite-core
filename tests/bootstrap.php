<?php
declare(strict_types=1);

$GLOBALS['flashsite_test_options'] = [];
$GLOBALS['flashsite_test_roles'] = [];

final class FlashSiteTestRole
{
    /** @var array<string, bool> */
    public array $caps = [];

    /** @param array<string, bool> $caps */
    public function __construct(array $caps = [])
    {
        $this->caps = $caps;
    }

    public function add_cap(string $cap): void
    {
        $this->caps[$cap] = true;
    }

    public function remove_cap(string $cap): void
    {
        unset($this->caps[$cap]);
    }
}


$GLOBALS['flashsite_test_hooks'] = [];
$GLOBALS['flashsite_test_rest_routes'] = [];
$GLOBALS['flashsite_test_current_user_caps'] = [];
$GLOBALS['flashsite_test_shortcodes'] = [];
$GLOBALS['flashsite_test_styles'] = [];
$GLOBALS['flashsite_test_scripts'] = [];
$GLOBALS['flashsite_test_enqueued_styles'] = [];
$GLOBALS['flashsite_test_enqueued_scripts'] = [];

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['flashsite_test_hooks'][$hook][] = $callback;
        return true;
    }
}


if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): bool
    {
        $GLOBALS['flashsite_test_shortcodes'][$tag] = $callback;
        return true;
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts(array $pairs, array $atts, string $shortcode = ''): array
    {
        return array_merge($pairs, $atts);
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class(string $class): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $class) ?? '';
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, ...$args): void
    {
        foreach (($GLOBALS['flashsite_test_hooks'][$hook] ?? []) as $callback) {
            $callback(...$args);
        }
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args, bool $override = false): bool
    {
        $GLOBALS['flashsite_test_rest_routes'][$namespace . $route] = $args;
        return true;
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response(mixed $response): mixed
    {
        return $response;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        return !empty($GLOBALS['flashsite_test_current_user_caps'][$capability]);
    }
}

if (!function_exists('__return_true')) {
    function __return_true(): bool
    {
        return true;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(
            public string $code,
            public string $message,
            public array $data = []
        ) {}
    }
}

final class FlashSiteTestRequest
{
    public function __construct(private array $params = [], private array $json = []) {}

    public function get_param(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    public function get_params(): array
    {
        return $this->params;
    }

    public function get_json_params(): array
    {
        return $this->json;
    }
}

function flashsite_reset_test_state(): void
{
    $GLOBALS['flashsite_test_options'] = [];
    $GLOBALS['flashsite_test_roles'] = [
        'administrator' => new FlashSiteTestRole(['read' => true]),
    ];
    $GLOBALS['flashsite_test_hooks'] = [];
    $GLOBALS['flashsite_test_rest_routes'] = [];
    $GLOBALS['flashsite_test_current_user_caps'] = [];
    $GLOBALS['flashsite_test_post_types'] = [];
    $GLOBALS['flashsite_test_taxonomies'] = [];
    $GLOBALS['flashsite_test_post_meta_registry'] = [];
    $GLOBALS['flashsite_test_post_meta'] = [];
    $GLOBALS['flashsite_test_filters'] = [];
    $GLOBALS['flashsite_test_rewrite_flushes'] = 0;
    $GLOBALS['flashsite_test_terms'] = [];
    $GLOBALS['flashsite_test_object_terms'] = [];
    $GLOBALS['flashsite_test_thumbnails'] = [];
$GLOBALS['flashsite_test_shortcodes'] = [];
$GLOBALS['flashsite_test_styles'] = [];
$GLOBALS['flashsite_test_scripts'] = [];
$GLOBALS['flashsite_test_enqueued_styles'] = [];
$GLOBALS['flashsite_test_enqueued_scripts'] = [];
}


if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return $GLOBALS['flashsite_test_options'][$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, mixed $autoload = null): bool
    {
        $GLOBALS['flashsite_test_options'][$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        unset($GLOBALS['flashsite_test_options'][$option]);
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? $value;
        return trim($value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $value): string
    {
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return trim($value);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $email): string
    {
        $email = trim($email);
        return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string
    {
        $url = trim($url);
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $value): string
    {
        return trim($value);
    }
}

if (!function_exists('absint')) {
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color(string $color): ?string
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color) === 1 ? $color : null;
    }
}

if (!function_exists('get_role')) {
    function get_role(string $role): ?FlashSiteTestRole
    {
        return $GLOBALS['flashsite_test_roles'][$role] ?? null;
    }
}

if (!function_exists('add_role')) {
    function add_role(string $role, string $display_name, array $caps = []): FlashSiteTestRole
    {
        $obj = new FlashSiteTestRole($caps);
        $GLOBALS['flashsite_test_roles'][$role] = $obj;
        return $obj;
    }
}


if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url(int $attachmentId, string $size = 'full'): string
    {
        return $attachmentId > 0 ? 'https://cdn.flashsite.test/media/' . $attachmentId . '-' . $size . '.png' : '';
    }
}

if (!function_exists('wp_get_attachment_metadata')) {
    function wp_get_attachment_metadata(int $attachmentId): array
    {
        return ['width' => 800, 'height' => 400];
    }
}

if (!function_exists('get_attachment_link')) {
    function get_attachment_link(int $attachmentId): string
    {
        return $attachmentId > 0 ? 'https://cdn.flashsite.test/attachment/' . $attachmentId : '';
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $value): string
    {
        return rtrim($value, '/\\') . DIRECTORY_SEPARATOR;
    }
}


if (!function_exists('wp_register_style')) {
    function wp_register_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool
    {
        $GLOBALS['flashsite_test_styles'][$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'media' => $media, 'registered' => true];
        return true;
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $in_footer = false): bool
    {
        $GLOBALS['flashsite_test_scripts'][$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'in_footer' => $in_footer, 'registered' => true];
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle): bool
    {
        $GLOBALS['flashsite_test_enqueued_styles'][] = $handle;
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle): bool
    {
        $GLOBALS['flashsite_test_enqueued_scripts'][] = $handle;
        return true;
    }
}

if (!function_exists('wp_style_is')) {
    function wp_style_is(string $handle, string $status = 'enqueued'): bool
    {
        if ($status === 'registered') {
            return !empty($GLOBALS['flashsite_test_styles'][$handle]['registered']);
        }
        return in_array($handle, $GLOBALS['flashsite_test_enqueued_styles'], true);
    }
}

if (!function_exists('wp_enqueue_media')) {
    function wp_enqueue_media(): void
    {
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen(): object
    {
        return (object) ['id' => $GLOBALS['flashsite_test_current_screen_id'] ?? ''];
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback): bool
    {
        $GLOBALS['flashsite_test_submenus'][] = compact('parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug');
        return true;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string
    {
        return 'https://example.test/wp-content/plugins/flashsite-core/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        return 'flashsite-core/flashsite-core.php';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return dirname($file) . DIRECTORY_SEPARATOR;
    }
}

if (!defined('FLASHSITE_CORE_VERSION')) {
    define('FLASHSITE_CORE_VERSION', '1.4.0');
}

if (!defined('FLASHSITE_DATA_VERSION')) {
    define('FLASHSITE_DATA_VERSION', '1.4.0');
}

$autoload = dirname(__DIR__) . '/flashsite-core.php';
if (!defined('FLASHSITE_CORE_PATH')) {
    define('FLASHSITE_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (!defined('FLASHSITE_CORE_URL')) {
    define('FLASHSITE_CORE_URL', 'https://example.test/wp-content/plugins/flashsite-core/');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'FlashSite\\Core\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

flashsite_reset_test_state();

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $value): string|false
    {
        return json_encode($value);
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return '/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location): bool
    {
        $GLOBALS['flashsite_test_last_redirect'] = $location;
        return true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = ''): never
    {
        throw new RuntimeException($message);
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer(string $action = '', string $query_arg = '_wpnonce'): bool
    {
        return true;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field(string $action = '', string $name = '_wpnonce'): string
    {
        return '';
    }
}

if (!function_exists('checked')) {
    function checked(bool $checked, bool $current = true): string
    {
        return $checked === $current ? 'checked' : '';
    }
}

if (!function_exists('disabled')) {
    function disabled(bool $disabled, bool $current = true): string
    {
        return $disabled === $current ? 'disabled' : '';
    }
}


// --- 2.6.0: stubs para o módulo Coleções ---
$GLOBALS['flashsite_test_post_types'] = [];
$GLOBALS['flashsite_test_taxonomies'] = [];
$GLOBALS['flashsite_test_post_meta_registry'] = [];
$GLOBALS['flashsite_test_post_meta'] = [];
$GLOBALS['flashsite_test_filters'] = [];
$GLOBALS['flashsite_test_rewrite_flushes'] = 0;

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['flashsite_test_filters'][$hook][] = $callback;
        return true;
    }
}

if (!function_exists('register_post_type')) {
    function register_post_type(string $postType, array $args = []): object
    {
        $GLOBALS['flashsite_test_post_types'][$postType] = $args;
        return (object) ['name' => $postType];
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy(string $taxonomy, array|string $objectType, array $args = []): object
    {
        $GLOBALS['flashsite_test_taxonomies'][$taxonomy] = ['object_type' => (array) $objectType, 'args' => $args];
        return (object) ['name' => $taxonomy];
    }
}

if (!function_exists('register_post_meta')) {
    function register_post_meta(string $postType, string $metaKey, array $args): bool
    {
        $GLOBALS['flashsite_test_post_meta_registry'][$postType][$metaKey] = $args;
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key = '', bool $single = false): mixed
    {
        $value = $GLOBALS['flashsite_test_post_meta'][$postId][$key] ?? null;
        if ($value === null) {
            return $single ? '' : [];
        }
        return $single ? $value : [$value];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): bool
    {
        $GLOBALS['flashsite_test_post_meta'][$postId][$key] = $value;
        return true;
    }
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(bool $hard = true): void
    {
        $GLOBALS['flashsite_test_rewrite_flushes']++;
    }
}

$GLOBALS['flashsite_test_terms'] = [];
$GLOBALS['flashsite_test_object_terms'] = [];
$GLOBALS['flashsite_test_thumbnails'] = [];

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return !empty($GLOBALS['flashsite_test_is_admin']);
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $postId, string $key): bool
    {
        unset($GLOBALS['flashsite_test_post_meta'][$postId][$key]);
        return true;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return false;
    }
}

/** Store de termos: [taxonomy][term_id] => ['term_id', 'slug', 'name', 'parent'] */
if (!function_exists('term_exists')) {
    function term_exists(int|string $term, string $taxonomy = '', ?int $parent = null): mixed
    {
        foreach ($GLOBALS['flashsite_test_terms'][$taxonomy] ?? [] as $t) {
            if ((is_int($term) && $t['term_id'] === $term) || (is_string($term) && ($t['slug'] === $term || $t['name'] === $term))) {
                if ($parent !== null && $t['parent'] !== $parent) {
                    continue;
                }
                return ['term_id' => $t['term_id'], 'term_taxonomy_id' => $t['term_id']];
            }
        }
        return null;
    }
}

if (!function_exists('wp_insert_term')) {
    function wp_insert_term(string $name, string $taxonomy, array $args = []): array
    {
        $GLOBALS['flashsite_test_term_seq'] = ($GLOBALS['flashsite_test_term_seq'] ?? 100) + 1;
        $id = $GLOBALS['flashsite_test_term_seq'];
        $slug = (string) ($args['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)));
        $GLOBALS['flashsite_test_terms'][$taxonomy][$id] = ['term_id' => $id, 'slug' => $slug, 'name' => $name, 'parent' => (int) ($args['parent'] ?? 0)];
        return ['term_id' => $id, 'term_taxonomy_id' => $id];
    }
}

if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms(int $objectId, array|int|string $terms, string $taxonomy, bool $append = false): array
    {
        $ids = array_map('intval', (array) $terms);
        $GLOBALS['flashsite_test_object_terms'][$objectId][$taxonomy] = $ids;
        return $ids;
    }
}

if (!function_exists('wp_get_object_terms')) {
    function wp_get_object_terms(int $objectId, string $taxonomy, array $args = []): array
    {
        $ids = $GLOBALS['flashsite_test_object_terms'][$objectId][$taxonomy] ?? [];
        $field = $args['fields'] ?? 'ids';
        $out = [];
        foreach ($ids as $id) {
            $t = $GLOBALS['flashsite_test_terms'][$taxonomy][$id] ?? null;
            if ($t === null) {
                continue;
            }
            $out[] = match ($field) {
                'slugs' => $t['slug'],
                'names' => $t['name'],
                default => $t['term_id'],
            };
        }
        return $out;
    }
}

if (!function_exists('set_post_thumbnail')) {
    function set_post_thumbnail(int $postId, int $thumbnailId): bool
    {
        $GLOBALS['flashsite_test_thumbnails'][$postId] = $thumbnailId;
        return true;
    }
}

if (!function_exists('delete_post_thumbnail')) {
    function delete_post_thumbnail(int $postId): bool
    {
        unset($GLOBALS['flashsite_test_thumbnails'][$postId]);
        return true;
    }
}
