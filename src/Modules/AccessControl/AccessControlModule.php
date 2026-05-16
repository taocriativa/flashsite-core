<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\AccessControl;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Access\RoleManager;

final class AccessControlModule implements ModuleInterface
{
    public function __construct(private RoleManager $roleManager, private LoggerInterface $logger) {}

    public function register(): void
    {
        add_action('init', [$this, 'ensureAccessControl']);
    }

    public function boot(): void
    {
        $this->logger->info('AccessControl module booted.');
    }

    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'access-control'; }

    public function ensureAccessControl(): void
    {
        $this->roleManager->ensureRole();
    }
}
