<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use Throwable;

/**
 * Carrega e valida os presets declarativos de config/collections/*.php e das pastas externas.
 *
 * Um preset inválido nunca derruba o site: é ignorado e o erro fica em errors().
 *
 * Origens (@since 3.4.0):
 *  - core: a pasta do Core. Chaves oficiais nunca começam por "x_".
 *  - external: pastas devolvidas pelo filtro "flashsite/collections/preset_dirs" (kits e jobs).
 *    A chave tem de começar por "x_"; um preset externo nunca substitui um oficial.
 *
 * @since 2.6.0
 */
final class CollectionRegistry
{
    public const EXTERNAL_PREFIX = 'x_';
    public const ORIGIN_CORE = 'core';
    public const ORIGIN_EXTERNAL = 'external';
    public const FILTER_DIRS = 'flashsite/collections/preset_dirs';

    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    /** @var array<string, string> chave => origem */
    private array $origins = [];

    /** @var array<string, string> ficheiro => mensagem */
    private array $errors = [];

    private string $market = Market::DEFAULT;

    public function market(): string
    {
        return $this->market;
    }

    /**
     * Pastas externas de presets (filtro oficial). A pasta do Core nunca passa por aqui.
     * Quem usa o filtro tem de o registar no carregamento do plugin, antes de plugins_loaded (prioridade 10).
     *
     * @return list<string>
     */
    public static function externalDirectories(string $coreDirectory = ''): array
    {
        $dirs = function_exists('apply_filters') ? apply_filters(self::FILTER_DIRS, []) : [];
        $core = $coreDirectory !== '' ? (realpath($coreDirectory) ?: rtrim($coreDirectory, '/\\')) : '';
        $clean = [];
        foreach (is_array($dirs) ? $dirs : [] as $dir) {
            if (! is_string($dir) || trim($dir) === '' || ! is_dir($dir)) {
                continue;
            }
            $real = realpath($dir) ?: rtrim($dir, '/\\');
            if ($real === $core || in_array($real, $clean, true)) {
                continue;
            }
            $clean[] = $real;
        }
        return $clean;
    }

    public static function fromDirectory(string $directory, string $market = Market::DEFAULT): self
    {
        return self::fromDirectories($directory, [], $market);
    }

    /**
     * @param list<string> $externalDirectories
     */
    public static function fromDirectories(string $coreDirectory, array $externalDirectories = [], string $market = Market::DEFAULT): self
    {
        $registry = new self();
        $registry->market = Market::normalize($market);
        $registry->load($coreDirectory, self::ORIGIN_CORE);
        foreach ($externalDirectories as $directory) {
            $registry->load($directory, self::ORIGIN_EXTERNAL);
        }
        return $registry;
    }

    private function load(string $directory, string $origin): void
    {
        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $basename = basename($file);
            // Ficheiros auxiliares começam por "_".
            if (str_starts_with($basename, '_')) {
                continue;
            }
            $source = $origin === self::ORIGIN_EXTERNAL ? 'externo/' . $basename : $basename;
            try {
                $config = require $file;
                if (! is_array($config)) {
                    $this->errors[$source] = 'O ficheiro não devolve um array.';
                    continue;
                }
                $key = (string) ($config['key'] ?? '');
                $prefixed = str_starts_with($key, self::EXTERNAL_PREFIX);
                if ($origin === self::ORIGIN_EXTERNAL && ! $prefixed) {
                    $this->errors[$source] = sprintf('Preset externo "%s": a chave tem de começar por "%s".', $key, self::EXTERNAL_PREFIX);
                    continue;
                }
                if ($origin === self::ORIGIN_CORE && $prefixed) {
                    $this->errors[$source] = sprintf('Preset oficial "%s": o prefixo "%s" é reservado a presets externos.', $key, self::EXTERNAL_PREFIX);
                    continue;
                }
                $this->add(CollectionPreset::fromArray(MarketLayer::apply($config, $this->market)), $origin, $source);
            } catch (Throwable $e) {
                $this->errors[$source] = $e->getMessage();
            }
        }
    }

    public function add(CollectionPresetInterface $preset, string $origin = self::ORIGIN_CORE, string $source = ''): void
    {
        $errorKey = $source !== '' ? $source : $preset->key();
        foreach ($this->presets as $existing) {
            if ($existing->postType() === $preset->postType() || $existing->slug() === $preset->slug()) {
                $this->errors[$errorKey] = sprintf('Conflito de post_type/slug com o preset "%s".', $existing->key());
                return;
            }
        }
        if (isset($this->presets[$preset->key()])) {
            $this->errors[$errorKey] = 'Chave de preset duplicada.';
            return;
        }
        $this->presets[$preset->key()] = $preset;
        $this->origins[$preset->key()] = $origin;
    }

    public function origin(string $key): ?string
    {
        return $this->origins[$key] ?? null;
    }

    public function isExternal(string $key): bool
    {
        return ($this->origins[$key] ?? '') === self::ORIGIN_EXTERNAL;
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
