<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Contracts;

interface ModuleInterface
{
    public function register(): void;
    public function boot(): void;
    public function isActive(): bool;
    public function getSlug(): string;
}
