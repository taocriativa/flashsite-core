<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\Hardening\HardeningModule;

final class HardeningHiddenLinksTest extends TestCase
{
    public function run(): void
    {
        $domains = ['full.services'];

        // O caso real do FULL.Cliente 4.1.7, no fim do wp_footer.
        $real = '</script><a href="https://full.services/" style="visibility: hidden; user-select: none; pointer-events: none; display: none;">plugins premium WordPress</a>			<script>';
        $this->assertSame('</script><script>', HardeningModule::removeHiddenLinks($real, $domains));

        // Só visibility:hidden, aspas simples e subdomínio.
        $variant = "<p>x</p><a style='visibility:hidden' href='https://painel.full.services/loja'>x</a><p>y</p>";
        $this->assertSame('<p>x</p><p>y</p>', HardeningModule::removeHiddenLinks($variant, $domains));

        // Link visível para o mesmo domínio fica.
        $visible = '<a href="https://full.services/">FULL</a>';
        $this->assertSame($visible, HardeningModule::removeHiddenLinks($visible, $domains));

        // Link oculto para outro domínio fica (a regra é só para a lista).
        $other = '<a href="https://exemplo.pt/" style="display:none">menu</a>';
        $this->assertSame($other, HardeningModule::removeHiddenLinks($other, $domains));

        // Domínio parecido não é apanhado.
        $lookalike = '<a href="https://notfull.services/" style="display:none">x</a>';
        $this->assertSame($lookalike, HardeningModule::removeHiddenLinks($lookalike, $domains));

        // Sem domínios ou sem ocorrência, o HTML passa igual.
        $this->assertSame($real, HardeningModule::removeHiddenLinks($real, []));
        $this->assertSame('<p>nada</p>', HardeningModule::removeHiddenLinks('<p>nada</p>', $domains));
    }
}
