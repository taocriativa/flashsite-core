<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionPreset;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldType;

final class CollectionPresetTest extends TestCase
{
    public function run(): void
    {
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__) . '/fixtures/collections');

        // Preset inválido é ignorado e reportado, sem derrubar o carregamento.
        $this->assertTrue($registry->has('teste'), 'Preset de teste deve carregar.');
        $this->assertFalse($registry->has('invalido'), 'Preset inválido não pode ser registado.');
        $this->assertArrayHasKey('invalido.php', $registry->errors());

        $preset = $registry->get('teste');
        $this->assertSame('fs_teste', $preset->postType());
        $this->assertSame('testes', $preset->slug());
        $this->assertSame('fs_teste_items', $preset->capabilityPlural());
        $this->assertSame($preset, $registry->byPostType('fs_teste'));

        // Meta keys: públicos fs_*, privados _fs_*.
        $this->assertSame('fs_preco', $preset->field('preco')->metaKey());
        $this->assertSame('_fs_notas_internas', $preset->field('notas_internas')->metaKey());
        $this->assertFalse(in_array('notas_internas', array_map(static fn ($f) => $f->key, $preset->publicFields()), true), 'Campo privado não pode estar nos públicos.');

        $this->assertSame(FieldType::Money, $preset->field('preco')->type);
        $this->assertSame('array', FieldType::Gallery->metaType());
        $this->assertSame('object', FieldType::Geo->metaType());

        // Taxonomias: termos fixos ficam "locked"; zona é gerida pelo cliente.
        $estado = $preset->taxonomies()[0];
        $this->assertSame('estado', $estado->key);
        $this->assertTrue($estado->locked);
        $this->assertSame('fs_teste_estado', $estado->taxonomyName('fs_teste'));
        $this->assertFalse($preset->taxonomies()[1]->locked);

        // Revisões e custom-fields são sempre suportados.
        $this->assertTrue(in_array('revisions', $preset->supports(), true));
        $this->assertTrue(in_array('custom-fields', $preset->supports(), true));

        // Validação estrutural.
        $this->assertThrows(static fn () => CollectionPreset::fromArray(['key' => 'X!', 'labels' => ['singular' => 'a', 'plural' => 'b'], 'fields' => ['a' => ['type' => 'text', 'label' => 'A']]]), 'Chave inválida deve falhar.');
        $this->assertThrows(static fn () => CollectionPreset::fromArray(['key' => 'semcampos', 'labels' => ['singular' => 'a', 'plural' => 'b']]), 'Preset sem campos deve falhar.');
        $this->assertThrows(static fn () => CollectionPreset::fromArray(['key' => 'semopcoes', 'labels' => ['singular' => 'a', 'plural' => 'b'], 'fields' => ['s' => ['type' => 'select', 'label' => 'S']]]), 'Select sem opções deve falhar.');
        $this->assertThrows(static fn () => CollectionPreset::fromArray(['key' => 'grupo', 'labels' => ['singular' => 'a', 'plural' => 'b'], 'fields' => ['a' => ['type' => 'text', 'label' => 'A', 'group' => 'nao_existe']]]), 'Grupo inexistente deve falhar.');
        $this->assertThrows(static fn () => CollectionPreset::fromArray(['key' => 'longo', 'post_type' => 'fs_tipo_muito_compri', 'labels' => ['singular' => 'a', 'plural' => 'b'], 'taxonomies' => ['taxonomia_longa' => ['label' => 'T']], 'fields' => ['a' => ['type' => 'text', 'label' => 'A']]]), 'Taxonomia acima de 32 caracteres deve falhar.');

        // Conflito de slug entre presets é recusado.
        $dup = CollectionPreset::fromArray(['key' => 'outro', 'slug' => 'testes', 'labels' => ['singular' => 'a', 'plural' => 'b'], 'fields' => ['a' => ['type' => 'text', 'label' => 'A']]]);
        $registry->add($dup);
        $this->assertFalse($registry->has('outro'), 'Slug duplicado deve ser recusado.');
    }

    private function assertThrows(callable $fn, string $message): void
    {
        try {
            $fn();
        } catch (InvalidArgumentException) {
            return;
        }
        throw new RuntimeException($message);
    }
}
