<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class PluginChecker
{
    public function isActive(string $pluginBasename): bool
    {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($pluginBasename);
    }

    public function isInstalled(string $pluginBasename): bool
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        return array_key_exists($pluginBasename, $plugins);
    }

    public function getVersion(string $pluginBasename): ?string
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        return isset($plugins[$pluginBasename]['Version']) ? (string) $plugins[$pluginBasename]['Version'] : null;
    }

    public function meetsMinVersion(string $pluginBasename, ?string $minVersion): bool
    {
        if ($minVersion === null) {
            return true;
        }
        $installedVersion = $this->getVersion($pluginBasename);
        if ($installedVersion === null) {
            return false;
        }
        return version_compare($installedVersion, $minVersion, '>=');
    }
}
