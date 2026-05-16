<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

use Closure;
use InvalidArgumentException;

final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $bindings = [];
    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $key, Closure $factory): void
    {
        $this->bindings[$key] = $factory;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->bindings) || array_key_exists($key, $this->instances);
    }

    public function make(string $key): mixed
    {
        if (array_key_exists($key, $this->instances)) {
            return $this->instances[$key];
        }
        if (! array_key_exists($key, $this->bindings)) {
            throw new InvalidArgumentException(sprintf('Service "%s" is not bound.', $key));
        }
        $this->instances[$key] = ($this->bindings[$key])($this);
        return $this->instances[$key];
    }
}
