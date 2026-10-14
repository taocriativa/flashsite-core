<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Modules\Collections\CollectionItemService;

/**
 * Itens de Coleções: leitura, validação e gravação. Adaptadores finos sobre o CollectionItemService,
 * que usa o pipeline real do Core. Os campos dinâmicos vêm do preset: nada de condicionais por coleção.
 *
 * @since 3.5.0
 */
final class ItemAbilities implements AbilityProvider
{
    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private CollectionItemService $items,
        private AbilityGate $gate,
    ) {}

    public function definitions(): array
    {
        $collection = ['type' => 'string', 'description' => 'Chave da coleção (ver flashsite/list-collections).'];
        if ($this->registry->keys() !== []) {
            $collection['enum'] = $this->registry->keys();
        }
        $id = ['type' => 'integer', 'minimum' => 1, 'description' => 'ID do item.'];
        $fields = ['type' => 'object', 'description' => 'Valores por chave de campo do preset (ver flashsite/get-collection-schema). Chaves desconhecidas são rejeitadas.'];
        $taxonomies = ['type' => 'object', 'description' => 'Termos por chave de taxonomia: slug, nome ou ID (ou lista). Termo novo só em taxonomias não fixas. Taxonomias automáticas não se definem.'];
        $writable = ['draft', 'pending', 'private', 'publish'];
        $item = ['type' => 'object', 'description' => 'Representação do item.'];
        $permission = fn (mixed $input = null): bool => $this->gate->items($input);

        return [
            'flashsite/list-items' => [
                'label' => 'Listar itens de uma coleção',
                'description' => 'Lista paginada de itens (máx. 50 por página). Sem permissão de editar itens de terceiros, só devolve os do próprio utilizador.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => $collection,
                        'status' => ['type' => 'string', 'enum' => CollectionItemService::LIST_STATUSES, 'default' => 'any'],
                        'search' => ['type' => 'string', 'maxLength' => 200],
                        'page' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                        'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => CollectionItemService::MAX_PER_PAGE, 'default' => CollectionItemService::DEFAULT_PER_PAGE],
                        'orderby' => ['type' => 'string', 'description' => 'date, title, modified, menu_order, preset ou field:<chave de campo público>.'],
                        'order' => ['type' => 'string', 'enum' => ['ASC', 'DESC']],
                        'when' => ['type' => 'string', 'enum' => ['upcoming', 'past', 'all'], 'description' => 'Janela temporal, em coleções com settings.temporal.'],
                        'lang' => ['type' => 'string', 'description' => 'Código de idioma (requer Polylang).'],
                    ],
                    'required' => ['collection'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => ['type' => 'string'],
                        'items' => ['type' => 'array', 'items' => $item],
                        'total' => ['type' => 'integer'],
                        'total_pages' => ['type' => 'integer'],
                        'page' => ['type' => 'integer'],
                        'per_page' => ['type' => 'integer'],
                    ],
                    'required' => ['items', 'total'],
                ],
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->items->list($p, $in), true),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/get-item' => [
                'label' => 'Ler um item',
                'description' => 'Representação canónica de um item para edição: campos por chave do preset e termos. Confirma que o item pertence à coleção.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => ['type' => 'object', 'properties' => ['collection' => $collection, 'id' => $id], 'required' => ['collection', 'id']],
                'output_schema' => $item,
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->items->get($p, (int) ($in['id'] ?? 0)), false),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/validate-item' => [
                'label' => 'Validar item (sem gravar)',
                'description' => 'Corre a sanitização e a validação reais do preset sem gravar nada. Com "id", valida o estado resultante de um patch.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => ['collection' => $collection, 'id' => $id, 'fields' => $fields, 'taxonomies' => $taxonomies],
                    'required' => ['collection'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'valid' => ['type' => 'boolean'],
                        'normalized' => ['type' => 'object', 'description' => 'Valores canónicos que seriam gravados.'],
                        'errors' => ['type' => 'object', 'description' => 'chave => {code, message}.'],
                    ],
                    'required' => ['valid', 'normalized', 'errors'],
                ],
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->validate($p, $in), false),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/create-item' => [
                'label' => 'Criar item',
                'description' => 'Cria um item. Estado por defeito: draft. Só publica com status=publish explícito, permissão de publicar e ficha íntegra. Rascunhos incompletos são gravados e listados em "incomplete".',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => $collection,
                        'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                        'content' => ['type' => 'string', 'description' => 'Descrição, em coleções que a suportam.'],
                        'slug' => ['type' => 'string', 'maxLength' => 200],
                        'status' => ['type' => 'string', 'enum' => $writable, 'default' => 'draft'],
                        'fields' => $fields,
                        'taxonomies' => $taxonomies,
                    ],
                    'required' => ['collection', 'title'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => ['item' => $item, 'created' => ['type' => 'boolean'], 'incomplete' => ['type' => 'object']],
                    'required' => ['item', 'created'],
                ],
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->items->create($p, $in), true),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(false, false),
            ],
            'flashsite/update-item' => [
                'label' => 'Atualizar item (patch)',
                'description' => 'Atualiza só o que for enviado; campos omitidos são preservados. Campos desconhecidos são rejeitados. Aplica preço, faixas e capa do preset.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'collection' => $collection,
                        'id' => $id,
                        'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                        'content' => ['type' => 'string'],
                        'slug' => ['type' => 'string', 'maxLength' => 200],
                        'status' => ['type' => 'string', 'enum' => $writable],
                        'fields' => $fields,
                        'taxonomies' => $taxonomies,
                    ],
                    'required' => ['collection', 'id'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'item' => $item,
                        'changed_fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'changed_taxonomies' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'incomplete' => ['type' => 'object'],
                    ],
                    'required' => ['item', 'changed_fields'],
                ],
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->items->update($p, (int) ($in['id'] ?? 0), $in), true),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(false, true),
            ],
            'flashsite/trash-item' => [
                'label' => 'Mover item para o lixo',
                'description' => 'Move o item para o Lixo do WordPress (recuperável). Nunca apaga definitivamente.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => ['type' => 'object', 'properties' => ['collection' => $collection, 'id' => $id], 'required' => ['collection', 'id']],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => ['id' => ['type' => 'integer'], 'collection' => ['type' => 'string'], 'status' => ['type' => 'string']],
                    'required' => ['id', 'status'],
                ],
                'execute_callback' => fn (mixed $input = null) => $this->run($input, fn (CollectionPresetInterface $p, array $in) => $this->items->trash($p, (int) ($in['id'] ?? 0)), true),
                'permission_callback' => $permission,
                'meta' => AbilityMeta::meta(false, true),
            ],
        ];
    }

    /**
     * @param callable(CollectionPresetInterface, array<string, mixed>): (array<string, mixed>|\WP_Error) $operation
     * @return array<string, mixed>|\WP_Error
     */
    private function run(mixed $input, callable $operation, bool $requiresActive): array|\WP_Error
    {
        $input = is_array($input) ? $input : [];
        $key = (string) ($input['collection'] ?? '');
        $preset = $this->registry->get($key);
        if ($preset === null) {
            return AbilityMeta::error('collection_not_found', sprintf('Coleção desconhecida: "%s".', $key), 404);
        }
        if (! $this->activation->isActive($key)) {
            return AbilityMeta::error('collection_inactive', sprintf('A coleção "%s" não está ativa. Ative-a com flashsite/activate-collection.', $key), 409);
        }
        return $operation($preset, $input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|\WP_Error
     */
    private function validate(CollectionPresetInterface $preset, array $input): array|\WP_Error
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $existing = $this->items->get($preset, $id);
            if ($existing instanceof \WP_Error) {
                return $existing;
            }
        }
        return $this->items->validate(
            $preset,
            $id,
            is_array($input['fields'] ?? null) ? $input['fields'] : [],
            is_array($input['taxonomies'] ?? null) ? $input['taxonomies'] : []
        );
    }
}
