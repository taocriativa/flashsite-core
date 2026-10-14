<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionPreset;
use FlashSite\Core\Domain\Collections\SchemaMap;

/**
 * 3.4.0-beta.2: settings.schema_map (declarativo, só valores simples, só campos públicos).
 */
final class SchemaMapTest extends TestCase
{
    public function run(): void
    {
        $base = [
            'key' => 'mapa',
            'labels' => ['singular' => 'A', 'plural' => 'B'],
            'groups' => ['principal' => 'P', 'privado' => 'X'],
            'taxonomies' => ['estado' => ['label' => 'Estado', 'terms' => ['ok' => 'Ok', 'nok' => 'Nok']]],
            'fields' => [
                'quando' => ['type' => 'date', 'label' => 'Quando'],
                'cargo' => ['type' => 'text', 'label' => 'Cargo'],
                'lotacao' => ['type' => 'number', 'label' => 'Lotação'],
                'segredo' => ['type' => 'text', 'label' => 'Segredo', 'group' => 'privado', 'public' => false],
                'fotos' => ['type' => 'gallery', 'label' => 'Fotos'],
                'itens' => ['type' => 'list', 'label' => 'Itens'],
                'preco' => ['type' => 'money', 'label' => 'Preço'],
                'ativo' => ['type' => 'bool', 'label' => 'Ativo'],
            ],
        ];
        $with = static fn (array $map): CollectionPreset => CollectionPreset::fromArray($base + ['settings' => ['schema_map' => $map]]);

        // Campo válido, campo ausente e campo privado.
        $preset = $with(['jobTitle' => 'cargo', 'startDate' => 'quando', 'maximumAttendeeCapacity' => 'lotacao', 'x' => 'nao_existe', 'secret' => 'segredo']);
        $this->assertSame(['jobTitle', 'startDate', 'maximumAttendeeCapacity'], array_keys(SchemaMap::entries($preset)), 'Inexistente e privado ficam de fora.');
        $item = ['fields' => ['cargo' => ' Sócia ', 'quando' => '2026-11-10', 'lotacao' => 120.0, 'segredo' => 'vazou?'], 'terms' => []];
        $out = SchemaMap::apply($preset, $item, ['@type' => 'Event', 'name' => 'N']);
        $this->assertSame(['@type' => 'Event', 'name' => 'N', 'jobTitle' => 'Sócia', 'startDate' => '2026-11-10', 'maximumAttendeeCapacity' => 120.0], $out);
        $this->assertFalse(str_contains((string) json_encode($out), 'vazou'), 'Campo privado nunca vaza, nem se vier no item.');

        // Valores vazios ou inválidos não geram propriedade.
        $out = SchemaMap::apply($preset, ['fields' => ['cargo' => '  ', 'quando' => '10/11/2026', 'lotacao' => null], 'terms' => []], []);
        $this->assertSame([], $out);

        // Mapeamentos inválidos: tipos não simples, nome de propriedade inválido, reservadas, taxonomia desconhecida, lista vazia.
        $preset = $with([
            'photos' => 'fotos', 'itemListElement' => 'itens', 'price' => 'preco', 'flag' => 'ativo',
            '1invalida' => 'cargo', 'com-hifen' => 'cargo', 'name' => 'cargo', 'url' => 'cargo', 'offers' => 'cargo', 'image' => 'cargo', 'description' => 'cargo',
            'lista' => ['cargo', 'quando'], 'semMapa' => ['taxonomy' => 'estado'], 'taxDesconhecida' => ['taxonomy' => 'nao', 'map' => ['ok' => 'x']],
            'mapaVazio' => ['taxonomy' => 'estado', 'map' => []], 'numero' => 5,
        ]);
        $this->assertSame([], SchemaMap::entries($preset), 'Nada disto é um mapeamento válido.');

        // Taxonomia com mapa termo -> valor.
        $preset = $with(['eventStatus' => ['taxonomy' => 'estado', 'map' => ['nok' => 'https://schema.org/EventCancelled', 'vazio' => '']]]);
        $this->assertSame(['eventStatus'], array_keys(SchemaMap::entries($preset)));
        $this->assertSame(['eventStatus' => 'https://schema.org/EventCancelled'], SchemaMap::apply($preset, ['fields' => [], 'terms' => ['estado' => [['slug' => 'nok', 'name' => 'Nok']]]], []));
        $this->assertSame([], SchemaMap::apply($preset, ['fields' => [], 'terms' => ['estado' => [['slug' => 'ok', 'name' => 'Ok']]]], []), 'Termo sem valor mapeado: sem propriedade.');
        $this->assertSame([], SchemaMap::apply($preset, ['fields' => [], 'terms' => []], []));

        // O que o builder já produz não é substituído.
        $preset = $with(['jobTitle' => 'cargo']);
        $this->assertSame(['jobTitle' => 'Original'], SchemaMap::apply($preset, ['fields' => ['cargo' => 'Outro'], 'terms' => []], ['jobTitle' => 'Original']));

        // Sem schema_map (ou com valor inválido) nada muda.
        $none = CollectionPreset::fromArray($base);
        $this->assertSame([], SchemaMap::entries($none));
        $data = ['@type' => 'Thing', 'name' => 'N'];
        $this->assertSame($data, SchemaMap::apply($none, ['fields' => ['cargo' => 'X'], 'terms' => []], $data));
        $broken = CollectionPreset::fromArray($base + ['settings' => ['schema_map' => 'isto-nao-e-lista']]);
        $this->assertSame($data, SchemaMap::apply($broken, ['fields' => ['cargo' => 'X'], 'terms' => []], $data));
    }
}
