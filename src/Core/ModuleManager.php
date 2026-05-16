<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use RuntimeException;

final class ModuleManager
{
    /** @var array<int, ModuleInterface> */
    private array $modules = [];

    public function load(array $moduleClasses, Container $container): void
    {
        foreach ($moduleClasses as $moduleClass) {
            $module = $container->has($moduleClass) ? $container->make($moduleClass) : new $moduleClass();
            if (! $module instanceof ModuleInterface) {
                throw new RuntimeException(sprintf('Module "%s" must implement ModuleInterface.', $moduleClass));
            }
            $this->modules[] = $module;
        }
    }

    public function registerAll(): void
    {
        foreach ($this->modules as $module) {
            $module->register();
        }
    }

    public function bootAll(): void
    {
        foreach ($this->modules as $module) {
            if ($module->isActive()) {
                $module->boot();
            }
        }
    }
}
