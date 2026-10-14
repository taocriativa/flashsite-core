<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../Support/WpPostStubs.php';
require_once __DIR__ . '/../Support/AbilitiesFixture.php';

use FlashSite\Core\Modules\Collections\CollectionStateService;

/** Abilities de sistema e de catálogo/ativação de Coleções (@since 3.5.0). */
final class AbilitiesCollectionsTest extends TestCase
{
    public function run(): void
    {
        $this->context();
        $this->catalogAndSchema();
        $this->externalPreset();
        $this->activation();
    }

    private function context(): void
    {
        $fx = new AbilitiesFixture('BR', [dirname(__DIR__) . '/fixtures/external']);
        $fx->asUser(['flashsite_manage_collections']);
        $context = $fx->call('flashsite/get-context');
        $this->assertSame('BR', $context['market']);
        $this->assertSame('BRL', $context['currency']);
        $this->assertTrue($context['abilities_api']);
        $this->assertSame([], $context['collections_active']);
        $this->assertTrue(in_array('imovel', $context['collections_available'], true) && in_array('x_agenda', $context['collections_available'], true));
        $this->assertTrue($context['registry_errors'] !== [], 'Presets inválidos são reportados.');
        $json = (string) json_encode($context);
        foreach (['password', 'secret', 'token', 'salt', 'ABSPATH', '.env', 'application'] as $needle) {
            $this->assertFalse(stripos($json, $needle) !== false, "get-context não deve expor $needle");
        }
        $this->assertSame(['collection_not_found'], [$fx->call('flashsite/get-collection-schema', ['collection' => 'nao_existe'])->get_error_code()]);
    }

    private function catalogAndSchema(): void
    {
        $fx = new AbilitiesFixture('PT');
        $fx->asUser(['flashsite_manage_collections']);
        $list = $fx->call('flashsite/list-collections');
        $byKey = [];
        foreach ($list['collections'] as $row) {
            $byKey[$row['key']] = $row;
        }
        foreach (['imovel', 'plano', 'prato', 'servico', 'equipa', 'projeto', 'evento'] as $key) {
            $this->assertTrue(isset($byKey[$key]), "$key no catálogo");
            $this->assertSame('core', $byKey[$key]['origin']);
            $this->assertFalse($byKey[$key]['active'], 'Nenhuma coleção é ativada sozinha.');
        }
        $this->assertSame('fs_imovel', $byKey['imovel']['post_type']);
        $this->assertSame('imoveis', $byKey['imovel']['slug']);
        $this->assertTrue($byKey['imovel']['field_count'] > 5 && $byKey['imovel']['taxonomy_count'] >= 5);
        $this->assertTrue(in_array('price_drop', $byKey['imovel']['features'], true));
        $this->assertSame('PT', $byKey['imovel']['market']);

        $schema = $fx->call('flashsite/get-collection-schema', ['collection' => 'imovel']);
        $fields = [];
        foreach ($schema['fields'] as $field) {
            $fields[$field['key']] = $field;
            $this->assertFalse(isset($field['meta_key']), 'A meta key não faz parte do contrato do agente.');
        }
        $this->assertSame('money', $fields['preco']['type']);
        $this->assertSame('select', $fields['classe_energetica']['type']);
        $this->assertTrue(isset($fields['classe_energetica']['options']['a+']));
        $this->assertFalse($fields['morada']['public'], 'Campos privados identificados.');
        $this->assertTrue(str_contains((string) json_encode($schema), '_fs_') === false && str_contains((string) json_encode($schema), '"fs_') === false, 'Sem meta keys.');
        $taxonomies = array_column($schema['taxonomies'], null, 'key');
        $this->assertTrue($taxonomies['faixa']['auto']);
        $this->assertTrue($taxonomies['finalidade']['required'] && $taxonomies['finalidade']['single']);
        $this->assertTrue(isset($schema['settings']['price_drop']), 'Settings relevantes para edição.');
        $this->assertSame('PT', $schema['market']);
        $this->assertSame('EUR', $schema['currency']);

        // O mercado muda o contrato (BR: aluguel em vez de arrendamento).
        $br = new AbilitiesFixture('BR');
        $br->asUser(['flashsite_manage_collections']);
        $brSchema = $br->call('flashsite/get-collection-schema', ['collection' => 'imovel']);
        $this->assertTrue(isset(array_column($brSchema['taxonomies'], null, 'key')['finalidade']['terms']['aluguel']));
        $this->assertTrue(isset($taxonomies['finalidade']['terms']['arrendamento']));
    }

    private function externalPreset(): void
    {
        $fx = new AbilitiesFixture('PT', [dirname(__DIR__) . '/fixtures/external']);
        $fx->asUser(['flashsite_manage_collections']);
        $list = $fx->call('flashsite/list-collections');
        $external = array_values(array_filter($list['collections'], static fn (array $c): bool => $c['key'] === 'x_agenda'));
        $this->assertCount(1, $external, 'Preset externo aparece sem código específico.');
        $this->assertSame('external', $external[0]['origin']);
        $schema = $fx->call('flashsite/get-collection-schema', ['collection' => 'x_agenda']);
        $this->assertSame('date', $schema['fields'][0]['type']);
        $this->assertSame('external', $schema['origin']);
        $this->assertTrue($fx->call('flashsite/activate-collection', ['collection' => 'x_agenda'])['active']);
    }

    private function activation(): void
    {
        $fx = new AbilitiesFixture('PT');
        $changes = 0;
        $GLOBALS['flashsite_test_hooks'][CollectionStateService::CHANGED_ACTION][] = static function () use (&$changes): void { $changes++; };

        // Sem capability: negado, nada muda.
        $fx->asUser(['edit_fs_imovel_items']);
        $denied = $fx->call('flashsite/activate-collection', ['collection' => 'imovel']);
        $this->assertSame('ability_invalid_permissions', $denied->get_error_code());
        $this->assertFalse($fx->activation->isActive('imovel'));

        $fx->asUser(['flashsite_manage_collections']);
        $unknown = $fx->call('flashsite/activate-collection', ['collection' => 'nao_existe']);
        $this->assertSame('collection_not_found', $unknown->get_error_code());
        $this->assertSame([], $fx->activation->activeKeys());

        // Ativar: mesmos efeitos do painel (ação, capabilities, rewrite).
        $first = $fx->call('flashsite/activate-collection', ['collection' => 'imovel']);
        $this->assertTrue($first['active'] && $first['changed']);
        $this->assertTrue($fx->activation->isActive('imovel'));
        $this->assertSame(1, $changes, 'A ação flashsite_collections_changed corre uma vez.');
        $this->assertTrue(isset(get_role('administrator')->caps['edit_fs_imovel_items']), 'Capabilities sincronizadas.');
        $this->assertSame('1', (string) get_option('flashsite_collections_flush_rewrite'), 'Rewrite agendado.');

        // Idempotente: nada acontece.
        $again = $fx->call('flashsite/activate-collection', ['collection' => 'imovel']);
        $this->assertTrue($again['active'] && ! $again['changed']);
        $this->assertSame(1, $changes);
        $this->assertSame(['imovel'], $fx->activation->activeKeys());
        // Não mexe em mercado, moeda nem idiomas.
        $this->assertSame('PT', $fx->settings->market());
        $this->assertSame([], $fx->settings->perLanguageKeys());

        // Desativar nunca apaga conteúdo.
        $fx->activate('imovel');
        $fx->asUser(array_merge(['flashsite_manage_collections'], AbilitiesFixture::managerCaps('fs_imovel_items')));
        $created = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'T2 Alvalade', 'fields' => ['referencia' => 'IMO-1', 'preco' => '285000']]);
        $id = $created['item']['id'];
        $off = $fx->call('flashsite/deactivate-collection', ['collection' => 'imovel']);
        $this->assertFalse($off['active']);
        $this->assertTrue($off['changed']);
        $this->assertTrue(isset($GLOBALS['flashsite_test_posts'][$id]), 'Itens preservados ao desativar.');
        $this->assertSame('draft', $GLOBALS['flashsite_test_posts'][$id]->post_status);
        $this->assertSame(2, $changes);
        $offAgain = $fx->call('flashsite/deactivate-collection', ['collection' => 'imovel']);
        $this->assertFalse($offAgain['changed']);
        $this->assertSame(2, $changes);
        $this->assertFalse(isset(get_role('administrator')->caps['edit_fs_imovel_items']), 'Capabilities removidas ao desativar.');

        // Um único fluxo: o painel usa o mesmo serviço.
        $page = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Modules/Collections/Admin/CollectionsPage.php');
        $this->assertTrue(str_contains($page, 'CollectionStateService::notifyChanged()') && str_contains($page, '$this->state?->replace'));
        $this->assertSame('flashsite_collections_changed', CollectionStateService::CHANGED_ACTION);
    }
}
