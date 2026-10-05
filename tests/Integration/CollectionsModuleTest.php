<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Access\RoleManager;
use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Modules\Collections\Admin\ItemEditor;
use FlashSite\Core\Modules\Collections\Admin\ListColumns;
use FlashSite\Core\Modules\Collections\ItemPersistence;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Collections\CollectionsModule;

final class CollectionsModuleTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        (new RoleManager(dirname(__DIR__, 2) . '/config/capabilities.php'))->ensureRole();

        $storage = new OptionsStorage();
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__) . '/fixtures/collections');
        $activation = new ActivationRepository($storage);
        $caps = new CollectionCapabilities($storage);
        $persistence = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $module = new CollectionsModule($registry, $activation, $caps, new ItemSanitizer(), $storage, new Logger(), $persistence, new ItemEditor($persistence, new ItemReader()), new ListColumns(new ItemReader()));
        $module->register();

        // 1. Sem presets ativos: nada é registado (sites atuais não mudam).
        $module->registerContent();
        $this->assertSame([], $GLOBALS['flashsite_test_post_types']);
        $module->syncCapabilities();
        $manager = get_role(CollectionCapabilities::SITE_MANAGER_ROLE);
        $admin = get_role('administrator');
        $this->assertArrayHasKey('upload_files', $manager->caps, 'Gestor de Site deve poder carregar fotos.');
        $this->assertArrayHasKey(CollectionCapabilities::MANAGE_CAP, $admin->caps);
        $this->assertFalse(isset($manager->caps[CollectionCapabilities::MANAGE_CAP]), 'Gestor de Site não pode gerir presets.');
        $this->assertFalse(isset($manager->caps['edit_fs_teste_items']));

        // 2. Ativar: CPT, taxonomias e meta registados; caps atribuídas.
        $this->assertTrue($activation->activate('teste'));
        $this->assertTrue($activation->activate('teste'), 'activate é idempotente');
        do_action('flashsite_collections_changed');
        $module->registerContent();

        $cpt = $GLOBALS['flashsite_test_post_types']['fs_teste'] ?? null;
        $this->assertTrue(is_array($cpt), 'CPT fs_teste deve estar registado.');
        $this->assertSame('testes', $cpt['has_archive']);
        $this->assertSame(['slug' => 'testes', 'with_front' => false], $cpt['rewrite']);
        $this->assertSame(['fs_teste', 'fs_teste_items'], $cpt['capability_type']);
        $this->assertTrue($cpt['map_meta_cap']);
        $this->assertFalse($cpt['delete_with_user'], 'Apagar um utilizador nunca apaga itens.');

        $estado = $GLOBALS['flashsite_test_taxonomies']['fs_teste_estado']['args'];
        $this->assertSame(CollectionCapabilities::MANAGE_CAP, $estado['capabilities']['manage_terms'], 'Termos fixos só pelo administrador.');
        $this->assertSame('edit_fs_teste_items', $estado['capabilities']['assign_terms']);
        $zona = $GLOBALS['flashsite_test_taxonomies']['fs_teste_zona']['args'];
        $this->assertSame('edit_fs_teste_items', $zona['capabilities']['manage_terms'], 'Cliente gere as zonas.');
        $this->assertTrue($zona['hierarchical']);

        $meta = $GLOBALS['flashsite_test_post_meta_registry']['fs_teste'];
        $this->assertSame('number', $meta['fs_preco']['type']);
        $this->assertSame(['schema' => ['type' => 'number']], $meta['fs_preco']['show_in_rest']);
        $this->assertSame(false, $meta['_fs_notas_internas']['show_in_rest'], 'Campo privado nunca vai para a REST.');
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'integer']], $meta['fs_galeria']['show_in_rest']['schema']);
        $this->assertTrue($meta['fs_preco']['revisions_enabled']);
        $this->assertSame(285000.0, ($meta['fs_preco']['sanitize_callback'])('285.000,00'));

        $this->assertArrayHasKey('edit_fs_teste_items', $manager->caps);
        $this->assertArrayHasKey('publish_fs_teste_items', $manager->caps);
        $this->assertArrayHasKey('delete_others_fs_teste_items', $admin->caps);

        // Flush de rewrite rules só uma vez após a mudança.
        $module->maybeFlushRewriteRules();
        $module->maybeFlushRewriteRules();
        $this->assertSame(1, $GLOBALS['flashsite_test_rewrite_flushes']);

        // Gutenberg desligado só nas coleções.
        $this->assertFalse($module->disableBlockEditor(true, 'fs_teste'));
        $this->assertTrue($module->disableBlockEditor(true, 'page'));

        // 3. Leitura com projeção pública.
        update_post_meta(10, 'fs_preco', 1500.5);
        update_post_meta(10, '_fs_notas_internas', 'Chave na portaria');
        update_post_meta(10, 'fs_galeria', ['3', '1']);
        $reader = new ItemReader();
        $public = $reader->read($registry->get('teste'), 10);
        $this->assertFalse(array_key_exists('notas_internas', $public), 'Leitura pública não inclui privados.');
        $this->assertSame(1500.5, $public['preco']);
        $this->assertSame([3, 1], $public['galeria']);
        $this->assertSame(false, $public['preco_sob_consulta']);
        $full = $reader->read($registry->get('teste'), 10, true);
        $this->assertSame('Chave na portaria', $full['notas_internas']);

        // 4. Desativar: CPT deixa de ser registado, caps saem, dados ficam.
        $this->assertTrue($activation->deactivate('teste'));
        do_action('flashsite_collections_changed');
        $GLOBALS['flashsite_test_post_types'] = [];
        $module->registerContent();
        $this->assertSame([], $GLOBALS['flashsite_test_post_types']);
        $this->assertFalse(isset($manager->caps['edit_fs_teste_items']));
        $this->assertSame(1500.5, $GLOBALS['flashsite_test_post_meta'][10]['fs_preco'], 'Desativar nunca apaga dados.');

        // 5. replace() descarta chaves desconhecidas.
        $activation->replace(['teste', 'fantasma'], $registry->keys());
        $this->assertSame(['teste'], $activation->activeKeys());
    }
}
