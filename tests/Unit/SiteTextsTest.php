<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\Strings;
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

        $this->multilingual();
    }

    /** Vários idiomas (@since 3.2.0-beta.3): mesma chave em cada página, valor por idioma. */
    private function multilingual(): void
    {
        flashsite_reset_test_state();
        $context = new class implements LanguageContextInterface {
            public string $now = Language::PT_PT;
            public function current(): string { return $this->now; }
            public function primary(): string { return Language::PT_PT; }
            public function available(): array { return [Language::PT_PT, Language::EN, Language::FR]; }
            public function ofPost(int $postId): string { return [20 => Language::PT_PT, 30 => Language::EN, 40 => Language::FR][$postId] ?? Language::PT_PT; }
        };
        Strings::useContext($context);

        $registry = new SiteTextsRegistry(new OptionsStorage());
        $field = static fn (string $default): array => ['key' => 'inicio_titulo', 'label' => 'Título', 'type' => 'line', 'default' => $default, 'section' => 'Topo', 'element' => 'a1', 'max' => 0];
        $registry->syncPage(20, [$field('Apanhe a sua primeira onda.')], $context->ofPost(20));
        $registry->syncPage(30, [$field('Catch your first wave.')], $context->ofPost(30));
        $registry->syncPage(40, [$field('Prenez votre première vague.')], $context->ofPost(40));

        // Principal com a chave simples (sites de um idioma não mudam); os outros com @idioma.
        $this->assertSame(['inicio_titulo', 'inicio_titulo@en', 'inicio_titulo@fr'], array_keys($registry->fields()));
        $this->assertSame([Language::PT_PT, Language::EN, Language::FR], $registry->languages());
        $this->assertSame(['inicio_titulo@en'], array_keys($registry->fieldsFor(Language::EN)));
        $this->assertSame(['inicio_titulo'], array_keys($registry->fieldsFor('')));

        $changed = $registry->saveValues(['inicio_titulo@en' => 'Your first wave in Ericeira', 'inicio_titulo@xx' => 'ignorado']);
        $this->assertSame(['inicio_titulo@en'], $changed);
        $this->assertSame('', SiteTextsRegistry::sanitizeId('inicio_titulo@xx'));
        $this->assertSame('inicio_titulo', SiteTextsRegistry::sanitizeId('inicio_titulo@pt-PT'));
        $this->assertSame('Apanhe a sua primeira onda.', $registry->text('inicio_titulo', 'Apanhe a sua primeira onda.', Language::PT_PT));

        // Cada página mostra o valor do seu idioma; sem valor, o padrão da própria página.
        $context->now = Language::EN;
        $this->assertSame('Your first wave in Ericeira', $registry->text('inicio_titulo', 'Catch your first wave.'));
        $context->now = Language::FR;
        $this->assertSame('Prenez votre première vague.', $registry->text('inicio_titulo', 'Prenez votre première vague.'));
        $context->now = Language::PT_PT;
        $this->assertSame('Apanhe a sua primeira onda.', $registry->text('inicio_titulo', 'Apanhe a sua primeira onda.'));

        // Campos de antes da 3.2 (sem idioma) contam como do idioma principal.
        flashsite_reset_test_state();
        $legacy = new SiteTextsRegistry(new OptionsStorage());
        $legacy->syncPage(72, [$field('Seu próximo imóvel')]);
        $legacy->saveValues(['inicio_titulo' => 'Novo']);
        $this->assertSame('Novo', $legacy->text('inicio_titulo'));
        $this->assertSame([Language::PT_PT], $legacy->languages());
        Strings::useContext(null);
    }
}
