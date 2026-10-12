<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

use FlashSite\Core\Core\Contracts\ModuleInterface;
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
        add_action('plugins_loaded', static function () use ($market): void {
            if (function_exists('pll_current_language')) {
                Strings::useContext(new PolylangLanguageContext($market));
            }
        }, 20);
    }
}
