<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Modules\Collections\CollectionStateService;

/**
 * Catálogo, contrato (schema) e ativação das Coleções. Tudo derivado do CollectionRegistry final:
 * presets oficiais e externos (x_*) entram sem código por preset.
 *
 * @since 3.5.0
 */
final class CollectionAbilities implements AbilityProvider
{
    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private CollectionSettings $settings,
        private CollectionStateService $state,
        private AbilityGate $gate,
    ) {}

    public function definitions(): array
    {
        $collectionInput = $this->collectionProperty();
        $stateOutput = [
            'type' => 'object',
            'properties' => [
                'collection' => ['type' => 'string'],
                'active' => ['type' => 'boolean'],
                'changed' => ['type' => 'boolean', 'description' => 'false quando a coleção já estava neste estado (nada foi feito).'],
                'effects' => ['type' => 'object', 'description' => 'Efeitos aplicados ao mudar o estado.'],
            ],
            'required' => ['collection', 'active', 'changed'],
        ];

        return [
            'flashsite/list-collections' => [
                'label' => 'Listar coleções',
                'description' => 'Catálogo de coleções conhecidas (oficiais e externas), com estado, endereço, rótulos e taxonomias. Só leitura.',
                'category' => AbilityMeta::CATEGORY,
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'market' => ['type' => 'string'],
                        'currency' => ['type' => 'string'],
                        'collections' => ['type' => 'array', 'items' => ['type' => 'object', 'description' => 'Resumo de uma coleção.']],
                    ],
                    'required' => ['collections'],
                ],
                'execute_callback' => [$this, 'listCollections'],
                'permission_callback' => fn (): bool => $this->gate->anyFlashSite(),
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/get-collection-schema' => [
                'label' => 'Contrato de uma coleção',
                'description' => 'Campos (chaves do preset, tipos, obrigatórios, opções), taxonomias e regras da coleção para o mercado atual. Use estas chaves nas abilities de itens; nunca meta keys.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => ['collection' => $collectionInput],
                    'required' => ['collection'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => ['type' => 'string'],
                        'active' => ['type' => 'boolean'],
                        'fields' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'taxonomies' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'settings' => ['type' => 'object'],
                    ],
                    'required' => ['collection', 'fields', 'taxonomies'],
                ],
                'execute_callback' => [$this, 'getSchema'],
                'permission_callback' => fn (): bool => $this->gate->anyFlashSite(),
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/activate-collection' => [
                'label' => 'Ativar coleção',
                'description' => 'Liga uma coleção do catálogo. Idempotente. Aplica as mesmas consequências do painel (permissões, termos fixos e endereços). Não altera país, moeda nem idiomas. A coleção fica utilizável a partir do pedido seguinte.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => ['type' => 'object', 'properties' => ['collection' => $collectionInput], 'required' => ['collection']],
                'output_schema' => $stateOutput,
                'execute_callback' => fn (mixed $input = null) => $this->setActive($input, true),
                'permission_callback' => fn (): bool => $this->gate->manageCollections(),
                'meta' => AbilityMeta::meta(false, true),
            ],
            'flashsite/deactivate-collection' => [
                'label' => 'Desativar coleção',
                'description' => 'Desliga uma coleção. Idempotente. Nunca apaga itens: apenas deixa de os registar no site.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => ['type' => 'object', 'properties' => ['collection' => $collectionInput], 'required' => ['collection']],
                'output_schema' => $stateOutput,
                'execute_callback' => fn (mixed $input = null) => $this->setActive($input, false),
                'permission_callback' => fn (): bool => $this->gate->manageCollections(),
                'meta' => AbilityMeta::meta(false, true),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function listCollections(mixed $input = null): array
    {
        $collections = [];
        foreach ($this->registry->all() as $key => $preset) {
            $collections[] = $this->summary((string) $key, $preset);
        }
        return ['market' => $this->settings->market(), 'currency' => $this->settings->currency()->code, 'collections' => $collections];
    }

    /** @return array<string, mixed>|\WP_Error */
    public function getSchema(mixed $input = null): array|\WP_Error
    {
        $preset = $this->resolve($input);
        if ($preset instanceof \WP_Error) {
            return $preset;
        }
        $data = $preset->toArray();
        $fields = [];
        foreach ($preset->fields() as $field) {
            $row = $field->toArray();
            // O agente trabalha com a chave do preset; a meta key é detalhe interno.
            unset($row['meta_key']);
            $row['options'] = $field->options === [] ? new \stdClass() : $field->options;
            $fields[] = $row;
        }
        $taxonomies = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $taxonomies[] = $taxonomy->toArray();
        }
        return [
            'collection' => $preset->key(),
            'origin' => (string) $this->registry->origin($preset->key()),
            'active' => $this->activation->isActive($preset->key()),
            'market' => $this->registry->market(),
            'currency' => $this->settings->currency()->code,
            'labels' => $preset->labels(),
            'groups' => $preset->groups(),
            'supports_content' => in_array('editor', $preset->supports(), true),
            'per_language' => $this->settings->perLanguage($preset->key()),
            'fields' => $fields,
            'taxonomies' => $taxonomies,
            'settings' => (array) ($data['settings'] ?? []),
        ];
    }

    /** @return array<string, mixed>|\WP_Error */
    private function setActive(mixed $input, bool $active): array|\WP_Error
    {
        $key = is_array($input) ? (string) ($input['collection'] ?? '') : '';
        if (! $this->registry->has($key)) {
            return AbilityMeta::error('collection_not_found', sprintf('Coleção desconhecida: "%s".', $key), 404);
        }
        $result = $active ? $this->state->activate($key) : $this->state->deactivate($key);
        if ($result === null) {
            return AbilityMeta::error('save_failed', 'Não foi possível guardar o estado da coleção.', 500);
        }
        return [
            'collection' => $key,
            'active' => $result['active'],
            'changed' => $result['changed'],
            'effects' => $result['changed']
                ? ['capabilities_synced' => true, 'rewrite_flush_scheduled' => true, 'fixed_terms_seeded_on_next_request' => $active, 'items_deleted' => false]
                : new \stdClass(),
        ];
    }

    private function resolve(mixed $input): CollectionPresetInterface|\WP_Error
    {
        $key = is_array($input) ? (string) ($input['collection'] ?? '') : '';
        $preset = $this->registry->get($key);
        return $preset ?? AbilityMeta::error('collection_not_found', sprintf('Coleção desconhecida: "%s".', $key), 404);
    }

    /** @return array<string, mixed> */
    private function summary(string $key, CollectionPresetInterface $preset): array
    {
        $taxonomies = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $taxonomies[] = ['key' => $taxonomy->key, 'label' => $taxonomy->label, 'locked' => $taxonomy->locked, 'auto' => $taxonomy->auto, 'required' => $taxonomy->required, 'single' => $taxonomy->single];
        }
        $settings = $preset->toArray()['settings'] ?? [];
        return [
            'key' => $key,
            'origin' => (string) $this->registry->origin($key),
            'active' => $this->activation->isActive($key),
            'post_type' => $preset->postType(),
            'slug' => $preset->slug(),
            'labels' => ['singular' => $preset->labels()['singular'] ?? '', 'plural' => $preset->labels()['plural'] ?? ''],
            'field_count' => count($preset->fields()),
            'taxonomy_count' => count($taxonomies),
            'taxonomies' => $taxonomies,
            'per_language' => $this->settings->perLanguage($key),
            'features' => array_values(array_intersect(array_keys(is_array($settings) ? $settings : []), ['temporal', 'order', 'order_past', 'price_drop', 'cover_from', 'extra_price', 'schema_map', 'rules'])),
            'market' => $this->registry->market(),
        ];
    }

    /** @return array<string, mixed> */
    private function collectionProperty(): array
    {
        $property = ['type' => 'string', 'description' => 'Chave da coleção (ver flashsite/list-collections).'];
        $keys = $this->registry->keys();
        if ($keys !== []) {
            $property['enum'] = $keys;
        }
        return $property;
    }
}
