<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;

/**
 * Idioma lido do Polylang (@since 3.2.0). Só é criado quando o Polylang está ativo.
 * Idioma do Polylang que o Core não conhece cai no principal do site.
 */
final class PolylangLanguageContext implements LanguageContextInterface
{
    public function __construct(private LanguageContextInterface $fallback) {}

    public function current(): string
    {
        $locale = function_exists('pll_current_language') ? (string) pll_current_language('locale') : '';
        $code = Language::fromLocale($locale);
        return $code !== '' ? $this->refine($code, $locale) : $this->primary();
    }

    public function primary(): string
    {
        $locale = function_exists('pll_default_language') ? (string) pll_default_language('locale') : '';
        $code = Language::fromLocale($locale);
        return $code !== '' ? $this->refine($code, $locale) : $this->fallback->primary();
    }

    public function available(): array
    {
        $locales = function_exists('pll_languages_list') ? (array) pll_languages_list(['fields' => 'locale']) : [];
        $codes = [];
        foreach ($locales as $locale) {
            $code = Language::fromLocale((string) $locale);
            if ($code !== '') {
                $codes[] = $this->refine($code, (string) $locale);
            }
        }
        $primary = $this->primary();
        return array_values(array_unique(array_merge([$primary], $codes)));
    }

    public function ofPost(int $postId): string
    {
        $locale = $postId > 0 && function_exists('pll_get_post_language') ? (string) pll_get_post_language($postId, 'locale') : '';
        $code = Language::fromLocale($locale);
        return $code !== '' ? $this->refine($code, $locale) : $this->primary();
    }

    /** Português sem região ("pt") segue o mercado do site (pt-PT ou pt-BR). */
    private function refine(string $code, string $locale): string
    {
        if ($code === Language::PT_PT && ! str_contains(strtolower(str_replace('_', '-', $locale)), 'pt-pt')) {
            return $this->fallback->primary();
        }
        return $code;
    }
}
