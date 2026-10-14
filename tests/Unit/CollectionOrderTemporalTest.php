<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionPublicSerializer;
use FlashSite\Core\Domain\Collections\CollectionQuery;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\SchemaOrgBuilder;
use FlashSite\Core\Modules\Collections\Output\CollectionsOutput;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\OrderResolver;
use FlashSite\Core\Domain\Collections\TemporalResolver;

/**
 * 3.4.0-beta.1: settings.order, settings.order_past e settings.temporal.
 */
final class CollectionOrderTemporalTest extends TestCase
{
    public function run(): void
    {
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__) . '/fixtures/temporal');
        $this->assertSame([], $registry->errors(), 'Os presets de teste carregam sem erro.');
        $this->orderRules($registry);
        $this->orderFragment($registry);
        $this->temporalWindows($registry);
        $this->queryMerge($registry);
        $this->queryIds($registry);
        $this->legacyPresetsNeverChange();
    }

    private function orderRules(CollectionRegistry $registry): void
    {
        $evt = $registry->get('evt');
        $rules = OrderResolver::rules($evt);
        $this->assertSame(['data_inicio', 'hora_inicio', '@title'], array_column($rules, 'by'), 'Privado, galeria e inexistente ficam de fora.');
        $this->assertSame('keep', $rules[1]['missing'], 'missing=first não existe: recua para keep.');
        $this->assertSame('ASC', $rules[0]['dir']);

        // Sem inversão automática: past usa order_past se existir, senão order; os outros usam order.
        $past = OrderResolver::rulesFor($evt, TemporalResolver::PAST);
        $this->assertSame([['by' => 'data_inicio', 'dir' => 'DESC', 'missing' => 'hide']], $past);
        $this->assertSame($rules, OrderResolver::rulesFor($evt, TemporalResolver::UPCOMING));
        $this->assertSame($rules, OrderResolver::rulesFor($evt, null));
        $simples = $registry->get('simples');
        $this->assertSame([], OrderResolver::rulesFor($simples, TemporalResolver::PAST), 'Sem settings.order nada é imposto.');

        // Pedidos de quem consulta (shortcode e REST).
        $this->assertSame([['by' => 'local', 'dir' => 'DESC', 'missing' => 'keep']], OrderResolver::fromToken($evt, 'field:local', 'desc'));
        $this->assertSame([['by' => '@date', 'dir' => 'ASC', 'missing' => 'keep']], OrderResolver::fromToken($evt, '@date'));
        $this->assertSame([], OrderResolver::fromToken($evt, 'field:segredo'), 'Campo privado nunca ordena.');
        $this->assertSame([], OrderResolver::fromToken($evt, 'field:galeria'));
        $this->assertSame([], OrderResolver::fromToken($evt, 'field:inexistente'));

        // Configuração inválida desliga a primitiva sem erro.
        $inv = $registry->get('invtemp');
        $this->assertSame([], OrderResolver::rules($inv));
        $this->assertTrue(TemporalResolver::config($inv) === null, 'temporal sobre campo não-date fica desligado.');
    }

    private function orderFragment(CollectionRegistry $registry): void
    {
        $evt = $registry->get('evt');
        $fragment = OrderResolver::fragment($evt, OrderResolver::rules($evt));
        $this->assertSame(['fs_ord_0_data_inicio', 'fs_ord_1_hora_inicio', 'title', 'menu_order', 'date'], array_keys($fragment['orderby']));
        $this->assertSame(['ASC', 'ASC', 'ASC', 'ASC', 'DESC'], array_values($fragment['orderby']), 'Desempate final: menu_order ASC, data DESC.');
        $this->assertCount(2, $fragment['meta_query']);
        $first = $fragment['meta_query'][0];
        $this->assertSame('OR', $first['relation']);
        $this->assertSame('fs_data_inicio', $first['fs_ord_0_data_inicio']['key']);
        $this->assertSame('DATE', $first['fs_ord_0_data_inicio']['type']);
        $this->assertSame('NOT EXISTS', $first[0]['compare'], 'keep mantém itens sem valor.');
        $this->assertSame('TIME', $fragment['meta_query'][1]['fs_ord_1_hora_inicio']['type']);

        $hide = OrderResolver::fragment($evt, OrderResolver::rulesFor($evt, TemporalResolver::PAST));
        $this->assertSame('EXISTS', $hide['meta_query'][0]['fs_ord_0_data_inicio']['compare'], 'hide só mantém itens com valor.');
        $this->assertSame('DESC', $hide['orderby']['fs_ord_0_data_inicio']);
    }

    private function temporalWindows(CollectionRegistry $registry): void
    {
        $evt = $registry->get('evt');
        $config = TemporalResolver::config($evt);
        $this->assertSame('upcoming', $config['archive_when']);
        $this->assertSame('data_fim', $config['end']->key);

        $this->assertSame(null, TemporalResolver::normalizeWhen('quando=proximos'));
        $this->assertSame('past', TemporalResolver::normalizeWhen(' PAST '));
        $this->assertSame(null, TemporalResolver::clause($evt, 'all', '2026-10-14'), 'all não filtra.');
        $this->assertSame(null, TemporalResolver::clause($evt, null, '2026-10-14'), 'Sem when não filtra.');
        $this->assertSame(null, TemporalResolver::clause($evt, 'depois', '2026-10-14'), 'when inválido não filtra.');

        // Com data final: referência = data_fim, senão data_inicio; item sem data entra em upcoming.
        $up = TemporalResolver::clause($evt, 'upcoming', '2026-10-14');
        $this->assertSame('OR', $up['relation']);
        $this->assertCount(3, array_filter($up, 'is_array'));
        $this->assertSame(['key' => 'fs_data_fim', 'value' => '2026-10-14', 'compare' => '>=', 'type' => 'DATE'], $up[0]);
        $this->assertSame('AND', $up[1]['relation']);
        $this->assertSame('NOT EXISTS', $up[1][0]['compare']);
        $this->assertSame(['key' => 'fs_data_inicio', 'value' => '2026-10-14', 'compare' => '>=', 'type' => 'DATE'], $up[1][1]);
        $this->assertSame('NOT EXISTS', $up[2][1]['compare'], 'Sem data nenhuma: upcoming.');

        // past: o dia de hoje é "próximo" (>= hoje), não passado (< hoje); item sem data não entra.
        $past = TemporalResolver::clause($evt, 'past', '2026-10-14');
        $this->assertSame('<', $past[0]['compare']);
        $this->assertSame('<', $past[1][1]['compare']);
        $this->assertCount(2, array_filter($past, 'is_array'), 'undated=upcoming: sem ramo de itens sem data em past.');

        // Sem data final e undated=past.
        $simples = $registry->get('simples');
        $up = TemporalResolver::clause($simples, 'upcoming', '2026-10-14');
        $this->assertCount(1, array_filter($up, 'is_array'));
        $past = TemporalResolver::clause($simples, 'past', '2026-10-14');
        $this->assertCount(2, array_filter($past, 'is_array'));
        $this->assertSame('NOT EXISTS', $past[1]['compare']);

        $this->assertSame(1, preg_match('/^\d{4}-\d{2}-\d{2}$/', TemporalResolver::today()), 'today() devolve AAAA-MM-DD.');
    }

    private function queryMerge(CollectionRegistry $registry): void
    {
        $evt = $registry->get('evt');
        $args = ['post_type' => 'fs_evt', 'order' => 'DESC'];

        $both = CollectionQuery::apply($args, $evt, 'upcoming', OrderResolver::rulesFor($evt, 'upcoming'), '2026-10-14');
        $this->assertTrue(! isset($both['order']), 'order solto sai quando há regras.');
        $this->assertSame('AND', $both['meta_query']['relation']);
        $this->assertCount(3, array_filter($both['meta_query'], 'is_array'), 'Temporal + 2 grupos de ordem.');
        $this->assertSame('fs_ord_0_data_inicio', array_key_first($both['orderby']));

        $onlyWhen = CollectionQuery::apply($args, $evt, 'past', [], '2026-10-14');
        $this->assertSame('DESC', $onlyWhen['order'], 'Sem regras o orderby original fica.');
        $this->assertTrue(! isset($onlyWhen['orderby']));
        $this->assertSame('OR', $onlyWhen['meta_query'][0]['relation'], 'Uma só cláusula não leva envoltório AND.');
        $this->assertCount(1, $onlyWhen['meta_query']);

        // meta_query já existente: AND acrescenta; OR é embrulhado para não mudar o sentido.
        $and = CollectionQuery::apply(['meta_query' => [['key' => 'x', 'value' => '1']]], $evt, 'past', [], '2026-10-14');
        $this->assertSame(['key' => 'x', 'value' => '1'], $and['meta_query'][0]);
        $this->assertCount(2, $and['meta_query']);
        $or = CollectionQuery::apply(['meta_query' => ['relation' => 'OR', ['key' => 'x'], ['key' => 'y']]], $evt, 'past', [], '2026-10-14');
        $this->assertSame('AND', $or['meta_query']['relation']);
        $this->assertSame('OR', $or['meta_query'][0]['relation']);
    }

    /** Query IDs do Loop Grid: flashsite_ordered, flashsite_upcoming e flashsite_past. */
    private function queryIds(CollectionRegistry $registry): void
    {
        $reader = new ItemReader();
        $formatter = new FieldFormatter($reader);
        $output = new CollectionsOutput($registry, $formatter, new SchemaOrgBuilder(new CollectionPublicSerializer($reader, $formatter)));
        $evt = $registry->get('evt');
        $output->register(['evt' => $evt]);

        $query = new class {
            /** @var array<string, mixed> */
            public array $vars = ['post_type' => ['fs_evt']];
            public function get(string $key): mixed { return $this->vars[$key] ?? ''; }
            public function set(string $key, mixed $value): void { $this->vars[$key] = $value; }
        };

        $output->queryOrdered($query);
        $this->assertTrue(! isset($query->vars['meta_query']) || count(array_filter((array) $query->vars['meta_query'], 'is_array')) === 2, 'ordered: só os grupos de ordem, sem janela temporal.');
        $this->assertSame('fs_ord_0_data_inicio', array_key_first($query->vars['orderby']));

        $query->vars = ['post_type' => ['fs_evt']];
        $output->queryUpcoming($query);
        $this->assertSame('>=', $query->vars['meta_query'][0][0]['compare'], 'upcoming: janela temporal na primeira cláusula.');
        $this->assertSame('ASC', $query->vars['orderby']['fs_ord_0_data_inicio']);

        $query->vars = ['post_type' => ['fs_evt']];
        $output->queryPast($query);
        $this->assertSame('DESC', $query->vars['orderby']['fs_ord_0_data_inicio'], 'past usa order_past, declarado no preset.');
        $this->assertSame('<', $query->vars['meta_query'][0][0]['compare']);

        // Preset sem order/temporal: o Query ID não mexe na consulta.
        $plain = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections')->get('prato');
        $output2 = new CollectionsOutput(CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections'), $formatter, new SchemaOrgBuilder(new CollectionPublicSerializer($reader, $formatter)));
        $output2->register(['prato' => $plain]);
        $query->vars = ['post_type' => ['fs_prato'], 'orderby' => 'x'];
        foreach (['queryOrdered', 'queryUpcoming', 'queryPast'] as $method) {
            $output2->$method($query);
        }
        $this->assertSame(['post_type' => ['fs_prato'], 'orderby' => 'x'], $query->vars, 'Presets atuais: Query IDs novos não alteram nada.');
    }

    private function legacyPresetsNeverChange(): void
    {
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections');
        foreach (['imovel', 'plano', 'prato'] as $key) {
            $preset = $registry->get($key);
            $args = ['post_type' => $preset->postType(), 'posts_per_page' => 6, 'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'], 'meta_query' => [['key' => 'a', 'value' => '1']]];
            foreach ([null, 'upcoming', 'past', 'all'] as $when) {
                $this->assertSame([], OrderResolver::rulesFor($preset, $when), "$key não declara order.");
                $this->assertSame($args, CollectionQuery::apply($args, $preset, $when, OrderResolver::rulesFor($preset, $when)), "$key: argumentos idênticos ao 3.3.0 (when=" . var_export($when, true) . ').');
            }
            $this->assertTrue(TemporalResolver::config($preset) === null, "$key não declara temporal.");
        }
    }
}
