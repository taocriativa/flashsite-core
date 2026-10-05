<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldDefinition;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\TaxonomyDefinition;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Collections\Admin\ItemEditor;
use FlashSite\Core\Modules\Collections\Admin\ListColumns;

/**
 * Motor das Coleções: regista CPT, taxonomias e post meta dos presets ativos.
 *
 * Princípios:
 * - Só presets ativos são registados. Desativar nunca apaga itens.
 * - Campos privados (`_fs_*`) nunca vão para a REST.
 * - Itens das coleções usam o editor clássico com a ficha do Core (sem Gutenberg).
 *
 * @since 2.6.0
 */
final class CollectionsModule implements ModuleInterface
{
    public const FLUSH_FLAG = 'flashsite_collections_flush_rewrite';

    /** @var array<string, CollectionPresetInterface> */
    private array $registered = [];

    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private CollectionCapabilities $capabilities,
        private ItemSanitizer $sanitizer,
        private OptionsStorage $storage,
        private LoggerInterface $logger,
        private ItemPersistence $persistence,
        private ItemEditor $editor,
        private ListColumns $columns,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerContent'], 9);
        add_action('init', [$this, 'syncCapabilities'], 11);
        add_action('init', [$this, 'maybeFlushRewriteRules'], 99);
        add_filter('use_block_editor_for_post_type', [$this, 'disableBlockEditor'], 10, 2);
        add_action('flashsite_collections_changed', [$this, 'onActivationChanged']);
    }

    public function boot(): void
    {
        foreach ($this->registry->errors() as $source => $message) {
            $this->logger->error('Preset de coleção inválido ignorado.', ['source' => $source, 'error' => $message]);
        }
        $this->logger->info('Collections module booted.', ['active' => $this->activation->activeKeys()]);
    }

    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'collections'; }

    public function registerContent(): void
    {
        foreach ($this->activePresets() as $preset) {
            $this->registerPreset($preset);
        }
        if ($this->registered === []) {
            return;
        }
        // A ficha também trata gravações REST (faixa de preço e capa), por isso regista sempre.
        $this->editor->register($this->registered);
        if (is_admin()) {
            $this->columns->register($this->registered);
        }
    }

    public function syncCapabilities(): void
    {
        $this->capabilities->sync($this->registry, $this->activeKeysKnown());
    }

    public function onActivationChanged(): void
    {
        $this->capabilities->sync($this->registry, $this->activeKeysKnown(), true);
        $this->storage->update(self::FLUSH_FLAG, '1', true);
    }

    public function maybeFlushRewriteRules(): void
    {
        // update_option(true) volta da BD como '1': ler como booleano.
        if (! filter_var($this->storage->get(self::FLUSH_FLAG, false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }
        $this->storage->update(self::FLUSH_FLAG, '0', true);
        // Termos fixos e faixas de preço só são semeados quando o conjunto ativo muda.
        foreach ($this->registered as $preset) {
            $this->persistence->seedTerms($preset);
        }
        flush_rewrite_rules(false);
    }

    public function disableBlockEditor(bool $useBlockEditor, string $postType): bool
    {
        return $this->registry->byPostType($postType) !== null ? false : $useBlockEditor;
    }

    /** @return array<string, CollectionPresetInterface> */
    public function activePresets(): array
    {
        $active = [];
        foreach ($this->activeKeysKnown() as $key) {
            $preset = $this->registry->get($key);
            if ($preset !== null) {
                $active[$key] = $preset;
            }
        }
        return $active;
    }

    /** @return array<string, CollectionPresetInterface> */
    public function registeredPresets(): array
    {
        return $this->registered;
    }

    public function registerPreset(CollectionPresetInterface $preset): void
    {
        $postType = $preset->postType();
        $labels = $preset->labels();

        // Taxonomias primeiro: as regras /imoveis/<taxonomia>/<termo>/ têm de ter prioridade
        // sobre as regras de anexo do CPT (/imoveis/<item>/<anexo>/), senão dão 404.
        foreach ($preset->taxonomies() as $taxonomy) {
            $this->registerTaxonomy($preset, $taxonomy);
        }

        register_post_type($postType, [
            'labels' => $this->postTypeLabels($labels),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 26,
            'menu_icon' => $preset->menuIcon(),
            'has_archive' => $preset->slug(),
            'rewrite' => ['slug' => $preset->slug(), 'with_front' => false],
            'supports' => $preset->supports(),
            'capability_type' => [$preset->capabilitySingular(), $preset->capabilityPlural()],
            'map_meta_cap' => true,
            'delete_with_user' => false,
        ]);

        foreach ($preset->fields() as $field) {
            $this->registerField($preset, $field);
        }

        $this->registered[$preset->key()] = $preset;
    }

    private function registerTaxonomy(CollectionPresetInterface $preset, TaxonomyDefinition $taxonomy): void
    {
        register_taxonomy($taxonomy->taxonomyName($preset->postType()), [$preset->postType()], [
            'labels' => [
                'name' => $taxonomy->label,
                'singular_name' => $taxonomy->singularLabel,
                'menu_name' => $taxonomy->label,
                'all_items' => 'Todos',
                'add_new_item' => 'Adicionar ' . mb_strtolower($taxonomy->singularLabel),
                'search_items' => 'Pesquisar',
                'not_found' => 'Nada encontrado.',
            ],
            'public' => true,
            'hierarchical' => $taxonomy->hierarchical,
            'show_ui' => true,
            'show_admin_column' => $taxonomy->adminColumn,
            'show_in_quick_edit' => false,
            'show_in_rest' => true,
            // A ficha do Core desenha a seleção; evita a metabox nativa duplicada.
            'meta_box_cb' => false,
            'rewrite' => ['slug' => $preset->slug() . '/' . $taxonomy->slug, 'with_front' => false, 'hierarchical' => $taxonomy->hierarchical],
            'capabilities' => CollectionCapabilities::taxonomyCaps($preset, $taxonomy),
        ]);
    }

    private function registerField(CollectionPresetInterface $preset, FieldDefinition $field): void
    {
        $type = $field->type;
        $args = [
            'type' => $type->metaType(),
            'single' => true,
            'description' => $field->label,
            'default' => $type->emptyValue(),
            'sanitize_callback' => fn (mixed $value): mixed => $this->sanitizer->sanitize($field, $value),
            'auth_callback' => static fn (bool $allowed, string $metaKey, int $postId): bool => current_user_can('edit_post', $postId),
            'show_in_rest' => $field->public ? ['schema' => $type->restSchema()] : false,
            'revisions_enabled' => true,
        ];
        if ($type->emptyValue() === null) {
            unset($args['default']);
        }

        register_post_meta($preset->postType(), $field->metaKey(), $args);
    }

    /** @param array<string, string> $labels */
    private function postTypeLabels(array $labels): array
    {
        $singular = $labels['singular'];
        $plural = $labels['plural'];
        $lower = mb_strtolower($singular);

        return [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $labels['menu_name'] ?? $plural,
            'add_new' => $labels['add_new'] ?? 'Adicionar novo',
            'add_new_item' => $labels['add_new_item'] ?? 'Adicionar ' . $lower,
            'edit_item' => $labels['edit_item'] ?? 'Editar ' . $lower,
            'new_item' => $labels['new_item'] ?? 'Novo ' . $lower,
            'view_item' => $labels['view_item'] ?? 'Ver ' . $lower,
            'view_items' => $labels['view_items'] ?? 'Ver ' . mb_strtolower($plural),
            'search_items' => $labels['search_items'] ?? 'Pesquisar ' . mb_strtolower($plural),
            'not_found' => $labels['not_found'] ?? 'Nenhum registo encontrado.',
            'not_found_in_trash' => $labels['not_found_in_trash'] ?? 'Nenhum registo no lixo.',
            'all_items' => $labels['all_items'] ?? 'Todos',
            'featured_image' => $labels['featured_image'] ?? 'Imagem de capa',
            'set_featured_image' => $labels['set_featured_image'] ?? 'Definir imagem de capa',
        ];
    }

    /** @return list<string> */
    private function activeKeysKnown(): array
    {
        return array_values(array_filter(
            $this->activation->activeKeys(),
            fn (string $key): bool => $this->registry->has($key)
        ));
    }
}
