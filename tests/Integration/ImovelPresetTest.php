<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Modules\Collections\ItemPersistence;

final class ImovelPresetTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        // O preset real carrega sem erros.
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections');
        $this->assertSame([], $registry->errors(), 'Presets distribuídos não podem ter erros: ' . json_encode($registry->errors()));
        $preset = $registry->get('imovel');
        $this->assertTrue($preset !== null, 'Preset imovel deve existir.');
        $this->assertSame('fs_imovel', $preset->postType());
        $this->assertSame('imoveis', $preset->slug());
        $this->assertSame('RealEstateListing', $preset->schemaType());

        // Privacidade: morada e notas internas nunca são públicas.
        $this->assertFalse($preset->field('morada')->public);
        $this->assertFalse($preset->field('notas_internas')->public);
        $this->assertSame('_fs_morada', $preset->field('morada')->metaKey());

        // Todos os nomes de taxonomia cabem no limite do WP.
        foreach ($preset->taxonomies() as $taxonomy) {
            $this->assertTrue(strlen($taxonomy->taxonomyName($preset->postType())) <= 32, 'Taxonomia demasiado longa: ' . $taxonomy->key);
        }

        // Faixas de preço.
        $this->assertSame(['venda-ate-100000', 'Venda · Até 100 mil €'], PriceBandResolver::resolve($preset, 95000.0, false, 'venda'));
        $this->assertSame(['venda-ate-100000', 'Venda · Até 100 mil €'], PriceBandResolver::resolve($preset, 100000.0, false, 'venda'));
        $this->assertSame(['venda-200000-300000', 'Venda · 200 a 300 mil €'], PriceBandResolver::resolve($preset, 285000.0, false, 'venda'));
        $this->assertSame(['venda-500000-1000000', 'Venda · 500 mil a 1 milhão €'], PriceBandResolver::resolve($preset, 750000.0, false, 'venda'));
        $this->assertSame(['venda-mais-1000000', 'Venda · Mais de 1 milhão €'], PriceBandResolver::resolve($preset, 1500000.0, false, 'venda'));
        $this->assertSame(['arrendamento-750-1000', 'Arrendamento · 750 a 1000 €/mês'], PriceBandResolver::resolve($preset, 900.0, false, 'arrendamento'));
        $this->assertSame(['sob-consulta', 'Preço sob consulta'], PriceBandResolver::resolve($preset, 900.0, true, 'venda'));
        $this->assertSame(null, PriceBandResolver::resolve($preset, null, false, 'venda'));
        $this->assertSame(null, PriceBandResolver::resolve($preset, 1000.0, false, null));
        $this->assertCount(13, PriceBandResolver::allTerms($preset), '6 faixas venda + 6 arrendamento + sob consulta.');
        $this->assertSame('1,5 milhões', PriceBandResolver::human(1500000));

        // Persistência: grava, valida, termos por defeito, faixa e capa.
        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $p->seedTerms($preset);
        $this->assertCount(4, $GLOBALS['flashsite_test_terms']['fs_imovel_estado'] ?? []);
        $this->assertCount(13, $GLOBALS['flashsite_test_terms']['fs_imovel_faixa'] ?? []);
        $p->seedTerms($preset);
        $this->assertCount(4, $GLOBALS['flashsite_test_terms']['fs_imovel_estado'], 'Semear é idempotente.');

        $postId = 501;
        $errors = $p->saveFields($preset, $postId, [
            'referencia' => 'IMO-0042',
            'preco' => '285.000,00',
            'area_util' => '92',
            'quartos' => '2',
            'classe_energetica' => 'B',
            'galeria' => '33,31,32',
            'morada' => 'Rua X, 10',
            'notas_internas' => 'Chave na portaria',
            'video_url' => 'https://example.test/tour',
        ]);
        $this->assertSame([], $errors);
        $this->assertSame(285000.0, $GLOBALS['flashsite_test_post_meta'][$postId]['fs_preco']);
        $this->assertSame([33, 31, 32], $GLOBALS['flashsite_test_post_meta'][$postId]['fs_galeria']);
        $this->assertSame('Rua X, 10', $GLOBALS['flashsite_test_post_meta'][$postId]['_fs_morada']);

        $termErrors = $p->saveTerms($preset, $postId, []);
        $this->assertSame([], $termErrors);
        $reader = new ItemReader();
        $this->assertSame(['venda'], wp_get_object_terms($postId, 'fs_imovel_finalidade', ['fields' => 'slugs']), 'Finalidade por defeito: venda.');
        $this->assertSame(['disponivel'], wp_get_object_terms($postId, 'fs_imovel_estado', ['fields' => 'slugs']), 'Estado por defeito: disponível.');
        $this->assertSame(['venda-200000-300000'], wp_get_object_terms($postId, 'fs_imovel_faixa', ['fields' => 'slugs']));

        $p->syncCover($preset, $postId);
        $this->assertSame(33, $GLOBALS['flashsite_test_thumbnails'][$postId], 'A primeira foto é a capa.');

        // Mudar para arrendamento recalcula a faixa.
        $arrendamento = term_exists('arrendamento', 'fs_imovel_finalidade');
        $p->saveFields($preset, $postId, ['preco' => '900']);
        $p->saveTerms($preset, $postId, ['finalidade' => [(string) $arrendamento['term_id']]]);
        $this->assertSame(['arrendamento-750-1000'], wp_get_object_terms($postId, 'fs_imovel_faixa', ['fields' => 'slugs']));

        // Single: dois termos enviados ficam só um.
        $reservado = term_exists('reservado', 'fs_imovel_estado');
        $vendido = term_exists('vendido', 'fs_imovel_estado');
        $p->saveTerms($preset, $postId, ['estado' => [$reservado['term_id'], $vendido['term_id']]]);
        $this->assertSame(['vendido'], wp_get_object_terms($postId, 'fs_imovel_estado', ['fields' => 'slugs']));

        // Termos de outra taxonomia são ignorados.
        $p->saveTerms($preset, $postId, ['tipo' => [$reservado['term_id']]]);
        $this->assertSame([], wp_get_object_terms($postId, 'fs_imovel_tipo', ['fields' => 'slugs']));

        // Zona: o cliente cria concelho e freguesia; finalidade (fixa) não aceita termos novos.
        $p->saveTerms($preset, $postId, [], ['zona' => ['name' => 'Lisboa'], 'finalidade' => ['name' => 'Permuta']]);
        $lisboa = term_exists('Lisboa', 'fs_imovel_zona');
        $this->assertTrue(is_array($lisboa));
        $this->assertSame(null, term_exists('Permuta', 'fs_imovel_finalidade'), 'Taxonomia fixa não aceita termos novos.');
        $p->saveTerms($preset, $postId, ['zona' => [$lisboa['term_id']]], ['zona' => ['name' => 'Alvalade', 'parent' => $lisboa['term_id']]]);
        $alvalade = term_exists('Alvalade', 'fs_imovel_zona');
        $this->assertSame($lisboa['term_id'], $GLOBALS['flashsite_test_terms']['fs_imovel_zona'][$alvalade['term_id']]['parent']);
        $this->assertCount(2, wp_get_object_terms($postId, 'fs_imovel_zona'));

        // Sob consulta: preço deixa de ser obrigatório e a faixa passa a "sob consulta".
        $errors = $p->saveFields($preset, $postId, ['preco' => '', 'preco_sob_consulta' => '1']);
        $this->assertSame([], $errors);
        $this->assertFalse(isset($GLOBALS['flashsite_test_post_meta'][$postId]['fs_preco']), 'Preço vazio remove a meta.');
        $p->syncPriceBand($preset, $postId);
        $this->assertSame(['sob-consulta'], wp_get_object_terms($postId, 'fs_imovel_faixa', ['fields' => 'slugs']));

        // Sem preço e sem "sob consulta": erro e validação antes de publicar.
        $errors = $p->saveFields($preset, $postId, ['preco' => '']);
        $this->assertArrayHasKey('preco', $errors);
        $pre = $p->validateInput($preset, $postId, ['preco' => '', 'preco_sob_consulta' => '0']);
        $this->assertArrayHasKey('preco', $pre);
        $this->assertSame([], $p->validateInput($preset, $postId, ['preco' => '120.000']));

        // Galeria vazia remove a capa.
        $p->saveFields($preset, $postId, ['galeria' => '', 'preco' => '1']);
        $p->syncCover($preset, $postId);
        $this->assertFalse(isset($GLOBALS['flashsite_test_thumbnails'][$postId]));

        // Leitura pública não expõe morada nem notas.
        $public = $reader->read($preset, $postId);
        $this->assertFalse(array_key_exists('morada', $public));
        $this->assertFalse(array_key_exists('notas_internas', $public));
    }
}
