<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\I18n\MarketLanguageContext;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Sites com mais de um idioma (@since 3.2.0).
 *
 * Sem plugin de tradução: um idioma, o do mercado (pt-PT ou pt-BR) — nada muda.
 * Com o Polylang ativo: o idioma de cada página vem dele, e os textos do Core
 * (Strings::t) saem no idioma da página. O adaptador só é ligado com o Polylang presente.
 */
final class MultilingualModule implements ModuleInterface
{
    public function register(): void
    {
        $settings = new CollectionSettings(new OptionsStorage());
        $market = new MarketLanguageContext(static fn (): string => $settings->market());
        Strings::useContext($market);
        add_action('plugins_loaded', static function () use ($market, $settings): void {
            if (function_exists('pll_current_language')) {
                Strings::useContext(new PolylangLanguageContext($market));
                add_filter('flashsite/front_page_ids', [self::class, 'frontTranslations']);
                add_filter('flashsite/multilingual_active', '__return_true');
                (new PolylangBridge($settings, static function () use ($settings): array {
                    static $map = null;
                    if ($map === null) {
                        $map = [];
                        // Um único registro por requisição (o do container), com as mesmas pastas externas.
                        $app = function_exists('flashsite_core') ? flashsite_core() : null;
                        $registry = $app !== null
                            ? $app->container()->make(CollectionRegistry::class)
                            : CollectionRegistry::fromDirectories(FLASHSITE_CORE_PATH . 'config/collections', CollectionRegistry::externalDirectories(FLASHSITE_CORE_PATH . 'config/collections'), $settings->market());
                        foreach ($registry->all() as $key => $preset) {
                            $map[(string) $key] = $preset->postType();
                        }
                    }
                    return $map;
                }))->register();
                (new LanguageSwitcher())->register();
                (new BusinessTranslationsPage())->register();
                add_filter('flashsite/model_page_id', [self::class, 'modelTranslation']);
            }
        }, 20);
    }

    /**
     * Traduções da página inicial (Textos do site mostram "Página inicial" em cada idioma).
     *
     * @param list<int> $ids
     * @return list<int>
     */
    public static function frontTranslations(array $ids): array
    {
        $front = (int) get_option('page_on_front');
        if ($front > 0 && function_exists('pll_get_post_translations')) {
            $ids = array_merge($ids, array_map('intval', array_values((array) pll_get_post_translations($front))));
        }
        return array_values(array_unique($ids));
    }

    /** Página modelo traduzida para o idioma atual (sem tradução: a original). */
    public static function modelTranslation(int $id): int
    {
        if (! function_exists('pll_get_post') || ! function_exists('pll_current_language')) {
            return $id;
        }
        $language = (string) pll_current_language('slug');
        $translated = $language !== '' ? (int) pll_get_post($id, $language) : 0;
        return $translated > 0 ? $translated : $id;
    }

    public function boot(): void {}

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'multilingual';
    }
}
