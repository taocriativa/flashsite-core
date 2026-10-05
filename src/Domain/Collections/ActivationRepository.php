<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Presets ativos no site. Desativar um preset nunca apaga itens: apenas deixa de registar o CPT.
 *
 * Formato: ['imovel' => ['activated_at' => ISO8601], ...]
 *
 * @since 2.6.0
 */
final class ActivationRepository
{
    public const OPTION_KEY = 'flashsite_collections_active';

    public function __construct(private OptionsStorage $storage) {}

    /** @return list<string> */
    public function activeKeys(): array
    {
        return array_keys($this->raw());
    }

    public function isActive(string $key): bool
    {
        return array_key_exists($key, $this->raw());
    }

    public function activate(string $key): bool
    {
        $raw = $this->raw();
        if (isset($raw[$key])) {
            return true;
        }
        $raw[$key] = ['activated_at' => gmdate('c')];
        return $this->storage->update(self::OPTION_KEY, $raw, true);
    }

    public function deactivate(string $key): bool
    {
        $raw = $this->raw();
        if (! isset($raw[$key])) {
            return true;
        }
        unset($raw[$key]);
        return $this->storage->update(self::OPTION_KEY, $raw, true);
    }

    /**
     * Substitui o conjunto ativo (usado pelo wizard). Chaves desconhecidas são descartadas.
     *
     * @param list<string> $keys
     * @param list<string> $knownKeys
     */
    public function replace(array $keys, array $knownKeys): bool
    {
        $current = $this->raw();
        $next = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (! in_array($key, $knownKeys, true)) {
                continue;
            }
            $next[$key] = $current[$key] ?? ['activated_at' => gmdate('c')];
        }
        return $this->storage->update(self::OPTION_KEY, $next, true);
    }

    /** @return array<string, array<string, mixed>> */
    private function raw(): array
    {
        $value = $this->storage->get(self::OPTION_KEY, []);
        if (! is_array($value)) {
            return [];
        }
        $normalized = [];
        foreach ($value as $key => $meta) {
            // Tolera o formato lista ['imovel', 'prato'].
            if (is_int($key) && is_string($meta)) {
                $normalized[$meta] = ['activated_at' => ''];
                continue;
            }
            if (is_string($key)) {
                $normalized[$key] = is_array($meta) ? $meta : ['activated_at' => ''];
            }
        }
        return $normalized;
    }
}
