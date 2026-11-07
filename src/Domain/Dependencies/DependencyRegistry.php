<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Dependencies;

final class DependencyRegistry
{
    public function __construct(private array $items) {}

    public function all(): array
    {
        return $this->items;
    }

    public static function fromConfig(string $configPath): self
    {
        $items = [];
        $config = file_exists($configPath) ? require $configPath : [];

        if (is_array($config)) {
            foreach ($config as $row) {
                if (! is_array($row)) { continue; }
                // Dependências só da stack Elementor não se pedem em sites com tema de blocos. @since 3.7.0-beta.7
                if (($row['stack'] ?? '') === 'elementor' && self::isBlockSite()) { continue; }
                $items[] = new DependencyDefinition(
                    (string) ($row['slug'] ?? ''),
                    (string) ($row['label'] ?? ''),
                    (string) ($row['level'] ?? 'recommended'),
                    isset($row['min_version']) ? (string) $row['min_version'] : null
                );
            }
        }

        return new self($items);
    }

    private static function isBlockSite(): bool
    {
        return function_exists('wp_is_block_theme') && wp_is_block_theme();
    }
}
