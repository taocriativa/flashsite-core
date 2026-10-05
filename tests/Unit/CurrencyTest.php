<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\Currency;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class CurrencyTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        // Convenções por moeda.
        $this->assertSame('285.000,00 €', Currency::of('EUR')->format(285000, 2));
        $this->assertSame('R$ 285.000,00', Currency::of('BRL')->format(285000, 2));
        $this->assertSame('$285,000.00', Currency::of('USD')->format(285000, 2));
        $this->assertSame('£1,250.50', Currency::of('GBP')->format(1250.5, 2));
        $this->assertSame('285.000,00 Kz', Currency::of('aoa')->format(285000, 2));
        $this->assertSame('EUR', Currency::of('XYZ')->code, 'Código desconhecido cai no euro.');
        $this->assertSame('R$ 285.000', FieldFormatter::money(285000.0, FieldFormatter::MONEY_AUTO, Currency::of('BRL')));

        // Definição do site.
        $settings = new CollectionSettings(new OptionsStorage());
        $this->assertSame('EUR', $settings->currency()->code, 'Por defeito: euro.');
        $this->assertFalse($settings->setCurrency('XYZ'));
        $this->assertTrue($settings->setCurrency('brl'));
        $this->assertSame('BRL', $settings->currency()->code);

        // Leitura de valores escritos à mão segue a moeda.
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285,000.00', '.'));
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285,000', '.'));
        $this->assertSame(1250.5, ItemSanitizer::parseNumber('1250.5', '.'));
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('R$ 285.000,00', ','));
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285.000,00', '.'), 'Dois separadores: o último é o decimal.');
        $preset = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections')->get('imovel');
        $usd = new CollectionSettings(new OptionsStorage());
        $usd->setCurrency('USD');
        $this->assertSame(285000.0, (new ItemSanitizer($usd))->sanitize($preset->field('preco'), '285,000'));

        // Faixas de preço com a moeda do site.
        $this->assertSame(['venda-ate-100000', 'Venda · Até R$ 100 mil'], PriceBandResolver::resolve($preset, 90000.0, false, 'venda', Currency::of('BRL')));
        $this->assertSame(['arrendamento-750-1000', 'Arrendamento · 750 a $1000/mês'], PriceBandResolver::resolve($preset, 900.0, false, 'arrendamento', Currency::of('USD')));
        $this->assertSame(['arrendamento-750-1000', 'Arrendamento · 750 a 1000 €/mês'], PriceBandResolver::resolve($preset, 900.0, false, 'arrendamento'));
        $this->assertSame(FieldType::Money, $preset->field('preco')->type);
    }
}
