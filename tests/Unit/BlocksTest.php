<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\Blocks\BlocksModule;
use FlashSite\Core\Modules\Blocks\Effects;
use FlashSite\Core\Modules\Blocks\FormHandler;

/** Módulo Blocos (3.6.1-blocks): formulário, bloco vazio escondido e atributos de efeito. */
final class BlocksTest extends TestCase
{
    public function run(): void
    {
        // Opções da lista: uma por linha, sem vazias nem espaços.
        $this->assertSame(['1 pessoa', '2 pessoas'], FormHandler::options("1 pessoa\r\n\n 2 pessoas \n"));

        // Nome técnico: o definido, senão gerado do rótulo.
        $this->assertSame('telefone', FormHandler::fieldName(['name' => 'Telefone']));
        $this->assertSame('nome', FormHandler::fieldName(['label' => 'Nome']));

        // Campos lidos do bloco gravado, mesmo dentro de grupos.
        $form = ['blockName' => 'flashsite/form', 'attrs' => ['formId' => 'r'], 'innerBlocks' => [
            ['blockName' => 'flashsite/form-field', 'attrs' => ['label' => 'Nome', 'name' => 'nome', 'required' => true], 'innerBlocks' => []],
            ['blockName' => 'core/group', 'attrs' => [], 'innerBlocks' => [
                ['blockName' => 'flashsite/form-field', 'attrs' => ['label' => 'Pessoas', 'name' => 'pessoas', 'type' => 'select', 'options' => "1\n2"], 'innerBlocks' => []],
            ]],
        ]];
        $fields = FormHandler::fieldsOf($form);
        $this->assertSame(2, count($fields));
        $this->assertSame(true, $fields[0]['required']);
        $this->assertSame(['1', '2'], $fields[1]['options']);

        // Bloco ligado ao Core com valor vazio não é impresso; com valor, passa igual.
        $module = new BlocksModule();
        $bound = ['attrs' => ['metadata' => ['bindings' => ['content' => ['source' => 'flashsite/item']]]]];
        $this->assertSame('', $module->dropEmptyBoundBlocks('<p class="selo"></p>', $bound));
        $this->assertSame('<p>Vegetariano</p>', $module->dropEmptyBoundBlocks('<p>Vegetariano</p>', $bound));
        // Ligação de outra fonte não é tocada.
        $other = ['attrs' => ['metadata' => ['bindings' => ['content' => ['source' => 'core/post-meta']]]]];
        $this->assertSame('<p></p>', $module->dropEmptyBoundBlocks('<p></p>', $other));

        // Efeitos: atributos só em blocos core/ e flashsite/.
        $fx = new Effects();
        $core = $fx->addAttributes(['attributes' => []], 'core/image');
        $this->assertSame(true, isset($core['attributes']['fsEffect'], $core['attributes']['fsDelay']));
        $third = $fx->addAttributes(['attributes' => []], 'outro/bloco');
        $this->assertSame(false, isset($third['attributes']['fsEffect']));
    }
}
