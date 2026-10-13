<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\Currency;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Collections\ItemPersistence;
use FlashSite\Core\Modules\Collections\Output\BandTermNames;

/** 3.3.0-beta.5: faixas de preço no idioma do visitante (EN, FR, ES); PT e os termos gravados não mudam. */
final class MultilingualPriceBandsTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $dir = dirname(__DIR__, 2) . '/config/collections';
        $pt = CollectionRegistry::fromDirectory($dir, 'PT')->get('imovel');
        $br = CollectionRegistry::fromDirectory($dir, 'BR')->get('imovel');
        $eur = Currency::of('EUR');
        $brl = Currency::of('BRL');

        // Números: unidade por idioma.
        foreach ([
            [Language::PT_PT, '100 mil', '1 milhão', '1,5 milhões', '2 milhões'],
            [Language::EN, '100k', '1M', '1.5M', '2M'],
            [Language::FR, '100 k', '1 M', '1,5 M', '2 M'],
            [Language::ES, '100 mil', '1 millón', '1,5 millones', '2 millones'],
        ] as [$lang, $hundredK, $one, $oneHalf, $two]) {
            $this->assertSame($hundredK, PriceBandResolver::human(100000, $eur, $lang), $lang);
            $this->assertSame($one, PriceBandResolver::human(1000000, $eur, $lang), $lang);
            $this->assertSame($oneHalf, PriceBandResolver::human(1500000, $eur, $lang), $lang);
            $this->assertSame($two, PriceBandResolver::human(2000000, $eur, $lang), $lang);
        }
        $this->assertSame('750', PriceBandResolver::human(750, $eur, Language::EN));
        $this->assertSame('1,000', PriceBandResolver::human(1000, $eur, Language::EN));
        $this->assertSame('1.000', PriceBandResolver::human(1000, $eur, Language::FR));

        // Nomes por idioma, refeitos a partir do slug (Portugal, euro).
        $expect = [
            Language::EN => [
                'venda-ate-100000' => 'For sale · Up to €100k',
                'venda-100000-200000' => 'For sale · €100k to €200k',
                'venda-500000-1000000' => 'For sale · €500k to €1M',
                'venda-mais-1000000' => 'For sale · Over €1M',
                'arrendamento-750-1000' => 'For rent · €750 to €1,000/month',
                'sob-consulta' => 'Price on request',
            ],
            Language::FR => [
                'venda-ate-100000' => "Vente · Jusqu'à 100 k€",
                'venda-100000-200000' => 'Vente · 100 à 200 k€',
                'venda-500000-1000000' => 'Vente · 500 k à 1 M€',
                'venda-mais-1000000' => 'Vente · Plus de 1 M€',
                'arrendamento-750-1000' => 'Location · 750 à 1.000 €/mois',
                'sob-consulta' => 'Prix sur demande',
            ],
            Language::ES => [
                'venda-ate-100000' => 'Venta · Hasta 100 mil €',
                'venda-100000-200000' => 'Venta · 100 a 200 mil €',
                'venda-500000-1000000' => 'Venta · 500 mil a 1 millón €',
                'venda-mais-1000000' => 'Venta · Más de 1 millón €',
                'arrendamento-750-1000' => 'Alquiler · 750 a 1.000 €/mes',
                'sob-consulta' => 'Precio a consultar',
            ],
        ];
        foreach ($expect as $lang => $names) {
            foreach ($names as $slug => $name) {
                $this->assertSame($name, PriceBandResolver::localizedName($pt, $slug, $eur, $lang), $lang . ' ' . $slug);
            }
        }
        // Brasil (real): símbolo antes, repetido no intervalo.
        $this->assertSame('For sale · R$200k to R$400k', PriceBandResolver::localizedName($br, 'venda-200000-400000', $brl, Language::EN));
        $this->assertSame('For rent · R$1,000 to R$2,000/month', PriceBandResolver::localizedName($br, 'aluguel-1000-2000', $brl, Language::EN));
        $this->assertSame('Venta · R$ 1 millón a R$ 2 millones', PriceBandResolver::localizedName($br, 'venda-1000000-2000000', $brl, Language::ES));
        $this->assertSame('Venta · Más de R$ 2 millones', PriceBandResolver::localizedName($br, 'venda-mais-2000000', $brl, Language::ES));
        $this->assertSame(null, PriceBandResolver::localizedName($pt, 'nao-existe', $eur, Language::EN));

        // Português: byte a byte igual ao nome gravado, em qualquer das duas variantes.
        foreach ([[$pt, $eur], [$br, $brl]] as [$preset, $currency]) {
            foreach (PriceBandResolver::allTerms($preset, $currency) as $slug => $stored) {
                $this->assertSame($stored, PriceBandResolver::localizedName($preset, $slug, $currency, Language::PT_PT), 'pt-PT ' . $slug);
                $this->assertSame($stored, PriceBandResolver::localizedName($preset, $slug, $currency, Language::PT_BR), 'pt-BR ' . $slug);
            }
        }
        $this->assertSame('Venda · Até 100 mil €', PriceBandResolver::allTerms($pt, $eur)['venda-ate-100000']);

        $context = new class implements LanguageContextInterface {
            public string $now = Language::PT_PT;
            public function current(): string { return $this->now; }
            public function primary(): string { return Language::PT_PT; }
            public function available(): array { return [Language::PT_PT, Language::EN, Language::FR, Language::ES]; }
            public function ofPost(int $postId): string { return Language::PT_PT; }
        };
        Strings::useContext($context);

        // Gravação: com a página em inglês, o termo gravado continua com o nome português.
        $context->now = Language::EN;
        $persistence = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $persistence->seedTerms($pt);
        $stored = $GLOBALS['flashsite_test_terms']['fs_imovel_faixa'];
        $byslug = [];
        foreach ($stored as $id => $term) {
            $byslug[$term['slug']] = ['id' => $id, 'name' => $term['name']];
        }
        $this->assertSame('Venda · Até 100 mil €', $byslug['venda-ate-100000']['name'], 'Nome gravado fica em português.');
        $this->assertSame('Preço sob consulta', $byslug['sob-consulta']['name']);

        // Apresentação (get_terms, Taxonomy Filter do Elementor): só o objeto devolvido muda.
        $names = new BandTermNames(new FieldFormatter(new ItemReader(), new CollectionSettings(new OptionsStorage())));
        $names->register(['imovel' => $pt]);
        $term = static fn (string $slug): object => (object) ['term_id' => $byslug[$slug]['id'], 'slug' => $slug, 'name' => $byslug[$slug]['name'], 'taxonomy' => 'fs_imovel_faixa', 'parent' => 0];
        $other = (object) ['term_id' => 9, 'slug' => 'venda-ate-100000', 'name' => 'Venda · Até 100 mil €', 'taxonomy' => 'category'];

        foreach ([Language::EN, Language::FR, Language::ES] as $lang) {
            $context->now = $lang;
            $original = $term('venda-100000-200000');
            $out = $names->filterList([$original, $term('sob-consulta'), $other, 7]);
            $this->assertSame($expect[$lang]['venda-100000-200000'], $out[0]->name, $lang);
            $this->assertSame($expect[$lang]['sob-consulta'], $out[1]->name, $lang);
            $this->assertSame('venda-100000-200000', $out[0]->slug, 'Slug não muda.');
            $this->assertSame($byslug['venda-100000-200000']['id'], $out[0]->term_id, 'term_id não muda.');
            $this->assertSame('Venda · 100 a 200 mil €', $original->name, 'O objeto original não é alterado.');
            $this->assertSame('Venda · Até 100 mil €', $out[2]->name, 'Outras taxonomias não são tocadas.');
            $this->assertSame(7, $out[3]);
            $this->assertSame($expect[$lang]['venda-ate-100000'], $names->filterTerm($term('venda-ate-100000'))->name);
            // Idempotente: aplicar de novo ao já traduzido dá o mesmo.
            $this->assertSame($out[0]->name, $names->filterList($out)[0]->name);
        }
        $this->assertSame('Venda · 100 a 200 mil €', $GLOBALS['flashsite_test_terms']['fs_imovel_faixa'][$byslug['venda-100000-200000']['id']]['name'], 'Banco intacto.');

        // Português e contextos que não podem traduzir: nada muda.
        $context->now = Language::PT_PT;
        $this->assertSame('Venda · 100 a 200 mil €', $names->filterList([$term('venda-100000-200000')])[0]->name);
        $context->now = Language::PT_BR;
        $this->assertSame('Venda · 100 a 200 mil €', $names->filterList([$term('venda-100000-200000')])[0]->name);

        $context->now = Language::EN;
        $GLOBALS['flashsite_test_is_admin'] = true;
        $this->assertSame('Venda · 100 a 200 mil €', $names->filterList([$term('venda-100000-200000')])[0]->name, 'wp-admin não traduz.');
        $GLOBALS['flashsite_test_is_admin'] = false;
        $GLOBALS['flashsite_test_rest'] = true;
        $this->assertSame('Venda · 100 a 200 mil €', $names->filterList([$term('venda-100000-200000')])[0]->name, 'REST não traduz.');
        $GLOBALS['flashsite_test_rest'] = false;
        $this->assertSame('Venda · 100 a 200 mil €', BandTermNames::suspend(fn () => $names->filterList([$term('venda-100000-200000')])[0]->name), 'Gravação não traduz.');
        $this->assertSame('For sale · €100k to €200k', $names->filterList([$term('venda-100000-200000')])[0]->name, 'Fora da gravação volta a traduzir.');
        Strings::useContext(null);
    }
}
