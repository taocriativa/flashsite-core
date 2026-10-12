<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Fonts\FontPlan;

/** Fontes locais (@since 3.2.0-beta.6): hierarquia, leitura do CSS do Google e CSS final. */
final class FontsTest extends TestCase
{
    public function run(): void
    {
        // Papel pela etiqueta da variável.
        $this->assertSame('titulo', FontPlan::roleFromLabel('surf-fonte-titulo'));
        $this->assertSame('titulo', FontPlan::roleFromLabel('font-heading'));
        $this->assertSame('texto', FontPlan::roleFromLabel('surf-fonte-texto'));

        // Variáveis do Elementor V4 no kit: só as de fonte, sem as apagadas.
        $record = json_encode(['data' => [
            'e-gv-1' => ['type' => 'global-font-variable', 'label' => 'surf-fonte-titulo', 'value' => 'Big Shoulders Display'],
            'e-gv-2' => ['type' => 'global-font-variable', 'label' => 'surf-fonte-texto', 'value' => '"Source Sans 3", sans-serif'],
            'e-gv-3' => ['type' => 'global-color-variable', 'label' => 'surf-coral', 'value' => '#E2603F'],
            'e-gv-4' => ['type' => 'global-font-variable', 'label' => 'velha', 'value' => 'Lato', 'deleted' => true],
        ]]);
        $this->assertSame(['surf-fonte-titulo' => 'Big Shoulders Display', 'surf-fonte-texto' => 'Source Sans 3'], FontPlan::fontVariables($record));

        // Endereço do Google só com os pesos pedidos (inválidos fora).
        $this->assertSame('https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap', FontPlan::googleCssUrl('Source Sans 3', [700, '400', 600, 450]));

        // CSS real do Google: só o subset latin; pesos do mesmo ficheiro (fonte variável) juntam-se.
        $faces = FontPlan::parseGoogleCss((string) file_get_contents(__DIR__ . '/../fixtures/google-fonts-css2.css'));
        $this->assertSame(4, count($faces));
        $groups = FontPlan::groupFaces($faces);
        $this->assertSame(2, count($groups));
        $source = array_values(array_filter($groups, static fn ($g) => $g['family'] === 'Source Sans 3'))[0];
        $this->assertSame([400, 600, 700], $source['weights']);
        $this->assertSame(true, str_starts_with($source['range'], 'U+0000-00FF'));

        // CSS final: @font-face local com intervalo de pesos, reserva com medidas e variável com a pilha.
        $metrics = ['Source Sans 3' => ['fallback' => 'Arial', 'size_adjust' => 92.06, 'ascent' => 111.23, 'descent' => 43.45, 'line_gap' => 0.0]];
        $css = FontPlan::css(
            ['Source Sans 3' => ['role' => 'texto', 'faces' => [['weights' => [400, 600, 700], 'style' => 'normal', 'file_url' => 'https://x.pt/wp-content/uploads/flashsite-fonts/ss3.woff2', 'range' => 'U+0000-00FF']]],
             'Fonte Rara' => ['role' => 'titulo', 'faces' => [['weights' => [800], 'style' => 'normal', 'file_url' => 'https://x.pt/f.woff2', 'range' => '']]]],
            ['surf-fonte-texto' => 'Source Sans 3', 'surf-fonte-titulo' => 'Fonte Rara', 'outra' => 'Nao Baixada'],
            $metrics
        );
        $this->assertSame(true, str_contains($css, '@font-face{font-family:"Source Sans 3";font-style:normal;font-weight:400 700;font-display:swap;src:url(https://x.pt/wp-content/uploads/flashsite-fonts/ss3.woff2) format("woff2");unicode-range:U+0000-00FF;}'));
        $this->assertSame(true, str_contains($css, '@font-face{font-family:"Source Sans 3 Reserva";src:local("Arial"),local("ArialMT"),local("Helvetica");size-adjust:92.06%;ascent-override:111.23%;descent-override:43.45%;line-gap-override:0%;}'));
        $this->assertSame(true, str_contains($css, '@font-face{font-family:"Fonte Rara Reserva";src:local("Arial"),local("ArialMT"),local("Helvetica");}'), 'Fonte fora da tabela: reserva sem ajuste.');
        $this->assertSame(true, str_contains($css, 'html:root{--surf-fonte-texto:"Source Sans 3","Source Sans 3 Reserva",Arial,sans-serif;--surf-fonte-titulo:"Fonte Rara","Fonte Rara Reserva",Arial,sans-serif}'));
        $this->assertSame(false, str_contains($css, 'Nao Baixada'));

        // Serifada: reserva Times New Roman e genérico serif.
        $this->assertSame('"Lora","Lora Reserva","Times New Roman",serif', FontPlan::stack('Lora', ['fallback' => 'Times New Roman']));

        // Tabela de medidas carregável.
        $table = require __DIR__ . '/../../config/fonts/metrics.php';
        $this->assertSame(true, count($table) >= 50 && isset($table['Inter'], $table['Playfair Display']));
    }
}
