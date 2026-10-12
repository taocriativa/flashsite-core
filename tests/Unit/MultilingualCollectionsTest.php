<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Multilingual\PolylangBridge;

/** Coleções por idioma (@since 3.2.0-beta.4): só as marcadas entram no Polylang. */
final class MultilingualCollectionsTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $settings = new CollectionSettings(new OptionsStorage());

        // Padrão: tudo compartilhado.
        $this->assertSame([], $settings->perLanguageKeys());
        $this->assertSame(false, $settings->perLanguage('plano'));

        // Guardar limpa as chaves e não mexe no mercado nem na moeda.
        $settings->setMarket('PT');
        $settings->setPerLanguage(['plano', 'Plano', 'pr ato!', '']);
        $this->assertSame(['plano', 'prato'], $settings->perLanguageKeys());
        $this->assertSame(true, $settings->perLanguage('plano'));
        $this->assertSame('PT', $settings->market());

        // Só as coleções por idioma entram na lista de tipos traduzíveis do Polylang.
        $bridge = new PolylangBridge($settings, static fn (): array => ['plano' => 'fs_plano', 'imovel' => 'fs_imovel']);
        $types = $bridge->translatablePostTypes(['page' => 'page', 'post' => 'post']);
        $this->assertSame(['page' => 'page', 'post' => 'post', 'fs_plano' => 'fs_plano'], $types);

        $settings->setPerLanguage([]);
        $this->assertSame(['page' => 'page'], $bridge->translatablePostTypes(['page' => 'page']));
    }
}
