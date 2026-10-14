<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../Support/WpPostStubs.php';
require_once __DIR__ . '/../Support/AbilitiesFixture.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Modules\Abilities\AbilitiesModule;

/**
 * Abilities (@since 3.5.0): compatibilidade com WordPress < 6.9, registo, metadados, schemas e permissões.
 */
final class AbilitiesRegistrationTest extends TestCase
{
    private const EXPECTED = [
        'flashsite/get-context', 'flashsite/list-collections', 'flashsite/get-collection-schema',
        'flashsite/activate-collection', 'flashsite/deactivate-collection',
        'flashsite/list-items', 'flashsite/get-item', 'flashsite/validate-item',
        'flashsite/create-item', 'flashsite/update-item', 'flashsite/trash-item',
        'flashsite/get-business-data', 'flashsite/update-business-data',
    ];

    public function run(): void
    {
        $this->compatibilityWithoutApi();
        $fx = new AbilitiesFixture();
        $this->registry($fx);
        $this->metaAndSchemas($fx);
        $this->permissions($fx);
        $this->forbiddenSurface($fx);
    }

    private function compatibilityWithoutApi(): void
    {
        // WP < 6.9: a API não existe. O módulo carrega, regista os seus hooks e não faz mais nada.
        $this->assertFalse(function_exists('wp_register_ability'), 'Este teste corre antes de simular a API.');
        flashsite_reset_test_state();
        $module = new AbilitiesModule([], new Logger());
        $module->register();
        $module->boot();
        $this->assertTrue($module->isActive());
        $this->assertSame('abilities', $module->getSlug());
        $this->assertArrayHasKey(AbilitiesModule::ABILITIES_HOOK, $GLOBALS['flashsite_test_hooks']);
        $module->registerCategory();
        $module->registerAbilities();
        $this->assertSame([], $module->definitions());
        $this->assertFalse(function_exists('wp_register_ability_category'), 'Sem polyfill da API.');
    }

    private function registry(AbilitiesFixture $fx): void
    {
        $this->assertTrue(function_exists('wp_register_ability'));
        $fx->module->registerCategory();
        $category = $GLOBALS['flashsite_test_ability_categories']['flashsite'] ?? null;
        $this->assertTrue(is_array($category), 'Categoria flashsite registada.');
        $this->assertTrue(($category['label'] ?? '') !== '' && ($category['description'] ?? '') !== '');

        $names = array_keys($fx->abilities);
        sort($names);
        $expected = self::EXPECTED;
        sort($expected);
        $this->assertSame($expected, $names, 'Superfície exata do Ability Pack v1.');
        foreach ($names as $name) {
            $this->assertTrue(str_starts_with($name, 'flashsite/'), "$name deve usar o namespace flashsite/.");
            $this->assertSame('flashsite', $fx->abilities[$name]['category']);
        }

        // Registo repetido de uma ability com problema não derruba as restantes.
        $before = count($GLOBALS['flashsite_test_registered_abilities']);
        $fx->module->registerAbilities();
        $this->assertSame($before, count($GLOBALS['flashsite_test_registered_abilities']));
    }

    private function metaAndSchemas(AbilitiesFixture $fx): void
    {
        $readonly = ['flashsite/get-context', 'flashsite/list-collections', 'flashsite/get-collection-schema', 'flashsite/list-items', 'flashsite/get-item', 'flashsite/validate-item', 'flashsite/get-business-data'];
        foreach ($fx->abilities as $name => $ability) {
            $this->assertTrue(is_callable($ability['execute_callback']), "$name: execute_callback");
            $this->assertTrue(is_callable($ability['permission_callback']), "$name: permission_callback obrigatório");
            $this->assertTrue(($ability['meta']['public'] ?? false) === true, "$name: meta.public");
            $this->assertTrue(($ability['meta']['mcp']['public'] ?? false) === true, "$name: meta.mcp.public");
            $this->assertSame(in_array($name, $readonly, true), $ability['meta']['annotations']['readonly'], "$name: readonly");
            $this->assertTrue(is_string($ability['label']) && is_string($ability['description']) && $ability['description'] !== '');
            $this->assertTrue(isset($ability['output_schema']), "$name: output_schema");
            $this->assertValidSchema($ability['output_schema'], "$name output");
            if (isset($ability['input_schema'])) {
                $this->assertSame('object', $ability['input_schema']['type']);
                $this->assertValidSchema($ability['input_schema'], "$name input");
            }
        }
        $perPage = $fx->abilities['flashsite/list-items']['input_schema']['properties']['per_page'];
        $this->assertSame(50, $perPage['maximum'], 'Paginação limitada.');
        $this->assertSame('draft', $fx->abilities['flashsite/create-item']['input_schema']['properties']['status']['default']);
        $this->assertTrue(! in_array('trash', $fx->abilities['flashsite/create-item']['input_schema']['properties']['status']['enum'], true));
        $collections = $fx->abilities['flashsite/get-item']['input_schema']['properties']['collection']['enum'];
        $this->assertTrue(in_array('imovel', $collections, true) && in_array('evento', $collections, true), 'enum derivado do Registry.');
    }

    private function permissions(AbilitiesFixture $fx): void
    {
        $item = ['collection' => 'imovel', 'id' => 5, 'title' => 'x'];
        // Não autenticado / sem capability: nenhuma ability corre.
        $fx->asUser([]);
        foreach (array_keys($fx->abilities) as $name) {
            $this->assertFalse($fx->allowed($name, $item), "$name deve negar sem capabilities.");
        }

        // Gestor de estrutura: coleções sim, itens e negócio não.
        $fx->asUser(['flashsite_manage_collections']);
        foreach (['get-context', 'list-collections', 'get-collection-schema', 'activate-collection', 'deactivate-collection'] as $name) {
            $this->assertTrue($fx->allowed("flashsite/$name", $item), "$name com flashsite_manage_collections");
        }
        foreach (['list-items', 'create-item', 'update-item', 'trash-item', 'get-business-data', 'update-business-data'] as $name) {
            $this->assertFalse($fx->allowed("flashsite/$name", $item), "$name sem a capability própria");
        }

        // Dados do Negócio.
        $fx->asUser(['flashsite_manage_business_data']);
        $this->assertTrue($fx->allowed('flashsite/get-business-data'));
        $this->assertTrue($fx->allowed('flashsite/update-business-data'));
        $this->assertTrue($fx->allowed('flashsite/get-context'));
        $this->assertFalse($fx->allowed('flashsite/activate-collection', $item));
        $this->assertFalse($fx->allowed('flashsite/create-item', $item));

        // Editor de uma coleção: só essa coleção.
        $fx->asUser(['edit_fs_imovel_items']);
        $this->assertTrue($fx->allowed('flashsite/create-item', $item));
        $this->assertTrue($fx->allowed('flashsite/list-items', $item));
        $this->assertFalse($fx->allowed('flashsite/create-item', ['collection' => 'prato', 'title' => 'x']));
        $this->assertFalse($fx->allowed('flashsite/activate-collection', $item));
        $this->assertFalse($fx->allowed('flashsite/update-business-data', ['data' => ['phone' => '1']]));
        $this->assertTrue($fx->allowed('flashsite/create-item', ['collection' => 'inexistente', 'title' => 'x']), 'Coleção desconhecida: erro estruturado em vez de proibido.');
    }

    private function forbiddenSurface(AbilitiesFixture $fx): void
    {
        $forbidden = ['option', 'meta', 'php', 'sql', 'shell', 'cli', 'file', 'plugin', 'theme', 'user', 'role', 'credential', 'release', 'delete-item', 'force'];
        foreach (array_keys($fx->abilities) as $name) {
            foreach ($forbidden as $word) {
                $this->assertFalse(str_contains($name, $word), "$name expõe superfície proibida ($word).");
            }
        }
        // Nenhum código de protocolo MCP no módulo.
        $source = '';
        foreach (glob(dirname(__DIR__, 2) . '/src/Modules/Abilities/*.php') ?: [] as $file) {
            $source .= (string) file_get_contents($file);
        }
        foreach (['jsonrpc', 'JSON-RPC', 'tools/list', 'McpServer', 'WP\\MCP'] as $needle) {
            $this->assertFalse(stripos($source, $needle) !== false, "Código de protocolo MCP no Core: $needle");
        }
    }

    /** @param array<string, mixed> $schema */
    private function assertValidSchema(array $schema, string $where): void
    {
        $valid = ['object', 'array', 'string', 'integer', 'number', 'boolean', 'null'];
        $types = (array) ($schema['type'] ?? []);
        $this->assertTrue($types !== [] && array_diff($types, $valid) === [], "$where: tipo inválido");
        foreach ((array) ($schema['properties'] ?? []) as $name => $child) {
            $this->assertTrue(is_array($child), "$where.$name");
            $this->assertValidSchema($child, "$where.$name");
        }
        foreach ((array) ($schema['required'] ?? []) as $required) {
            $this->assertTrue(isset($schema['properties'][$required]), "$where: required \"$required\" sem propriedade");
        }
        if (isset($schema['enum'])) {
            $this->assertTrue(is_array($schema['enum']) && $schema['enum'] !== [], "$where: enum");
        }
        if (isset($schema['items'])) {
            $this->assertValidSchema($schema['items'], "$where.items");
        }
    }
}
