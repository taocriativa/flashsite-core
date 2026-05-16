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
