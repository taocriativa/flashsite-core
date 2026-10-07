<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use Throwable;

/**
 * Carrega e valida os presets declarativos de config/collections/*.php.
 *
 * Um preset inválido nunca derruba o site: é ignorado e o erro fica em errors().
 *
 * @since 2.6.0
 */
final class CollectionRegistry
{
    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    /** @var array<string, string> ficheiro => mensagem */
    private array $errors = [];

    private string $market = Market::DEFAULT;

    public function market(): string
    {
        return $this->market;
    }

    public static function fromDirectory(string $directory, string $market = Market::DEFAULT): self
    {
        $registry = new self();
        $registry->market = Market::normalize($market);
        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $basename = basename($file);
            // Ficheiros auxiliares (ex.: sectors.php) começam por "_" ou estão na lista de exclusão.
            if (str_starts_with($basename, '_') || $basename === 'sectors.php') {
                continue;
            }
            try {
                $config = require $file;
                if (! is_array($config)) {
                    $registry->errors[$basename] = 'O ficheiro não devolve um array.';
                    continue;
                }
                $registry->add(CollectionPreset::fromArray(MarketLayer::apply($config, $registry->market)));
            } catch (Throwable $e) {
                $registry->errors[$basename] = $e->getMessage();
            }
        }

        return $registry;
    }

    public function add(CollectionPresetInterface $preset): void
    {
        foreach ($this->presets as $existing) {
            if ($existing->postType() === $preset->postType() || $existing->slug() === $preset->slug()) {
                $this->errors[$preset->key()] = sprintf('Conflito de post_type/slug com o preset "%s".', $existing->key());
                return;
            }
        }
        if (isset($this->presets[$preset->key()])) {
            $this->errors[$preset->key()] = 'Chave de preset duplicada.';
            return;
        }
        $this->presets[$preset->key()] = $preset;
    }

    public function has(string $key): bool
    {
        return isset($this->presets[$key]);
    }

    public function get(string $key): ?CollectionPresetInterface
    {
        return $this->presets[$key] ?? null;
    }

    public function byPostType(string $postType): ?CollectionPresetInterface
    {
        foreach ($this->presets as $preset) {
            if ($preset->postType() === $postType) {
                return $preset;
            }
        }
        return null;
    }

    /** @return array<string, CollectionPresetInterface> */
    public function all(): array
    {
        return $this->presets;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->presets);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
