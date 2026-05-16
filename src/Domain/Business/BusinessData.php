<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

final class BusinessData
{
    public function __construct(private BusinessRepository $repository) {}

    public function get(string $path, mixed $default = null): mixed
    {
        $current = $this->repository->getProfile()->toArray();
        foreach (explode('.', $path) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->repository->getProfile()->toArray();
    }

    public function has(string $path): bool
    {
        return $this->get($path, '__flashsite_missing__') !== '__flashsite_missing__';
    }
}
