<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Modules\Collections\ItemPersistence;

final class PlanoPresetTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections');
        $this->assertSame([], $registry->errors(), 'Presets distribuídos sem erros: ' . json_encode($registry->errors()));
        $preset = $registry->get('plano');
        $this->assertTrue($preset !== null, 'Preset plano deve existir.');
        $this->assertSame('fs_plano', $preset->postType());
        $this->assertSame('Service', $preset->schemaType());
        $this->assertFalse($preset->field('notas_internas')->public);

        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $postId = 701;
        $errors = $p->saveFields($preset, $postId, [
            'preco' => '149,00',
            'periodicidade' => 'mes',
            'preco_desde' => '1',
            'inclui' => "2 sessões por semana\nPlano na app\n\nCheck-in semanal",
        ]);
        $this->assertSame([], $errors);
        $this->assertSame(['2 sessões por semana', 'Plano na app', 'Check-in semanal'], $GLOBALS['flashsite_test_post_meta'][$postId]['fs_inclui']);

        $formatter = new FieldFormatter(new ItemReader());
        $this->assertSame('desde 149,00 €/mês', $formatter->price($preset, $postId));
        $this->assertSame('desde 149 €/mês', $formatter->price($preset, $postId, FieldFormatter::MONEY_AUTO));

        // Sem preço e sem "sob consulta": erro; com "sob consulta": texto próprio.
        $this->assertTrue($p->saveFields($preset, $postId, ['preco' => '']) !== []);
        $p->saveFields($preset, $postId, ['preco' => '', 'preco_sob_consulta' => '1']);
        $this->assertSame(FieldFormatter::ON_REQUEST_TEXT, $formatter->price($preset, $postId));
    }
}
