<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Contracts;

interface RepositoryInterface
{
    public function reset(): bool;
}
