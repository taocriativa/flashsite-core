<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/** Selo de destaque (@since 3.6.0): campos em todos os presets, texto pronto/livre/"só até" e expiração. */
final class SeloTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $settings = new CollectionSettings(new OptionsStorage());
        $settings->setMarket('BR');
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections', 'BR');

        foreach ($registry->all() as $key => $preset) {
            $this->assertTrue($preset->field('selo') !== null, "Preset $key tem o campo selo.");
            $this->assertTrue($preset->field('selo_texto') !== null && $preset->field('selo_ate') !== null, "Preset $key tem texto e data do selo.");
            $this->assertSame('Selo de destaque', $preset->groups()['selo']);
        }

        $imovel = $registry->get('imovel');
        $fmt = new FieldFormatter(new ItemReader(), $settings);
        $set = fn (string $k, mixed $v) => update_post_meta(960, $imovel->field($k)->metaKey(), $v);

        $this->assertSame('', $fmt->badge($imovel, 960), 'Sem escolha, sem selo.');
        $set('selo', 'esgotado');
        $this->assertSame('Esgotado', $fmt->badge($imovel, 960));
        $set('selo', 'ultimas');
        $this->assertSame('Últimas unidades', $fmt->badge($imovel, 960));

        $set('selo', 'ate');
        $this->assertSame('', $fmt->badge($imovel, 960), '"Só até" sem data não mostra nada.');
        $set('selo_ate', '2099-10-30');
        $this->assertSame('Só até 30/10', $fmt->badge($imovel, 960));

        $set('selo', 'personalizado');
        $set('selo_texto', 'Condição especial');
        $this->assertSame('Condição especial', $fmt->badge($imovel, 960));

        $set('selo_ate', '2000-01-01');
        $this->assertSame('', $fmt->badge($imovel, 960), 'Depois da data, o selo some.');
        $set('selo_ate', '');
        $set('selo', 'esgotado');
        $this->assertSame('Esgotado', $fmt->badge($imovel, 960), 'Sem data, o selo fica até ser desligado.');
    }
}
