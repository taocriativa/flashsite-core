<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\DependencyManager;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Core\Notices;
use FlashSite\Core\Core\PluginChecker;
use FlashSite\Core\Domain\Dependencies\DependencyRegistry;

final class DependencyManagerModule implements ModuleInterface
{
    public function __construct(
        private DependencyRegistry $registry,
        private PluginChecker $pluginChecker,
        private Notices $notices,
        private LoggerInterface $logger
    ) {}

    public function register(): void
    {
        add_action('admin_init', [$this, 'auditDependencies']);
    }

    public function boot(): void {}
    public function isActive(): bool { return is_admin(); }
    public function getSlug(): string { return 'dependency-manager'; }

    public function auditDependencies(): void
    {
        if (! current_user_can('flashsite_manage_dependencies') && ! current_user_can('manage_options')) {
            return;
        }

        $state = [];
        foreach ($this->registry->all() as $dependency) {
            $installed = $this->pluginChecker->isInstalled($dependency->getSlug());
            $active = $installed && $this->pluginChecker->isActive($dependency->getSlug());
            $meetsVersion = $this->pluginChecker->meetsMinVersion($dependency->getSlug(), $dependency->getMinVersion());

            $state[] = [
                'slug' => $dependency->getSlug(),
                'label' => $dependency->getLabel(),
                'level' => $dependency->getLevel(),
                'installed' => $installed,
                'active' => $active,
                'meets_version' => $meetsVersion,
            ];

            if (! $installed && $dependency->getLevel() !== 'optional') {
                $this->notices->add('warning', sprintf('%s não está instalado.', $dependency->getLabel()));
            } elseif ($installed && ! $active && $dependency->getLevel() !== 'optional') {
                $this->notices->add('warning', sprintf('%s está instalado mas não está ativo.', $dependency->getLabel()));
            }
        }

        update_option('flashsite_dependency_state', $state, false);
        $this->logger->info('Dependency audit completed.', ['count' => count($state)]);
    }
}
