<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

/**
 * Seletor de idiomas pela classe "fs-idiomas" (@since 3.2.0-beta.5), como o fs-mapa.
 *
 * Um parágrafo (ou título) com a classe global "fs-idiomas" passa a mostrar os idiomas do site,
 * cada um com o link para a tradução da página atual (sem tradução: a página inicial do idioma).
 * O idioma atual leva a classe is-ativo e aria-current. O visual vem do CSS do site; o Core só
 * põe uma base mínima (fsc-effects.css).
 */
final class LanguageSwitcher
{
    public function register(): void
    {
        add_filter('elementor/widget/render_content', [$this, 'inject'], 20, 2);
    }

    /** @param mixed $content */
    public function inject($content, $widget = null)
    {
        if (! is_string($content) || ! str_contains($content, 'fs-idiomas') || ! function_exists('pll_the_languages')) {
            return $content;
        }
        $languages = pll_the_languages(['raw' => 1, 'hide_if_empty' => 0, 'hide_if_no_translation' => 0]);
        return is_array($languages) ? self::markup($content, $languages) : $content;
    }

    /**
     * @param array<int|string, array<string, mixed>> $languages formato raw do Polylang (slug, url, name, current_lang, locale)
     */
    public static function markup(string $html, array $languages): string
    {
        $links = [];
        foreach ($languages as $language) {
            $slug = (string) ($language['slug'] ?? '');
            $url = (string) ($language['url'] ?? '');
            if ($slug === '' || $url === '') {
                continue;
            }
            $current = ! empty($language['current_lang']);
            $locale = str_replace('_', '-', (string) ($language['locale'] ?? $slug));
            $links[] = sprintf(
                '<a class="fs-idiomas__item%s" href="%s" hreflang="%s" lang="%s" title="%s"%s>%s</a>',
                $current ? ' is-ativo' : '',
                htmlspecialchars($url, ENT_QUOTES),
                htmlspecialchars($locale, ENT_QUOTES),
                htmlspecialchars($locale, ENT_QUOTES),
                htmlspecialchars((string) ($language['name'] ?? $slug), ENT_QUOTES),
                $current ? ' aria-current="true"' : '',
                htmlspecialchars(strtoupper($slug), ENT_QUOTES)
            );
        }
        if ($links === []) {
            return $html;
        }
        $out = preg_replace_callback(
            '/(<([a-z0-9]+)\b[^>]*\bclass="[^"]*\bfs-idiomas\b[^"]*"[^>]*>)(.*?)(<\/\2>)/s',
            static fn (array $m): string => $m[1] . implode('', $links) . $m[4],
            $html,
            1
        );
        return is_string($out) ? $out : $html;
    }
}
