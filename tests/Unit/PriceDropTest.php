<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Collections\ItemPersistence;

/** Baixa de preço (@since 3.1.0): preço anterior automático, só mostrado quando se escolhe e quando baixou. */
final class PriceDropTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $settings = new CollectionSettings(new OptionsStorage());
        $settings->setMarket('BR');
        $imovel = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections', 'BR')->get('imovel');
        $this->assertSame('Mostrar baixa de preço no site', $imovel->field('mostrar_baixa')->label);
        $this->assertSame('Preço anterior', $imovel->field('preco_anterior')->label);

        $persist = new ItemPersistence(new ItemSanitizer($settings), new ItemValidator(), new ItemReader(), $settings);
        $fmt = new FieldFormatter(new ItemReader(), $settings);
        $meta = fn (string $key) => get_post_meta(950, $imovel->field($key)->metaKey(), true);

        // Baixa: o valor antigo fica guardado; só aparece quando "mostrar" está ligado.
        update_post_meta(950, $imovel->field('preco')->metaKey(), 780000.0);
        $persist->trackPriceChange($imovel, 950, 780000.0, 720000.0);
        update_post_meta(950, $imovel->field('preco')->metaKey(), 720000.0);
        $this->assertSame(780000.0, (float) $meta('preco_anterior'));
        $this->assertTrue($fmt->previousPrice($imovel, 950) === null, 'Sem marcar "mostrar", não aparece.');
        update_post_meta(950, $imovel->field('mostrar_baixa')->metaKey(), true);
        $this->assertSame(780000.0, $fmt->previousPrice($imovel, 950));
        $this->assertSame('R$ 780.000', $fmt->previousPriceText($imovel, 950, FieldFormatter::MONEY_AUTO));

        // Sob consulta: nada.
        update_post_meta(950, $imovel->field('preco_sob_consulta')->metaKey(), true);
        $this->assertTrue($fmt->previousPrice($imovel, 950) === null, 'Preço sob consulta esconde a baixa.');
        update_post_meta(950, $imovel->field('preco_sob_consulta')->metaKey(), false);

        // Subida: guarda o anterior, desliga "mostrar" e nunca aparece.
        $persist->trackPriceChange($imovel, 950, 720000.0, 750000.0);
        update_post_meta(950, $imovel->field('preco')->metaKey(), 750000.0);
        $this->assertSame(720000.0, (float) $meta('preco_anterior'));
        $this->assertSame(false, (bool) $meta('mostrar_baixa'), 'Subida desliga o aviso.');
        update_post_meta(950, $imovel->field('mostrar_baixa')->metaKey(), true);
        $this->assertTrue($fmt->previousPrice($imovel, 950) === null, 'Preço anterior menor que o atual nunca aparece.');

        // Sem mudança ou sem preço antigo: nada muda.
        $persist->trackPriceChange($imovel, 950, 750000.0, 750000.0);
        $persist->trackPriceChange($imovel, 951, '', 300000.0);
        $this->assertSame(720000.0, (float) $meta('preco_anterior'));
        $this->assertSame('', get_post_meta(951, $imovel->field('preco_anterior')->metaKey(), true));

        // Formulário: o preço anterior reenviado sem mexer não apaga o automático; mexido à mão, vale o novo.
        update_post_meta(952, $imovel->field('preco')->metaKey(), 500000.0);
        update_post_meta(952, $imovel->field('preco_anterior')->metaKey(), 520000.0);
        $persist->saveFields($imovel, 952, ['preco' => '500.000,00', 'preco_anterior' => '520.000,00']);
        $this->assertSame(520000.0, (float) get_post_meta(952, $imovel->field('preco_anterior')->metaKey(), true));
        $persist->saveFields($imovel, 952, ['preco' => '500.000,00', 'preco_anterior' => '560.000,00']);
        $this->assertSame(560000.0, (float) get_post_meta(952, $imovel->field('preco_anterior')->metaKey(), true));
    }
}
