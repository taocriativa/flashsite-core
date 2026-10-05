<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;

require_once dirname(__DIR__, 3) . '/OutputFoundation/Elementor/ElementorTagBase.php';

/**
 * Base das Dynamic Tags das Coleções.
 *
 * Resolvem o item atual (página individual, Loop Grid, loop atómico) por get_the_ID()
 * e o preset pelo post type. Fora de um item da coleção devolvem vazio.
 *
 * Também podem apontar para um item fixo de uma lista, sem loop (útil em páginas montadas
 * pelo MCP, que não cria loops): item_source = "featured" | "latest" | "ordered", position = 1..n,
 * collection = chave do preset. Ex.: "2.º imóvel em destaque".
 *
 * @since 2.6.0
 */
abstract class CollectionTagBase extends \FlashSite\Core\Modules\OutputFoundation\Elementor\ElementorTagBase
{
    public const GROUP = 'flashsite-collections';

    /** @var array<string, list<int>> cache por pedido das listas featured/latest */
    private static array $lists = [];

    /**
     * Item "atual" imposto por quem renderiza (páginas modelo do Core). Necessário porque o
     * Elementor muda o post global para a página modelo durante o render.
     */
    private static int $contextPostId = 0;

    /** @var callable|null (int $postId): int  devolve um item de pré-visualização se $postId for uma página modelo */
    private static $previewResolver = null;

    public static function setContextPostId(int $postId): void
    {
        self::$contextPostId = max(0, $postId);
    }

    public static function setPreviewResolver(?callable $resolver): void
    {
        self::$previewResolver = $resolver;
    }

    abstract protected function slug(): string;
    abstract protected function title(): string;

    public function get_name(): string
    {
        return $this->slug();
    }

    public function get_title(): string
    {
        return $this->title();
    }

    public function get_group(): string
    {
        return self::GROUP;
    }

    protected function currentPostId(): int
    {
        $source = (string) ($this->get_settings('item_source') ?: 'current');
        if ($source === 'featured' || $source === 'latest' || $source === 'ordered') {
            return $this->listedPostId($source, max(1, (int) ($this->get_settings('position') ?: 1)));
        }
        if (self::$contextPostId > 0) {
            return self::$contextPostId;
        }
        $id = function_exists('get_the_ID') ? (int) get_the_ID() : 0;
        // A editar/pré-visualizar uma página modelo: mostra um item real como exemplo.
        if ($id > 0 && self::$previewResolver !== null) {
            $preview = (int) (self::$previewResolver)($id);
            if ($preview > 0) {
                return $preview;
            }
        }
        return $id > 0 ? $id : 0;
    }

    /** Controlos comuns: de onde vem o item. Chamar no início de register_controls(). */
    protected function registerItemSourceControls(): void
    {
        $this->add_control('item_source', [
            'label' => 'Item',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'current' => 'Item atual (página do item ou loop)',
                'featured' => 'Item em destaque n.º…',
                'latest' => 'Item mais recente n.º…',
                'ordered' => 'Item n.º… (pela ordem do painel)',
            ],
            'default' => 'current',
        ]);
        $this->add_control('position', [
            'label' => 'N.º',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 1,
            'min' => 1,
            'condition' => ['item_source!' => 'current'],
        ]);
        $this->add_control('collection', [
            'label' => 'Coleção',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => self::collectionOptions(),
            'default' => '',
            'condition' => ['item_source!' => 'current'],
        ]);
    }

    private function listedPostId(string $source, int $position): int
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry) {
            return 0;
        }
        $key = (string) ($this->get_settings('collection') ?? '');
        $preset = $key !== '' ? $registry->get($key) : null;
        if ($preset === null) {
            // Sem coleção escolhida: a primeira coleção ativa (registada como post type).
            foreach ($registry->all() as $candidate) {
                if (post_type_exists($candidate->postType())) {
                    $preset = $candidate;
                    break;
                }
            }
        }
        if ($preset === null || ! post_type_exists($preset->postType())) {
            return 0;
        }

        $cacheKey = $preset->key() . '|' . $source;
        if (! isset(self::$lists[$cacheKey])) {
            $args = [
                'post_type' => $preset->postType(),
                'post_status' => 'publish',
                'posts_per_page' => 24,
                'fields' => 'ids',
                'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
                'no_found_rows' => true,
            ];
            if ($source === 'featured') {
                $featured = $preset->field((string) $preset->setting('featured_field', 'destaque'));
                if ($featured !== null) {
                    $args['meta_query'] = [['key' => $featured->metaKey(), 'value' => '1']];
                }
            } elseif ($source === 'latest') {
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            }
            $unavailable = $preset->setting('unavailable_terms');
            if (is_array($unavailable) && isset($unavailable['taxonomy'], $unavailable['terms'])) {
                $args['tax_query'] = [[
                    'taxonomy' => $preset->postType() . '_' . (string) $unavailable['taxonomy'],
                    'field' => 'slug',
                    'terms' => array_values((array) $unavailable['terms']),
                    'operator' => 'NOT IN',
                ]];
            }
            self::$lists[$cacheKey] = array_map('intval', (array) get_posts($args));
        }
        return self::$lists[$cacheKey][$position - 1] ?? 0;
    }

    /** @return array<string, string> */
    protected static function collectionOptions(): array
    {
        $registry = self::service(CollectionRegistry::class);
        $options = ['' => 'Primeira coleção ativa'];
        if ($registry instanceof CollectionRegistry) {
            foreach ($registry->all() as $preset) {
                $options[$preset->key()] = $preset->labels()['plural'];
            }
        }
        return $options;
    }

    protected function currentPreset(int $postId): ?CollectionPresetInterface
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry || $postId <= 0) {
            return null;
        }
        return $registry->byPostType((string) get_post_type($postId));
    }

    protected static function formatter(): ?FieldFormatter
    {
        $formatter = self::service(FieldFormatter::class);
        return $formatter instanceof FieldFormatter ? $formatter : null;
    }

    protected static function reader(): ?ItemReader
    {
        $reader = self::service(ItemReader::class);
        return $reader instanceof ItemReader ? $reader : null;
    }

    protected static function service(string $id): mixed
    {
        $application = Plugin::instance()->application();
        if ($application === null || ! $application->container()->has($id)) {
            return null;
        }
        return $application->container()->make($id);
    }

    /**
     * Opções "chave => label" de campos públicos de todos os presets, filtradas por tipo.
     *
     * @param list<FieldType> $types vazio = todos
     * @return array<string, string>
     */
    protected static function fieldOptions(array $types = []): array
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry) {
            return [];
        }
        $options = [];
        foreach ($registry->all() as $preset) {
            foreach ($preset->publicFields() as $field) {
                if ($types !== [] && ! in_array($field->type, $types, true)) {
                    continue;
                }
                $options[$field->key] = isset($options[$field->key])
                    ? $options[$field->key]
                    : $preset->labels()['singular'] . ' · ' . $field->label;
            }
        }
        return $options;
    }

    /** @return array<string, string> */
    protected static function taxonomyOptions(): array
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry) {
            return [];
        }
        $options = [];
        foreach ($registry->all() as $preset) {
            foreach ($preset->taxonomies() as $taxonomy) {
                $options[$taxonomy->key] ??= $preset->labels()['singular'] . ' · ' . $taxonomy->singularLabel;
            }
        }
        return $options;
    }
}
