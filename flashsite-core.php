<?php
/**
 * Plugin Name: FlashSite Core
 * Plugin URI: https://www.flashsite.pt
 * Description: Núcleo operacional modular da Flash Site para provisionamento e gestão de sites WordPress.
 * Version: 2.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Flash Site
 * Text Domain: flashsite-core
 * Domain Path: /languages
 */
declare(strict_types=1);

if (! defined('ABSPATH')) { exit; }

define('FLASHSITE_CORE_VERSION', '2.4.0');
define('FLASHSITE_DATA_VERSION', '2.4.0');
define('FLASHSITE_CORE_FILE', __FILE__);
define('FLASHSITE_CORE_BASENAME', plugin_basename(__FILE__));
define('FLASHSITE_CORE_PATH', plugin_dir_path(__FILE__));
define('FLASHSITE_CORE_URL', plugin_dir_url(__FILE__));

define('FLASHSITE_CORE_EXPECTED_SLUG', 'flashsite-core');
define('FLASHSITE_CORE_HAS_SHELL', true);
if (defined('FLASHSITE_CORE_ACTIVE')) { return; }
define('FLASHSITE_CORE_ACTIVE', true);

$autoload = FLASHSITE_CORE_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'FlashSite\\Core\\';
        $baseDir = FLASHSITE_CORE_PATH . 'src/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) { return; }
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) { require_once $file; }
    });
}


if (!function_exists('flashsite_core_version')) {
    function flashsite_core_version(): string
    {
        return defined('FLASHSITE_CORE_VERSION') ? FLASHSITE_CORE_VERSION : '0.0.0';
    }
}



if (!function_exists('flashsite_core_has_shell')) {
    function flashsite_core_has_shell(): bool
    {
        return defined('FLASHSITE_CORE_HAS_SHELL') && FLASHSITE_CORE_HAS_SHELL === true;
    }
}

if (!function_exists('flashsite_core')) {
    function flashsite_core(): ?\FlashSite\Core\Core\Application
    {
        return \FlashSite\Core\Core\Plugin::instance()->application();
    }
}

if (!function_exists('flashsite_get_business_data')) {
    function flashsite_get_business_data(?string $path = null, $default = null)
    {
        $app = flashsite_core();
        if ($app === null) {
            return $default;
        }

        try {
            $repo = $app->container()->make(\FlashSite\Core\Domain\Business\BusinessRepository::class);
            $data = $repo->getProfile()->toArray();
        } catch (\Throwable $e) {
            return $default;
        }

        if ($path === null || $path === '') {
            return $data;
        }

        $segments = explode('.', $path);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('flashsite_get_brand_identity')) {
    function flashsite_get_brand_identity(?string $path = null, $default = null)
    {
        $branding = [
            'logo_light_id' => (int) flashsite_get_business_data('branding.logo_light_id', 0),
            'logo_dark_id' => (int) flashsite_get_business_data('branding.logo_dark_id', 0),
            'logo_svg_id' => 0,
        ];

        if ($path === null || $path === '') {
            return $branding;
        }

        return $branding[$path] ?? $default;
    }
}

if (is_admin()) {
    (new \FlashSite\Core\Core\UpdateChecker(
        FLASHSITE_CORE_BASENAME,
        FLASHSITE_CORE_EXPECTED_SLUG,
        FLASHSITE_CORE_VERSION
    ))->register();
}

register_activation_hook(__FILE__, static function (): void {
    FlashSite\Core\Core\Plugin::instance()->activate();
});
register_deactivation_hook(__FILE__, static function (): void {
    FlashSite\Core\Core\Plugin::instance()->deactivate();
});
add_action('plugins_loaded', static function (): void {
    FlashSite\Core\Core\Plugin::instance()->boot();
});
