<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\Visibility;
use FlashSite\Core\Modules\Collections\ItemPersistence;

final class PratoPresetTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections');
        $this->assertSame([], $registry->errors(), 'Presets sem erros: ' . json_encode($registry->errors()));
        $preset = $registry->get('prato');
        $this->assertTrue($preset !== null, 'Preset prato deve existir.');
        $this->assertSame('menu', $preset->slug());
        $this->assertCount(14, $preset->field('alergenios')->options, '14 alergénios da UE.');

        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $postId = 801;
        $errors = $p->saveFields($preset, $postId, ['preco' => '16,50', 'alergenios' => ['peixe', 'moluscos', 'inventado'], 'vegetariano' => '']);
        $this->assertSame([], $errors);
        $formatter = new FieldFormatter(new ItemReader());
        $this->assertSame('16,50 €', $formatter->price($preset, $postId));
        $this->assertSame('Peixe, Moluscos', $formatter->field($preset, $preset->field('alergenios'), $postId));

        // "Esgotado hoje" gera a regra que esconde o prato.
        $mq = Visibility::metaQuery($preset);
        $this->assertSame('OR', $mq['relation'] ?? null);
        $this->assertSame('fs_esgotado', $mq[0]['key'] ?? null);
    }
}
