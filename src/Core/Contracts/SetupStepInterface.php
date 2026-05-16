<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Contracts;

interface SetupStepInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getOrder(): int;
    public function isCompleted(): bool;

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public function validate(array $data): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): void;
}
