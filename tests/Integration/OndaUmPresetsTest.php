<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Access\RoleManager;
use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\OrderResolver;
use FlashSite\Core\Domain\Collections\SchemaMap;
use FlashSite\Core\Domain\Collections\TemporalResolver;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Collections\Admin\ItemEditor;
use FlashSite\Core\Modules\Collections\Admin\ListColumns;
use FlashSite\Core\Modules\Collections\CollectionsModule;
use FlashSite\Core\Modules\Collections\ItemPersistence;

require_once __DIR__ . '/../fixtures/JsonLdHarness.php';

/**
 * 3.4.0-beta.2: Onda 1 (servico, equipa, projeto, evento) e schema_map.
 */
final class OndaUmPresetsTest extends TestCase
{
    private const NEW_KEYS = ['servico', 'equipa', 'projeto', 'evento'];
    private string $config;

    public function run(): void
    {
        $this->config = dirname(__DIR__, 2) . '/config/collections';
        $this->loadsInBothMarkets();
        $this->offByDefault();
        $this->servico();
        $this->equipa();
        $this->projeto();
        $this->evento();
        $this->jsonLd();
        $this->legacyJsonLdUnchanged();
        $this->translations();
    }

    private function loadsInBothMarkets(): void
    {
        foreach (['PT', 'BR'] as $market) {
            $registry = CollectionRegistry::fromDirectory($this->config, $market);
            $this->assertSame([], $registry->errors(), "Presets sem erros ($market): " . json_encode($registry->errors()));
            $this->assertSame(['equipa', 'evento', 'imovel', 'plano', 'prato', 'projeto', 'servico'], $this->sorted($registry->keys()));
            $slugs = ['servico' => 'servicos', 'equipa' => $market === 'BR' ? 'equipe' : 'equipa', 'projeto' => 'projetos', 'evento' => 'eventos'];
            foreach ($slugs as $key => $slug) {
                $preset = $registry->get($key);
                $this->assertSame($slug, $preset->slug(), "$key ($market): slug.");
                $this->assertSame('fs_' . $key, $preset->postType());
                $this->assertSame(CollectionRegistry::ORIGIN_CORE, $registry->origin($key));
                $this->assertFalse(str_starts_with($key, CollectionRegistry::EXTERNAL_PREFIX));
                $this->assertFalse($preset->field('notas_internas')->public, "$key: notas internas são privadas.");
                $this->assertFalse(in_array('notas_internas', array_map(static fn ($f) => $f->key, $preset->publicFields()), true));
            }
            $this->assertSame('Service', $registry->get('servico')->schemaType());
            $this->assertSame('Person', $registry->get('equipa')->schemaType());
            $this->assertSame('CreativeWork', $registry->get('projeto')->schemaType());
            $this->assertSame('Event', $registry->get('evento')->schemaType());
        }
    }

    /** @param list<string> $values @return list<string> */
    private function sorted(array $values): array
    {
        sort($values);
        return $values;
    }

    /** Os quatro entram desativados: sem ativação nada é registado e nenhum papel recebe capabilities deles. */
    private function offByDefault(): void
    {
        flashsite_reset_test_state();
        (new RoleManager(dirname(__DIR__, 2) . '/config/capabilities.php'))->ensureRole();
        $storage = new OptionsStorage();
        $registry = CollectionRegistry::fromDirectory($this->config);
        $activation = new ActivationRepository($storage);
        $persistence = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $module = new CollectionsModule($registry, $activation, new CollectionCapabilities($storage), new ItemSanitizer(), $storage, new Logger(), $persistence, new ItemEditor($persistence, new ItemReader()), new ListColumns(new ItemReader()));
        $module->register();
        $module->registerContent();
        $this->assertSame([], $activation->activeKeys());
        $this->assertSame([], $GLOBALS['flashsite_test_post_types'], 'Sem ativação nenhum CPT é registado.');
        $module->syncCapabilities();
        $manager = get_role(CollectionCapabilities::SITE_MANAGER_ROLE);
        foreach (self::NEW_KEYS as $key) {
            $this->assertFalse(isset($manager->caps["edit_fs_{$key}_items"]), "$key: o gestor não recebe capabilities sem ativação.");
        }

        // Ativar só o evento: CPT, estado fixo e campo privado fora da REST.
        $activation->activate('evento');
        do_action('flashsite_collections_changed');
        $module->registerContent();
        $this->assertSame(['fs_evento'], array_keys($GLOBALS['flashsite_test_post_types']));
        $this->assertSame('eventos', $GLOBALS['flashsite_test_post_types']['fs_evento']['has_archive']);
        $estado = $GLOBALS['flashsite_test_taxonomies']['fs_evento_estado']['args'];
        $this->assertSame(CollectionCapabilities::MANAGE_CAP, $estado['capabilities']['manage_terms'], 'Estado é fixo: só o administrador altera.');
        $tipo = $GLOBALS['flashsite_test_taxonomies']['fs_evento_tipo']['args'];
        $this->assertSame('edit_fs_evento_items', $tipo['capabilities']['manage_terms'], 'Tipos são livres: o gestor cria.');
        $this->assertSame(false, $GLOBALS['flashsite_test_post_meta_registry']['fs_evento']['_fs_notas_internas']['show_in_rest']);
        $this->assertTrue(isset(get_role(CollectionCapabilities::SITE_MANAGER_ROLE)->caps['edit_fs_evento_items']));
        $this->assertFalse(isset(get_role(CollectionCapabilities::SITE_MANAGER_ROLE)->caps['edit_fs_servico_items']), 'Os outros continuam sem caps.');
    }

    private function servico(): void
    {
        flashsite_reset_test_state();
        $preset = CollectionRegistry::fromDirectory($this->config)->get('servico');
        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $formatter = new FieldFormatter(new ItemReader());

        // Sem preço: válido, e o preço sai vazio (o serviço pode existir sem preço).
        $this->assertSame([], $p->saveFields($preset, 901, ['resumo' => 'Primeira avaliação.', 'como_funciona' => "Marcação\nAvaliação\n\nPlano"]));
        $this->assertSame('', $formatter->price($preset, 901));
        $this->assertSame(['Marcação', 'Avaliação', 'Plano'], $GLOBALS['flashsite_test_post_meta'][901]['fs_como_funciona']);

        // Com preço e "desde".
        $this->assertSame([], $p->saveFields($preset, 902, ['preco' => '60', 'preco_desde' => '1']));
        $this->assertSame('desde 60,00 €', $formatter->price($preset, 902));

        // Preço exato: sem "desde" o prefixo não aparece (price_prefix só vale com o campo ligado).
        $this->assertSame([], $p->saveFields($preset, 905, ['preco' => '100']));
        $this->assertSame('100,00 €', $formatter->price($preset, 905), 'Preço exato não leva "desde".');
        $this->assertSame('100 €', $formatter->price($preset, 905, FieldFormatter::MONEY_AUTO));
        $p->saveFields($preset, 905, ['preco_desde' => '1']);
        $this->assertSame('desde 100,00 €', $formatter->price($preset, 905), '"A partir de" só com o campo ligado.');
        $this->assertSame('desde 100,00 €', $formatter->price($preset, 905, FieldFormatter::MONEY_CENTS, FieldFormatter::PART_AMOUNT));
        $p->saveFields($preset, 905, ['preco_desde' => '']);
        $this->assertSame('100,00 €', $formatter->price($preset, 905), 'Desligar o campo volta ao preço exato.');
        // "desde" ligado sem preço não inventa nada.
        $p->saveFields($preset, 906, ['preco_desde' => '1']);
        $this->assertSame('', $formatter->price($preset, 906));

        // Sob consulta.
        $this->assertSame([], $p->saveFields($preset, 903, ['preco' => '60', 'preco_sob_consulta' => '1']));
        $this->assertSame(FieldFormatter::ON_REQUEST_TEXT, $formatter->price($preset, 903));

        // Destaque: bool gravado e usado como featured_field.
        $p->saveFields($preset, 904, ['destaque' => '1']);
        $this->assertSame('destaque', $preset->setting('featured_field'));
        $this->assertSame(true, (new ItemReader())->value($preset->field('destaque'), 904));
        $this->assertSame('fs_destaque', $preset->field('destaque')->metaKey());

        // Distinção de plano: sem periodicidade e sem regra de preço obrigatório; nenhum order/temporal/schema_map.
        $this->assertTrue($preset->field('periodicidade') === null);
        $this->assertSame([], (array) $preset->setting('rules', []));
        $this->assertSame([], OrderResolver::rules($preset));
        $this->assertTrue(TemporalResolver::config($preset) === null);
        $this->assertSame([], SchemaMap::entries($preset));
        $this->assertSame(FieldType::Image, $preset->field('icone')->type);
        $this->assertTrue($preset->taxonomy('categoria') !== null && ! $preset->taxonomy('categoria')->locked, 'Categoria é livre.');
    }

    private function equipa(): void
    {
        flashsite_reset_test_state();
        $registry = CollectionRegistry::fromDirectory($this->config);
        $preset = $registry->get('equipa');
        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $errors = $p->saveFields($preset, 911, [
            'cargo' => 'Sócia fundadora',
            'email' => 'ana@exemplo.test',
            'telefone' => '+351 910 000 000',
            'linkedin' => 'https://linkedin.com/in/ana',
            'notas_internas' => 'Nota privada',
        ]);
        $this->assertSame([], $errors);
        $meta = $GLOBALS['flashsite_test_post_meta'][911];
        $this->assertSame('ana@exemplo.test', $meta['fs_email']);
        $this->assertArrayHasKey('_fs_notas_internas', $meta, 'Campo privado grava com prefixo protegido.');
        $public = (new ItemReader())->read($preset, 911, false);
        $this->assertFalse(array_key_exists('notas_internas', $public), 'Campo privado não sai na leitura pública.');
        $this->assertSame('Sócia fundadora', $public['cargo']);

        // Person: só campos públicos de valor simples.
        $entries = SchemaMap::entries($preset);
        $this->assertSame(['jobTitle', 'knowsAbout', 'email', 'telephone'], array_keys($entries));
        // Ordem manual (page-attributes), sem order declarativo.
        $this->assertTrue(in_array('page-attributes', $preset->supports(), true));
        $this->assertSame([], OrderResolver::rules($preset));
        $this->assertTrue($preset->taxonomy('funcao') !== null && ! $preset->taxonomy('funcao')->locked, 'Função é livre.');
        $this->assertTrue(in_array('thumbnail', $preset->supports(), true), 'Foto = imagem de capa.');
        $this->assertTrue(in_array('editor', $preset->supports(), true), 'Biografia = editor.');
        // Brasil: equipe e registro.
        $br = CollectionRegistry::fromDirectory($this->config, 'BR')->get('equipa');
        $this->assertSame('equipe', $br->slug());
        $this->assertSame('Equipe', $br->labels()['plural']);
        $this->assertSame('Registro profissional', $br->field('registro_profissional')->label);
        $this->assertSame($preset->postType(), $br->postType(), 'O post_type não muda com o mercado.');
        $this->assertSame(array_map(static fn ($f) => $f->key, $preset->fields()), array_map(static fn ($f) => $f->key, $br->fields()), 'As chaves dos campos são as mesmas nos dois mercados.');
    }

    private function projeto(): void
    {
        flashsite_reset_test_state();
        $preset = CollectionRegistry::fromDirectory($this->config)->get('projeto');
        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());
        $this->assertSame([], $p->saveFields($preset, 921, ['cliente' => 'Cliente Exemplo', 'ano' => '2025', 'galeria' => [31, 32, 33], 'link' => 'https://exemplo.test/projeto']));
        $this->assertSame([31, 32, 33], $GLOBALS['flashsite_test_post_meta'][921]['fs_galeria']);
        // "ano" é texto: nada de 2.025.
        $formatter = new FieldFormatter(new ItemReader());
        $this->assertSame('2025', $formatter->field($preset, $preset->field('ano'), 921));
        $this->assertSame(FieldType::Text, $preset->field('ano')->type);
        // cover_from => galeria: a primeira foto passa a ser a capa.
        $this->assertSame('galeria', $preset->setting('cover_from'));
        $p->syncCover($preset, 921);
        $this->assertSame(31, $GLOBALS['flashsite_test_thumbnails'][921] ?? null);
        $p->saveFields($preset, 921, ['galeria' => []]);
        $p->syncCover($preset, 921);
        $this->assertTrue(! isset($GLOBALS['flashsite_test_thumbnails'][921]), 'Sem galeria, sem capa.');
        $this->assertTrue($preset->taxonomy('categoria') !== null && ! $preset->taxonomy('categoria')->locked, 'Categoria é livre.');
        $this->assertSame(['sameAs'], array_keys(SchemaMap::entries($preset)));
    }

    private function evento(): void
    {
        flashsite_reset_test_state();
        $preset = CollectionRegistry::fromDirectory($this->config)->get('evento');
        $p = new ItemPersistence(new ItemSanitizer(), new ItemValidator(), new ItemReader());

        // Data de início é obrigatória; fim não pode ser anterior.
        $this->assertTrue(isset($p->saveFields($preset, 931, ['local' => 'Teatro'])['data_inicio']), 'data_inicio é obrigatória.');
        $this->assertSame([], $p->saveFields($preset, 932, ['data_inicio' => '2026-11-10', 'data_fim' => '2026-11-10', 'hora_inicio' => '21:00']));
        $this->assertTrue(isset($p->saveFields($preset, 933, ['data_inicio' => '2026-11-10', 'data_fim' => '2026-11-09'])['data_fim']), 'data_fim >= data_inicio.');

        // Estado fixo, com confirmado por defeito.
        $estado = $preset->taxonomy('estado');
        $this->assertTrue($estado->locked && $estado->required && $estado->single);
        $this->assertSame('confirmado', $estado->default);
        $this->assertSame(['confirmado', 'esgotado', 'adiado', 'cancelado'], array_keys($estado->terms));
        $this->assertTrue($preset->taxonomy('tipo') !== null && ! $preset->taxonomy('tipo')->locked, 'Tipo é livre.');
        $this->assertTrue(! in_array('page-attributes', $preset->supports(), true), 'A ordem vem da data, não de "Ordem".');

        // Ordem declarada: próximos ASC, passados DESC, sem inversão automática.
        $up = OrderResolver::rulesFor($preset, TemporalResolver::UPCOMING);
        $this->assertSame([['by' => 'data_inicio', 'dir' => 'ASC', 'missing' => 'keep'], ['by' => 'hora_inicio', 'dir' => 'ASC', 'missing' => 'keep'], ['by' => '@title', 'dir' => 'ASC', 'missing' => 'keep']], $up);
        $past = OrderResolver::rulesFor($preset, TemporalResolver::PAST);
        $this->assertSame(['DESC', 'DESC', 'ASC'], array_column($past, 'dir'));
        $this->assertSame($up, OrderResolver::rulesFor($preset, TemporalResolver::ALL));

        // Janela temporal (hoje = 2026-10-14): dia de hoje é "próximo"; ontem é passado; vários dias em curso é próximo.
        $config = TemporalResolver::config($preset);
        $this->assertSame('upcoming', $config['archive_when']);
        $this->assertSame('upcoming', $config['undated']);
        $upcoming = TemporalResolver::clause($preset, 'upcoming', '2026-10-14');
        $pastClause = TemporalResolver::clause($preset, 'past', '2026-10-14');
        $this->assertTrue($this->clauseMatches($upcoming, ['data_inicio' => '2026-10-14']), 'Hoje, sem fim: próximo.');
        $this->assertFalse($this->clauseMatches($pastClause, ['data_inicio' => '2026-10-14']), 'Hoje não é passado.');
        $this->assertTrue($this->clauseMatches($pastClause, ['data_inicio' => '2026-10-13']), 'Ontem, sem fim: passado.');
        $this->assertFalse($this->clauseMatches($upcoming, ['data_inicio' => '2026-10-13']));
        $this->assertTrue($this->clauseMatches($upcoming, ['data_inicio' => '2026-10-13', 'data_fim' => '2026-10-15']), 'Vários dias em curso: próximo.');
        $this->assertFalse($this->clauseMatches($pastClause, ['data_inicio' => '2026-10-13', 'data_fim' => '2026-10-15']));
        $this->assertTrue($this->clauseMatches($pastClause, ['data_inicio' => '2026-10-10', 'data_fim' => '2026-10-13']), 'Terminou ontem: passado.');
        $this->assertTrue($this->clauseMatches($upcoming, []), 'Sem data: conta como próximo (undated = upcoming).');
        $this->assertFalse($this->clauseMatches($pastClause, []));
        $this->assertSame(null, TemporalResolver::clause($preset, 'all', '2026-10-14'), 'all: sem filtro, nada desaparece.');
        $this->assertSame('', (new FieldFormatter(new ItemReader()))->price($preset, 932), 'Sem preço, sem texto de preço.');
    }

    /**
     * Avalia uma cláusula de meta_query (como o MySQL a avaliaria) para um item com estes campos.
     *
     * @param array<int|string, mixed> $clause
     * @param array<string, string> $fields chave do campo => valor
     */
    private function clauseMatches(array $clause, array $fields): bool
    {
        if (isset($clause['key'])) {
            $value = $fields[preg_replace('/^fs_/', '', (string) $clause['key'])] ?? null;
            return match ($clause['compare']) {
                'NOT EXISTS' => $value === null,
                '>=' => $value !== null && $value >= $clause['value'],
                '<' => $value !== null && $value < $clause['value'],
                default => false,
            };
        }
        $relation = strtoupper((string) ($clause['relation'] ?? 'AND'));
        $children = array_filter($clause, 'is_array');
        $results = array_map(fn (array $child): bool => $this->clauseMatches($child, $fields), $children);
        return $relation === 'OR' ? in_array(true, $results, true) : ! in_array(false, $results, true);
    }

    private function jsonLd(): void
    {
        flashsite_reset_test_state();
        $ld = JsonLdHarness::build($this->config, 'evento', 'PT', 941, [
            'fs_data_inicio' => '2026-11-10', 'fs_data_fim' => '2026-11-11', 'fs_local' => 'Teatro Municipal',
            'fs_preco' => 15.0, 'fs_cidade' => 'Lisboa', '_fs_notas_internas' => 'segredo',
        ], ['estado' => ['cancelado']]);
        $this->assertSame('Event', $ld['@type']);
        $this->assertSame('2026-11-10', $ld['startDate']);
        $this->assertSame('2026-11-11', $ld['endDate']);
        $this->assertSame('Teatro Municipal', $ld['location']);
        $this->assertSame('https://schema.org/EventCancelled', $ld['eventStatus']);
        $this->assertSame('15.00', $ld['offers']['price'], 'O Offer continua a vir do preço do preset.');
        $this->assertFalse(str_contains((string) json_encode($ld), 'segredo'), 'Campo privado nunca aparece no JSON-LD.');

        // Sem termo mapeado (esgotado) e sem datas: as propriedades simplesmente não existem.
        flashsite_reset_test_state();
        $ld = JsonLdHarness::build($this->config, 'evento', 'PT', 942, ['fs_data_inicio' => '2026-11-10'], ['estado' => ['esgotado']]);
        $this->assertSame('2026-11-10', $ld['startDate']);
        $this->assertFalse(array_key_exists('endDate', $ld));
        $this->assertFalse(array_key_exists('location', $ld));
        $this->assertFalse(array_key_exists('eventStatus', $ld));

        flashsite_reset_test_state();
        $ld = JsonLdHarness::build($this->config, 'equipa', 'PT', 943, [
            'fs_cargo' => 'Sócia fundadora', 'fs_especialidade' => 'Nutrição', 'fs_email' => 'ana@exemplo.test', 'fs_telefone' => '+351 910 000 000',
            '_fs_notas_internas' => 'segredo',
        ], []);
        $this->assertSame('Person', $ld['@type']);
        $this->assertSame('Sócia fundadora', $ld['jobTitle']);
        $this->assertSame('Nutrição', $ld['knowsAbout']);
        $this->assertSame('ana@exemplo.test', $ld['email']);
        $this->assertSame('+351 910 000 000', $ld['telephone']);
        $this->assertFalse(str_contains((string) json_encode($ld), 'segredo'));

        flashsite_reset_test_state();
        $ld = JsonLdHarness::build($this->config, 'projeto', 'PT', 944, ['fs_link' => 'https://exemplo.test/projeto'], []);
        $this->assertSame('CreativeWork', $ld['@type']);
        $this->assertSame('https://exemplo.test/projeto', $ld['sameAs']);

        flashsite_reset_test_state();
        $ld = JsonLdHarness::build($this->config, 'servico', 'PT', 945, ['fs_preco' => 60.0], []);
        $this->assertSame('Service', $ld['@type']);
        $this->assertSame('60.00', $ld['offers']['price']);
    }

    /** JSON-LD de imovel, plano e prato idêntico ao Core 3.3.0 (golden gerado a partir da main). */
    private function legacyJsonLdUnchanged(): void
    {
        $golden = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/golden/jsonld-3.3.0.json'), true);
        $now = JsonLdHarness::legacyCases($this->config);
        $this->assertSame($golden, json_decode((string) json_encode($now), true), 'O JSON-LD dos presets antigos tem de ser igual ao do 3.3.0.');
    }

    /** Textos dos presets novos (arquivos, taxonomias, termos) existem nos pacotes EN, ES e FR. */
    private function translations(): void
    {
        flashsite_reset_test_state();
        Strings::useDirectory(dirname(__DIR__, 2) . '/config/i18n');
        foreach (['PT', 'BR'] as $market) {
            $registry = CollectionRegistry::fromDirectory($this->config, $market);
            foreach (self::NEW_KEYS as $key) {
                $preset = $registry->get($key);
                $texts = array_values((array) $preset->setting('archive_texts', []));
                foreach ($preset->taxonomies() as $taxonomy) {
                    $texts[] = $taxonomy->singularLabel;
                    $texts[] = $taxonomy->label;
                    array_push($texts, ...array_values($taxonomy->terms));
                }
                foreach (array_unique($texts) as $text) {
                    foreach ([Language::EN, Language::ES, Language::FR] as $language) {
                        $this->assertTrue(Strings::t($text, $language) !== $text || $this->sameInLanguage($text, $language), "Sem tradução $language para \"$text\" ($key, $market).");
                    }
                }
            }
        }
        Strings::useDirectory(null);
    }

    /** Palavras iguais em português e no idioma (ex.: "Confirmado", "Cancelado" em ES). */
    private function sameInLanguage(string $text, string $language): bool
    {
        return $language === Language::ES && in_array($text, ['Estado', 'Tipo', 'Confirmado', 'Cancelado', 'Tipos de evento'], true);
    }
}
