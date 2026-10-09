<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\SiteTexts\SiteTextsRegistry;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/** Textos do site (@since 3.1.0): campos lidos das páginas do Elementor e valores do cliente. */
final class SiteTextsTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $atomic = static fn (array $settings): array => ['$$type' => 'dynamic', 'value' => ['name' => 'flashsite-site-text', 'settings' => $settings]];
        $data = [[
            'elType' => 'e-flexbox',
            'elements' => [
                ['widgetType' => 'e-heading', 'settings' => ['title' => $atomic([
                    'key' => ['$$type' => 'string', 'value' => 'Início Título'],
                    'label' => ['$$type' => 'string', 'value' => 'Título do topo'],
                    'section' => ['$$type' => 'string', 'value' => 'Topo'],
                    'default' => ['$$type' => 'string', 'value' => 'Seu próximo imóvel'],
                ])]],
                ['widgetType' => 'e-paragraph', 'settings' => ['paragraph' => $atomic(['key' => 'inicio_texto', 'type' => 'paragraph', 'default' => 'Faço a ponte.'])]],
                // Clássico: tag codificada em __dynamic__.
                ['widgetType' => 'heading', 'settings' => ['__dynamic__' => ['title' => '[elementor-tag id="a1" name="flashsite-site-text" settings="' . urlencode(json_encode(['key' => 'rodape_frase', 'label' => 'Frase do rodapé', 'default' => 'Fale comigo'])) . '"]']]],
                // Repetida: conta uma vez. Sem chave: ignorada.
                ['widgetType' => 'e-heading', 'settings' => ['title' => $atomic(['key' => 'inicio_titulo', 'default' => 'outro'])]],
                ['widgetType' => 'e-heading', 'settings' => ['title' => $atomic(['label' => 'sem chave'])]],
            ],
        ]];

        $found = SiteTextsRegistry::extract($data);
        $this->assertSame(3, count($found));
        $this->assertSame('inicio_titulo', $found[0]['key']);
        $this->assertSame('Título do topo', $found[0]['label']);
        $this->assertSame('Topo', $found[0]['section']);
        $this->assertSame('line', $found[0]['type']);
        $this->assertSame('paragraph', $found[1]['type']);
        $this->assertSame('inicio_texto', $found[1]['label']); // sem rótulo: usa a chave
        $this->assertSame('Fale comigo', $found[2]['default']);

        $registry = new SiteTextsRegistry(new OptionsStorage());
        $registry->syncPage(72, $found);
        $registry->syncPage(105, [$found[2]]);
        $this->assertSame([72, 105], $registry->fields()['rodape_frase']['pages']);

        // Cliente muda dois textos; igual ao padrão não guarda; chave desconhecida ignorada.
        $changed = $registry->saveValues([
            'inicio_titulo' => "  Novo <b>título</b>\n",
            'inicio_texto' => "Linha 1\r\n\r\n\r\n\r\nLinha 2",
            'rodape_frase' => 'Fale comigo',
            'invasor' => 'x',
        ]);
        $this->assertSame(['inicio_titulo', 'inicio_texto'], $changed);
        $this->assertSame('Novo título', $registry->text('inicio_titulo', 'Seu próximo imóvel'));
        $this->assertSame("Linha 1\n\nLinha 2", $registry->text('inicio_texto'));
        $this->assertSame('Fale comigo', $registry->text('rodape_frase', 'Fale comigo'));
        $this->assertSame(false, isset($registry->values()['invasor']));

        // Tamanho previsto: sai do texto padrão (+30%, mínimo 20 numa linha); trava com 20% de folga.
        $this->assertSame(['max' => 25, 'hard' => 30], SiteTextsRegistry::limit(['type' => 'line', 'default' => 'Seu próximo imóvel']));
        $this->assertSame(['max' => 70, 'hard' => 84], SiteTextsRegistry::limit(['type' => 'line', 'default' => 'x', 'max' => 70]));
        $this->assertSame(80, SiteTextsRegistry::limit(['type' => 'paragraph', 'default' => ''])['max']);
        $registry->saveValues(['inicio_titulo' => str_repeat('a', 26)]);
        $this->assertSame(['inicio_titulo'], $registry->overLimit());
        $registry->saveValues(['inicio_titulo' => str_repeat('b', 50)]);
        $this->assertSame(30, mb_strlen($registry->text('inicio_titulo')));

        // Campo vazio volta ao padrão.
        $registry->saveValues(['inicio_titulo' => '']);
        $this->assertSame('Seu próximo imóvel', $registry->text('inicio_titulo', 'Seu próximo imóvel'));

        // Página sem a tag: os campos só dela saem da lista; os partilhados ficam.
        $registry->syncPage(72, []);
        $this->assertSame(false, isset($registry->fields()['inicio_titulo']));
        $this->assertSame([105], $registry->fields()['rodape_frase']['pages']);
    }
}
