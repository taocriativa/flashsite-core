<?php
declare(strict_types=1);

abstract class TestCase
{
    protected function assertTrue(bool $condition, string $message = 'Expected condition to be true'): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected condition to be false'): void
    {
        if ($condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message !== '' ? $message : sprintf('Failed asserting that %s is identical to %s.', var_export($actual, true), var_export($expected, true)));
        }
    }

    protected function assertCount(int $expectedCount, Countable|array $value, string $message = ''): void
    {
        $actual = is_array($value) ? count($value) : count($value);
        if ($actual !== $expectedCount) {
            throw new RuntimeException($message !== '' ? $message : sprintf('Failed asserting count %d, got %d.', $expectedCount, $actual));
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new RuntimeException($message !== '' ? $message : sprintf('Array key %s not found.', (string) $key));
        }
    }
}
