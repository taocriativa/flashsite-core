<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Definições globais das Coleções no site (FlashSite › Coleções).
 *
 * Formato da option: ['currency' => 'EUR', 'market' => 'PT']
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
        if (isset($raw['currency'])) {
            return Currency::of((string) $raw['currency']);
        }
        // Sem moeda escolhida: a do mercado, se este já foi escolhido.
        return Currency::of(isset($raw['market']) ? Market::defaultCurrency(Market::normalize((string) $raw['market'])) : Currency::DEFAULT);
    }

    /** Mercado do site (PT/BR). Sem escolha guardada: deduzido da moeda e da língua do WordPress. */
    public function market(): string
    {
        $raw = $this->raw();
        if (isset($raw['market']) && Market::isValid((string) $raw['market'])) {
            return Market::normalize((string) $raw['market']);
        }
        $locale = function_exists('get_locale') ? (string) get_locale() : '';
        return Market::guess(isset($raw['currency']) ? (string) $raw['currency'] : null, $locale);
    }

    public function setMarket(string $code): bool
    {
        if (! Market::isValid($code)) {
            return false;
        }
        $raw = $this->raw();
        $raw['market'] = Market::normalize($code);
        return $this->storage->update(self::OPTION_KEY, $raw, true);
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
