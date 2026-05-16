<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Dependencies;

final class DependencyDefinition
{
    public function __construct(
        private string $slug,
        private string $label,
        private string $level,
        private ?string $minVersion = null
    ) {}

    public function getSlug(): string { return $this->slug; }
    public function getLabel(): string { return $this->label; }
    public function getLevel(): string { return $this->level; }
    public function getMinVersion(): ?string { return $this->minVersion; }
}
