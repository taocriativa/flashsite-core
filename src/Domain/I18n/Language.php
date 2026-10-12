<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\I18n;

/**
 * Idiomas que o Core sabe mostrar no site (@since 3.2.0).
 * Códigos curtos e estáveis: pt-PT, pt-BR, en, es, fr. Português é a língua de origem dos textos
 * do Core e dos presets; os outros idiomas vêm dos pacotes em config/i18n/<código>.php.
 */
final class Language
{
    public const PT_PT = 'pt-PT';
    public const PT_BR = 'pt-BR';
    public const EN = 'en';
    public const ES = 'es';
    public const FR = 'fr';

    /** @var array<string, string> código => nome no próprio idioma */
    public const SUPPORTED = [
        self::PT_PT => 'Português (Portugal)',
        self::PT_BR => 'Português (Brasil)',
        self::EN => 'English',
        self::ES => 'Español',
        self::FR => 'Français',
    ];

    public static function isSupported(string $code): bool
    {
        return isset(self::SUPPORTED[$code]);
    }

    public static function isPortuguese(string $code): bool
    {
        return $code === self::PT_PT || $code === self::PT_BR;
    }

    /** "en_GB", "en-US", "pt_BR", "fr" → código suportado; desconhecido → ''. */
    public static function fromLocale(string $locale): string
    {
        $locale = str_replace('_', '-', trim($locale));
        if ($locale === '') {
            return '';
        }
        $lower = strtolower($locale);
        if ($lower === 'pt-br') {
            return self::PT_BR;
        }
        $base = explode('-', $lower)[0];
        return match ($base) {
            'pt' => self::PT_PT,
            'en' => self::EN,
            'es' => self::ES,
            'fr' => self::FR,
            default => '',
        };
    }

    /** Mercado do site (PT | BR) → idioma por omissão. */
    public static function fromMarket(string $market): string
    {
        return strtoupper($market) === 'BR' ? self::PT_BR : self::PT_PT;
    }
}
