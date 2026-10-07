<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\Market;
use FlashSite\Core\Domain\Collections\MarketLayer;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class MarketTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        // Dedução e definição do mercado.
        $this->assertSame('BR', Market::guess('BRL', 'pt_PT'));
        $this->assertSame('PT', Market::guess('EUR', 'pt_BR'));
        $this->assertSame('BR', Market::guess(null, 'pt_BR'));
        $this->assertSame('PT', Market::guess(null, 'en_US'));
        $settings = new CollectionSettings(new OptionsStorage());
        $this->assertSame('PT', $settings->market(), 'Sem escolha nem moeda: Portugal.');
        $this->assertTrue($settings->setMarket('br'));
        $this->assertSame('BR', $settings->market());
        $this->assertSame('BRL', $settings->currency()->code, 'Sem moeda escolhida, segue o mercado.');
        $this->assertFalse($settings->setMarket('XX'));

        // Camada: substitui, remove, acrescenta na posição pedida.
        $config = ['labels' => ['a' => 1, 'b' => 2], 'fields' => ['x' => ['label' => 'X'], 'y' => ['label' => 'Y'], 'z' => ['label' => 'Z']],
            'markets' => ['BR' => ['labels' => ['b' => 3], 'fields' => ['y' => null, 'x' => ['label' => 'X2'], 'n' => ['label' => 'N', 'after' => 'x']]]]];
        $out = MarketLayer::apply($config, 'BR');
        $this->assertSame(['a' => 1, 'b' => 3], $out['labels']);
        $this->assertSame(['x', 'n', 'z'], array_keys($out['fields']));
        $this->assertSame('X2', $out['fields']['x']['label']);
        $this->assertFalse(isset($out['fields']['n']['after']), '"after" não passa para o campo.');
        $this->assertFalse(isset($out['markets']));
        $this->assertSame(['x', 'y', 'z'], array_keys(MarketLayer::apply($config, 'PT')['fields']), 'Portugal fica com a base.');

        // Presets reais no Brasil.
        $dir = dirname(__DIR__, 2) . '/config/collections';
        $br = CollectionRegistry::fromDirectory($dir, 'BR');
        $this->assertSame([], $br->errors(), 'Presets BR sem erros: ' . json_encode($br->errors()));
        $this->assertSame('BR', $br->market());
        $imovel = $br->get('imovel');
        $this->assertSame(['venda' => 'Venda', 'aluguel' => 'Aluguel'], $imovel->taxonomy('finalidade')->terms);
        $this->assertSame('2 quartos', $imovel->taxonomy('tipologia')->terms['2-quartos']);
        $this->assertSame('Quartos', $imovel->taxonomy('tipologia')->label);
        $this->assertTrue($imovel->field('classe_energetica') === null, 'Sem classe energética no Brasil.');
        $this->assertTrue($imovel->field('quartos') === null, 'Quartos vêm da taxonomia.');
        $this->assertSame('Banheiros', $imovel->field('wc')->label);
        $this->assertSame('Suítes', $imovel->field('suites')->label);
        $this->assertSame('Vagas de garagem', $imovel->field('vagas')->label);
        $this->assertTrue($imovel->field('valor_condominio') !== null && $imovel->field('valor_iptu') !== null);
        $this->assertFalse($imovel->field('morada')->public, 'Endereço continua privado.');
        $this->assertSame('aluguel-ate-1000', PriceBandResolver::resolve($imovel, 900.0, false, 'aluguel')[0]);
        $this->assertSame(['taxonomy' => 'estado', 'terms' => ['reservado', 'vendido', 'alugado']], $imovel->setting('closed_terms'));
        $prato = $br->get('prato');
        $this->assertSame('cardapio', $prato->slug());
        $this->assertSame('Gergelim', $prato->field('alergenios')->options['sesamo']);

        // Portugal mantém tudo como estava.
        $pt = CollectionRegistry::fromDirectory($dir, 'PT');
        $this->assertSame('T2', $pt->get('imovel')->taxonomy('tipologia')->terms['t2']);
        $this->assertSame('menu', $pt->get('prato')->slug());
        $this->assertTrue($pt->get('imovel')->field('classe_energetica') !== null);
    }
}
