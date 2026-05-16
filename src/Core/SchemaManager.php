<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class SchemaManager
{
    /** @var array<string, callable(): void> */
    private array $upgrades = [];

    public function register(string $version, callable $callback): void
    {
        $this->upgrades[$version] = $callback;
    }

    public function runIfNeeded(): void
    {
        $installedVersion = (string) get_option('flashsite_core_version', '0.0.0');
        if (version_compare($installedVersion, FLASHSITE_CORE_VERSION, '>=')) {
            return;
        }
        uksort($this->upgrades, 'version_compare');
        foreach ($this->upgrades as $version => $callback) {
            if (version_compare($installedVersion, $version, '<')) {
                $callback();
            }
        }
        update_option('flashsite_core_version', FLASHSITE_CORE_VERSION, true);
    }
}
