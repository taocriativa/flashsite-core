<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;

/**
 * Nome das faixas de preço no idioma do visitante (@since 3.3.0-beta.5).
 *
 * Os termos continuam gravados em português (nome, slug e ID não mudam). Só o objeto devolvido
 * ao site, nas taxonomias de faixa do Core, leva o nome localizado, o que inclui o Taxonomy Filter
 * nativo do Elementor, que lê os termos com get_terms().
 *
 * Fica de fora: o painel e o wp-admin, a REST (inclui a API pública e a técnica), a gravação
 * (suspend()) e o português. O AJAX só vale quando nasce numa página do site, não no painel.
 * O filtro `flashsite/localize_band_terms` permite desligar.
 */
final class BandTermNames
{
    /** @var array<string, CollectionPresetInterface> taxonomia => preset */
    private array $bands = [];

    private static int $suspended = 0;

    public function __construct(private FieldFormatter $formatter) {}

    /** @param array<string, CollectionPresetInterface> $presets ativos */
    public function register(array $presets): void
    {
        $this->bands = [];
        foreach ($presets as $preset) {
            $config = PriceBandResolver::config($preset);
            if ($config === null) {
                continue;
            }
            foreach ($preset->taxonomies() as $taxonomy) {
                if ($taxonomy->key === (string) $config['taxonomy']) {
                    $this->bands[$taxonomy->taxonomyName($preset->postType())] = $preset;
                }
            }
        }
        if ($this->bands === []) {
            return;
        }
        add_filter('get_terms', [$this, 'filterList'], 10, 1);
        add_filter('get_the_terms', [$this, 'filterList'], 10, 1);
        add_filter('wp_get_object_terms', [$this, 'filterList'], 10, 1);
        add_filter('get_term', [$this, 'filterTerm'], 10, 1);
    }

    /** Durante uma gravação de termos nada é traduzido (o nome persistido tem de ser o original). */
    public static function suspend(callable $callback): mixed
    {
        self::$suspended++;
        try {
            return $callback();
        } finally {
            self::$suspended--;
        }
    }

    /**
     * @param mixed $terms
     * @return mixed
     */
    public function filterList($terms)
    {
        if (! is_array($terms) || $terms === [] || ! $this->active()) {
            return $terms;
        }
        foreach ($terms as $i => $term) {
            if (is_object($term)) {
                $terms[$i] = $this->localize($term);
            }
        }
        return $terms;
    }

    /**
     * @param mixed $term
     * @return mixed
     */
    public function filterTerm($term)
    {
        return is_object($term) && $this->active() ? $this->localize($term) : $term;
    }

    private function localize(object $term): object
    {
        $taxonomy = (string) ($term->taxonomy ?? '');
        if (! isset($this->bands[$taxonomy]) || ! isset($term->slug)) {
            return $term;
        }
        $name = PriceBandResolver::localizedName($this->bands[$taxonomy], (string) $term->slug, $this->formatter->currency(), Strings::language());
        if ($name === null || $name === (string) ($term->name ?? '')) {
            return $term;
        }
        $copy = clone $term;
        $copy->name = $name;
        return $copy;
    }

    private function active(): bool
    {
        if (self::$suspended > 0 || Language::isPortuguese(Strings::language())) {
            return false;
        }
        $rest = function_exists('wp_is_serving_rest_request') ? wp_is_serving_rest_request() : (defined('REST_REQUEST') && REST_REQUEST);
        if ($rest) {
            return false;
        }
        if (is_admin() && ! $this->frontendAjax()) {
            return false;
        }
        return ! function_exists('apply_filters') || (bool) apply_filters('flashsite/localize_band_terms', true);
    }

    /** admin-ajax.php disparado por uma página do site (não por um ecrã do painel). */
    private function frontendAjax(): bool
    {
        if (! function_exists('wp_doing_ajax') || ! wp_doing_ajax()) {
            return false;
        }
        $referer = function_exists('wp_get_referer') ? (string) wp_get_referer() : '';
        return $referer !== '' && ! str_starts_with($referer, admin_url());
    }
}
