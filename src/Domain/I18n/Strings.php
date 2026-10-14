<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\I18n;

/**
 * Textos do Core no idioma da página (@since 3.2.0).
 *
 *   Strings::t('Ver todos')   "Ver todos" em português, "See all" em inglês…
 *
 * O texto de origem é sempre o português que o Core e os presets já usam; em pt-PT e pt-BR
 * devolve-o sem mexer (sites de um idioma ficam exatamente como antes). Nos outros idiomas
 * procura no pacote do idioma, depois no pacote en, e por fim devolve o português.
 * Espaços no início e no fim são preservados ("a partir de " → "from ").
 */
final class Strings
{
    private static ?LanguageContextInterface $context = null;

    /** @var array<string, array<string, string>> */
    private static array $phrases = [];

    private static ?string $directory = null;

    public static function useContext(?LanguageContextInterface $context): void
    {
        self::$context = $context;
    }

    public static function context(): ?LanguageContextInterface
    {
        return self::$context;
    }

    /** Pasta dos pacotes (testes). */
    public static function useDirectory(?string $directory): void
    {
        self::$directory = $directory;
        self::$phrases = [];
    }

    /** Idioma da página; o filtro flashsite/language permite forçar. */
    public static function language(): string
    {
        $language = self::$context !== null ? self::$context->current() : Language::PT_PT;
        if (function_exists('apply_filters')) {
            $language = (string) apply_filters('flashsite/language', $language);
        }
        return Language::isSupported($language) ? $language : Language::PT_PT;
    }

    public static function t(string $text, ?string $language = null): string
    {
        $language ??= self::language();
        if ($text === '' || Language::isPortuguese($language)) {
            return $text;
        }
        $core = trim($text);
        if ($core === '') {
            return $text;
        }
        $lead = substr($text, 0, strlen($text) - strlen(ltrim($text)));
        $tail = substr($text, strlen(rtrim($text)));
        foreach ([$language, Language::EN] as $code) {
            $map = self::phrases($code);
            if (isset($map[$text])) {
                return $map[$text];
            }
            if (isset($map[$core])) {
                return $lead . trim($map[$core]) . $tail;
            }
        }
        return $text;
    }

    /**
     * Pastas extra de pacotes (filtro "flashsite/i18n/pack_dirs"), cada uma com <idioma>.php.
     *
     * @return list<string>
     */
    private static function externalDirectories(): array
    {
        $dirs = function_exists('apply_filters') ? apply_filters('flashsite/i18n/pack_dirs', []) : [];
        return array_values(array_filter(is_array($dirs) ? $dirs : [], static fn ($dir): bool => is_string($dir) && $dir !== '' && is_dir($dir)));
    }

    /** @return array<string, string> */
    private static function phrases(string $language): array
    {
        if (isset(self::$phrases[$language])) {
            return self::$phrases[$language];
        }
        $directory = self::$directory ?? dirname(__DIR__, 3) . '/config/i18n';
        $file = $directory . '/' . $language . '.php';
        $pack = is_readable($file) ? require $file : [];
        $phrases = is_array($pack) && isset($pack['phrases']) && is_array($pack['phrases']) ? $pack['phrases'] : [];
        // Pacotes de kits e extensões (@since 3.4.0): só acrescentam; em conflito o pacote do Core vence.
        $external = [];
        foreach (self::externalDirectories() as $extraDirectory) {
            $extraFile = rtrim($extraDirectory, '/\\') . '/' . $language . '.php';
            $extraPack = is_readable($extraFile) ? require $extraFile : [];
            if (is_array($extraPack) && isset($extraPack['phrases']) && is_array($extraPack['phrases'])) {
                $external = array_replace($external, array_map('strval', $extraPack['phrases']));
            }
        }
        return self::$phrases[$language] = $external === []
            ? array_map('strval', $phrases)
            : array_replace($external, array_map('strval', $phrases));
    }
}
