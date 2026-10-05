<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Definições globais das Coleções no site (FlashSite › Coleções).
 *
 * Formato da option: ['currency' => 'EUR']
 *
 * @since 2.6.0
 */
final class CollectionSettings
{
    public const OPTION_KEY = 'flashsite_collections_settings';

    public function __construct(private OptionsStorage $storage) {}

    public function currency(): Currency
    {
        $raw = $this->raw();
        return Currency::of((string) ($raw['currency'] ?? Currency::DEFAULT));
    }

    public function setCurrency(string $code): bool
    {
        if (! Currency::isValid($code)) {
            return false;
        }
        $raw = $this->raw();
        $raw['currency'] = Currency::of($code)->code;
        return $this->storage->update(self::OPTION_KEY, $raw, true);
    }

    /** @return array<string, mixed> */
    private function raw(): array
    {
        $value = $this->storage->get(self::OPTION_KEY, []);
        return is_array($value) ? $value : [];
    }
}
