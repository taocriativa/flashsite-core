<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Mercado do site: Portugal ou Brasil. Decide a terminologia das coleções (ex.: "T2" vs
 * "2 quartos", "Arrendamento" vs "Aluguel"), os campos próprios de cada país e a moeda por omissão.
 * Os dados guardados (chaves dos campos) são os mesmos nos dois mercados.
 *
 * @since 3.0.0
 */
final class Market
{
    public const PT = 'PT';
    public const BR = 'BR';
    public const DEFAULT = self::PT;

    /** @return array<string, string> código => nome */
    public static function options(): array
    {
        return [self::PT => 'Portugal', self::BR => 'Brasil'];
    }

    public static function isValid(string $code): bool
    {
        return isset(self::options()[strtoupper(trim($code))]);
    }

    public static function normalize(string $code): string
    {
        $code = strtoupper(trim($code));
        return self::isValid($code) ? $code : self::DEFAULT;
    }

    /** Mercado deduzido quando o site ainda não o escolheu: moeda e, depois, língua do WordPress. */
    public static function guess(?string $currency, ?string $locale): string
    {
        if (strtoupper((string) $currency) === 'BRL') {
            return self::BR;
        }
        if (strtoupper((string) $currency) === 'EUR') {
            return self::PT;
        }
        return str_starts_with((string) $locale, 'pt_BR') ? self::BR : self::PT;
    }

    public static function defaultCurrency(string $market): string
    {
        return $market === self::BR ? 'BRL' : 'EUR';
    }
}
