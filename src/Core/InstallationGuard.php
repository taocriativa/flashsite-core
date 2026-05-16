<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class InstallationGuard
{
    /** @var array<string,mixed>|null */
    private static ?array $status = null;

    /** @return string[] */
    private static function officialPluginBasenames(): array
    {
        return [
            'flashsite-core/flashsite-core.php',
            'flashsite-design/flashsite-design.php',
            'flashsite-crm/flashsite-crm.php',
        ];
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $expectedSlug = defined('FLASHSITE_CORE_EXPECTED_SLUG') ? (string) FLASHSITE_CORE_EXPECTED_SLUG : 'flashsite-core';
        $currentDir = defined('FLASHSITE_CORE_PATH') ? basename(rtrim((string) FLASHSITE_CORE_PATH, '/\\')) : $expectedSlug;
        $currentBasename = defined('FLASHSITE_CORE_BASENAME') ? (string) FLASHSITE_CORE_BASENAME : 'flashsite-core/flashsite-core.php';
        $duplicates = [];

        if (! defined('FLASHSITE_CORE_BASENAME') && ! defined('ABSPATH')) {
            $currentDir = $expectedSlug;
        }

        if (! function_exists('get_plugins')) {
            $rootPath = defined('ABSPATH') ? (string) constant('ABSPATH') : '';
            $pluginFile = $rootPath . 'wp-admin/includes/plugin.php';
            if ($rootPath !== '' && file_exists($pluginFile)) {
                require_once $pluginFile;
            }
        }

        if (function_exists('get_plugins')) {
            $plugins = get_plugins();
            foreach ($plugins as $pluginFile => $headers) {
                if ($pluginFile === $currentBasename) {
                    continue;
                }

                // Official companion plugins are explicitly allowed.
                if (in_array($pluginFile, self::officialPluginBasenames(), true)) {
                    continue;
                }

                $name = (string) ($headers['Name'] ?? '');
                $textDomain = (string) ($headers['TextDomain'] ?? '');
                $pluginSlug = dirname($pluginFile);

                // Only a duplicate FlashSite Core installation should be treated as conflict.
                $matchesDuplicateCore = $name === 'FlashSite Core'
                    || $textDomain === 'flashsite-core'
                    || str_contains($pluginFile, 'flashsite-core');

                if (! $matchesDuplicateCore) {
                    continue;
                }

                $duplicates[] = [
                    'file' => $pluginFile,
                    'name' => $name,
                    'version' => (string) ($headers['Version'] ?? ''),
                    'text_domain' => $textDomain,
                    'slug' => $pluginSlug,
                ];
            }
        }

        $pathStatus = $currentDir === $expectedSlug ? 'canonical' : 'legacy_slug';
        $hasConflict = $pathStatus !== 'canonical' || $duplicates !== [];

        self::$status = [
            'path_status' => $pathStatus,
            'current_dir' => $currentDir,
            'current_basename' => $currentBasename,
            'expected_slug' => $expectedSlug,
            'duplicate_candidates' => $duplicates,
            'has_conflict' => $hasConflict,
            'official_allowed' => self::officialPluginBasenames(),
        ];

        return self::$status;
    }

    public static function hasConflict(): bool
    {
        $status = self::$status ?? self::status();
        return ! empty($status['has_conflict']);
    }

    /**
     * Sensitive writes are allowed only when the active Core is installed in the
     * canonical plugin directory and no duplicate Core installation is detected.
     */
    public static function canWriteSensitive(): bool
    {
        return ! self::hasConflict();
    }

    public static function blockReason(): string
    {
        $status = self::$status ?? self::status();
        if (($status['path_status'] ?? '') !== 'canonical') {
            return sprintf(
                'Instalação fora do diretório canônico. Diretório atual: %s. Diretório esperado: %s.',
                (string) ($status['current_dir'] ?? ''),
                (string) ($status['expected_slug'] ?? 'flashsite-core')
            );
        }
        if (! empty($status['duplicate_candidates']) && is_array($status['duplicate_candidates'])) {
            return 'Foi detectada outra instalação do FlashSite Core. Remova versões duplicadas antes de guardar dados sensíveis.';
        }
        return '';
    }
}

