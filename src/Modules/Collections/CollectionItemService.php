<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionQuery;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\OrderResolver;
use FlashSite\Core\Domain\Collections\TaxonomyDefinition;
use FlashSite\Core\Domain\Collections\TemporalResolver;

/**
 * Casos de uso sobre itens de Coleções para consumo programático (Abilities).
 *
 * Não tem regras próprias de domínio: sanitização, validação, preço, faixas, capa e termos são
 * do pipeline existente (ItemSanitizer, ItemValidator, ItemPersistence). Este serviço só traduz
 * "chaves de campo do preset" em chamadas a esse pipeline, com as permissões reais do WordPress.
 *
 * Política de gravação:
 *  - campo desconhecido, valor inválido (que o sanitizador descartaria) ou termo desconhecido
 *    rejeitam o pedido inteiro, sem gravar nada;
 *  - rascunho incompleto (campo obrigatório em falta) é gravado e devolvido em "incomplete",
 *    como no painel;
 *  - publicar (ou manter publicado) exige a ficha íntegra: caso contrário o pedido é rejeitado.
 *
 * Erros: \WP_Error com código estável e, em validação, data['errors'] = chave => [code, message].
 *
 * @since 3.5.0
 */
final class CollectionItemService
{
    public const WRITABLE_STATUSES = ['draft', 'pending', 'private', 'publish'];
    public const LIST_STATUSES = ['any', 'publish', 'draft', 'pending', 'private', 'future', 'trash'];
    public const MAX_PER_PAGE = 50;
    public const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private ItemPersistence $persistence,
        private ItemSanitizer $sanitizer,
        private ItemValidator $validator,
        private ItemReader $reader,
    ) {}

    // ------------------------------------------------------------------ leitura

    /**
     * @param array<string, mixed> $args status, search, page, per_page, orderby, order, when, lang
     * @return array<string, mixed>|\WP_Error
     */
    public function list(CollectionPresetInterface $preset, array $args): array|\WP_Error
    {
        $status = (string) ($args['status'] ?? 'any');
        if (! in_array($status, self::LIST_STATUSES, true)) {
            return $this->error('invalid_status', 'Estado desconhecido.', 422);
        }
        $perPage = max(1, min(self::MAX_PER_PAGE, (int) ($args['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $page = max(1, (int) ($args['page'] ?? 1));

        $query = [
            'post_type' => $preset->postType(),
            'post_status' => $status === 'any' ? ['publish', 'draft', 'pending', 'private', 'future'] : $status,
            'posts_per_page' => $perPage,
            'paged' => $page,
            'no_found_rows' => false,
        ];
        $search = trim((string) ($args['search'] ?? ''));
        if ($search !== '') {
            $query['s'] = $search;
        }
        if (! $this->can('edit_others_' . $preset->capabilityPlural())) {
            $query['author'] = get_current_user_id();
        }

        $lang = trim((string) ($args['lang'] ?? ''));
        if ($lang !== '') {
            if (! function_exists('pll_get_post_language')) {
                return $this->error('lang_unsupported', 'Filtro de idioma indisponível: o Polylang não está ativo.', 422);
            }
            $query['lang'] = sanitize_key($lang);
        }

        $when = TemporalResolver::normalizeWhen($args['when'] ?? null);
        if (isset($args['when']) && $args['when'] !== '' && $when === null) {
            return $this->error('invalid_when', 'Valor de "when" inválido (upcoming, past ou all).', 422);
        }
        $order = strtoupper((string) ($args['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $orderby = (string) ($args['orderby'] ?? '');
        $rules = [];
        if ($orderby === 'preset') {
            $rules = OrderResolver::rulesFor($preset, $when);
        } elseif ($orderby === 'modified') {
            $query['orderby'] = 'modified';
            $query['order'] = $order;
        } elseif ($orderby !== '') {
            $token = match ($orderby) {
                'date' => '@date',
                'title' => '@title',
                'menu_order' => '@menu_order',
                default => $orderby,
            };
            $rules = OrderResolver::fromToken($preset, $token, $order);
            if ($rules === []) {
                return $this->error('invalid_orderby', 'Ordenação desconhecida para esta coleção.', 422);
            }
        }
        $query = CollectionQuery::apply($query, $preset, $when, $rules);

        $result = new \WP_Query($query);
        $items = [];
        foreach ($result->posts as $post) {
            if ($post instanceof \WP_Post) {
                $items[] = $this->summary($preset, $post);
            }
        }
        return [
            'collection' => $preset->key(),
            'items' => $items,
            'total' => (int) $result->found_posts,
            'total_pages' => (int) $result->max_num_pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|\WP_Error */
    public function get(CollectionPresetInterface $preset, int $id): array|\WP_Error
    {
        $post = $this->ownedPost($preset, $id);
        if ($post instanceof \WP_Error) {
            return $post;
        }
        if (! $this->can('edit_post', $id)) {
            return $this->error('forbidden', 'Sem permissão para ver este item.', 403);
        }
        return $this->detail($preset, $post);
    }

    // ---------------------------------------------------------------- validação

    /**
     * Sem efeitos secundários. Em atualização (id > 0) valida o estado resultante do patch.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $taxonomies
     * @return array{valid: bool, normalized: array<string, mixed>, errors: array<string, array{code: string, message: string}>}
     */
    public function validate(CollectionPresetInterface $preset, int $id, array $fields, array $taxonomies): array
    {
        $plan = $this->plan($preset, $id, $fields, $taxonomies);
        return ['valid' => $plan['errors'] === [], 'normalized' => $plan['normalized'], 'errors' => $plan['errors']];
    }

    // ------------------------------------------------------------------- escrita

    /**
     * @param array<string, mixed> $data title, content, slug, status, fields, taxonomies
     * @return array<string, mixed>|\WP_Error
     */
    public function create(CollectionPresetInterface $preset, array $data): array|\WP_Error
    {
        $plural = $preset->capabilityPlural();
        if (! $this->can('edit_' . $plural)) {
            return $this->error('forbidden', 'Sem permissão para criar itens nesta coleção.', 403);
        }
        $status = (string) ($data['status'] ?? 'draft');
        $statusError = $this->checkStatus($preset, $status);
        if ($statusError !== null) {
            return $statusError;
        }
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return $this->error('validation_failed', 'O título é obrigatório.', 422, ['errors' => ['title' => ['code' => 'required', 'message' => 'O título é obrigatório.']]]);
        }

        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $plan = $this->plan($preset, 0, $fields, is_array($data['taxonomies'] ?? null) ? $data['taxonomies'] : []);
        $blocking = $this->blockingErrors($plan, $status === 'publish');
        if ($blocking !== []) {
            return $this->error('validation_failed', 'O pedido tem erros e nada foi gravado.', 422, ['errors' => $blocking]);
        }

        $postarr = [
            'post_type' => $preset->postType(),
            // Publica no fim, depois de campos, termos, faixa de preço e capa estarem gravados.
            'post_status' => $status === 'publish' ? 'draft' : $status,
            'post_title' => sanitize_text_field($title),
            'post_author' => get_current_user_id(),
        ];
        if (isset($data['slug']) && trim((string) $data['slug']) !== '') {
            $postarr['post_name'] = sanitize_title((string) $data['slug']);
        }
        if (isset($data['content']) && in_array('editor', $preset->supports(), true)) {
            $postarr['post_content'] = wp_kses_post((string) $data['content']);
        }
        $id = wp_insert_post($postarr, true);
        if (is_wp_error($id) || (int) $id <= 0) {
            return $this->error('save_failed', 'Não foi possível criar o item.', 500);
        }
        $id = (int) $id;

        $this->persist($preset, $id, $fields, $plan);
        if ($status === 'publish') {
            wp_update_post(['ID' => $id, 'post_status' => 'publish']);
        }

        $post = get_post($id);
        $item = $post instanceof \WP_Post ? $this->detail($preset, $post) : ['id' => $id];
        return ['item' => $item, 'created' => true, 'incomplete' => $plan['incomplete']];
    }

    /**
     * Patch: campos omitidos são preservados.
     *
     * @param array<string, mixed> $data title, content, slug, status, fields, taxonomies
     * @return array<string, mixed>|\WP_Error
     */
    public function update(CollectionPresetInterface $preset, int $id, array $data): array|\WP_Error
    {
        $post = $this->ownedPost($preset, $id);
        if ($post instanceof \WP_Error) {
            return $post;
        }
        if (! $this->can('edit_post', $id)) {
            return $this->error('forbidden', 'Sem permissão para editar este item.', 403);
        }
        if ($post->post_status === 'trash') {
            return $this->error('item_in_trash', 'O item está no lixo.', 409);
        }

        $currentStatus = (string) $post->post_status;
        $status = isset($data['status']) ? (string) $data['status'] : $currentStatus;
        if ($status !== $currentStatus) {
            $statusError = $this->checkStatus($preset, $status);
            if ($statusError !== null) {
                return $statusError;
            }
        }
        $title = isset($data['title']) ? trim((string) $data['title']) : null;
        if ($title === '') {
            return $this->error('validation_failed', 'O título não pode ficar vazio.', 422, ['errors' => ['title' => ['code' => 'required', 'message' => 'O título não pode ficar vazio.']]]);
        }

        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $before = $this->reader->read($preset, $id, true);
        $beforeTerms = $this->currentTerms($preset, $id);
        $plan = $this->plan($preset, $id, $fields, is_array($data['taxonomies'] ?? null) ? $data['taxonomies'] : []);
        $mustBeValid = in_array($status, ['publish', 'future'], true);
        $blocking = $this->blockingErrors($plan, $mustBeValid);
        if ($blocking !== []) {
            return $this->error('validation_failed', 'O pedido tem erros e nada foi gravado.', 422, ['errors' => $blocking]);
        }

        $postarr = ['ID' => $id];
        if ($title !== null) {
            $postarr['post_title'] = sanitize_text_field($title);
        }
        if (isset($data['slug']) && trim((string) $data['slug']) !== '') {
            $postarr['post_name'] = sanitize_title((string) $data['slug']);
        }
        if (isset($data['content']) && in_array('editor', $preset->supports(), true)) {
            $postarr['post_content'] = wp_kses_post((string) $data['content']);
        }
        if (count($postarr) > 1) {
            $updated = wp_update_post($postarr, true);
            if (is_wp_error($updated) || (int) $updated <= 0) {
                return $this->error('save_failed', 'Não foi possível atualizar o item.', 500);
            }
        }

        $this->persist($preset, $id, $fields, $plan);
        if ($status !== $currentStatus) {
            wp_update_post(['ID' => $id, 'post_status' => $status]);
        }

        $after = $this->reader->read($preset, $id, true);
        $changed = [];
        foreach (array_keys($plan['normalized']) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = (string) $key;
            }
        }
        $changedTerms = [];
        foreach ($this->currentTerms($preset, $id) as $key => $ids) {
            if (($beforeTerms[$key] ?? []) !== $ids) {
                $changedTerms[] = (string) $key;
            }
        }
        $fresh = get_post($id);
        return [
            'item' => $fresh instanceof \WP_Post ? $this->detail($preset, $fresh) : ['id' => $id],
            'changed_fields' => $changed,
            'changed_taxonomies' => $changedTerms,
            'incomplete' => $plan['incomplete'],
        ];
    }

    /**
     * Move para o Lixo do WordPress (recuperável). Nunca apaga definitivamente.
     *
     * @return array<string, mixed>|\WP_Error
     */
    public function trash(CollectionPresetInterface $preset, int $id): array|\WP_Error
    {
        $post = $this->ownedPost($preset, $id);
        if ($post instanceof \WP_Error) {
            return $post;
        }
        if (! $this->can('delete_post', $id)) {
            return $this->error('forbidden', 'Sem permissão para apagar este item.', 403);
        }
        if ($post->post_status !== 'trash') {
            $result = wp_trash_post($id);
            if (! $result) {
                return $this->error('trash_failed', 'Não foi possível mover o item para o lixo.', 500);
            }
        }
        $fresh = get_post($id);
        return ['id' => $id, 'collection' => $preset->key(), 'status' => $fresh instanceof \WP_Post ? (string) $fresh->post_status : 'trash'];
    }

    // ---------------------------------------------------------------- internals

    /**
     * Planeia um pedido: normaliza campos, resolve termos e junta todos os erros.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $taxonomies
     * @return array{
     *   normalized: array<string, mixed>,
     *   errors: array<string, array{code: string, message: string}>,
     *   input_errors: array<string, array{code: string, message: string}>,
     *   incomplete: array<string, array{code: string, message: string}>,
     *   selected: array<string, list<int>>,
     *   new_terms: array<string, array{name: string}>,
     *   provided_taxonomies: list<string>
     * }
     */
    private function plan(CollectionPresetInterface $preset, int $id, array $fields, array $taxonomies): array
    {
        $normalized = [];
        $inputErrors = [];

        foreach ($fields as $key => $raw) {
            $key = (string) $key;
            $field = $preset->field($key);
            if ($field === null) {
                $inputErrors[$key] = ['code' => 'unknown_field', 'message' => sprintf('Campo desconhecido: "%s".', $key)];
                continue;
            }
            $clean = $this->sanitizer->sanitize($field, $raw);
            // O sanitizador descarta em silêncio o que não reconhece; um agente tem de saber.
            if (ItemValidator::isEmpty($field, $clean) && $field->type !== FieldType::Bool && ! $this->isIntentionallyEmpty($raw)) {
                $inputErrors[$key] = ['code' => 'invalid_value', 'message' => sprintf('Valor inválido para "%s" (%s).', $field->label, $field->type->value)];
                continue;
            }
            $normalized[$key] = $clean;
        }

        $resolved = $this->resolveTaxonomies($preset, $taxonomies);
        $inputErrors += $resolved['errors'];

        $current = $id > 0 ? $this->currentTerms($preset, $id) : [];
        $selected = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $selected[$taxonomy->key] = $resolved['selected'][$taxonomy->key] ?? ($current[$taxonomy->key] ?? []);
        }

        // Estado completo para a validação: bool omitido mantém o valor guardado (patch, não checkbox).
        $input = $normalized;
        foreach ($preset->fields() as $field) {
            if ($field->type === FieldType::Bool && ! array_key_exists($field->key, $input)) {
                $input[$field->key] = $id > 0 ? $this->reader->value($field, $id) : false;
            }
        }
        $domain = $this->persistence->validateInput($preset, $id, $input, $selected);
        $incomplete = [];
        foreach ($domain as $key => $message) {
            $field = $preset->field((string) $key);
            $incomplete[(string) $key] = [
                'code' => $field !== null && $field->required ? 'required' : 'invalid',
                'message' => (string) $message,
            ];
        }

        return [
            'normalized' => $normalized,
            'errors' => $inputErrors + $incomplete,
            'input_errors' => $inputErrors,
            'incomplete' => $incomplete,
            'selected' => $selected,
            'new_terms' => $resolved['new'],
            'provided_taxonomies' => array_keys($resolved['selected'] + $resolved['new']),
        ];
    }

    /**
     * @param array{errors: array<string, array{code: string, message: string}>, input_errors: array<string, array{code: string, message: string}>, incomplete: array<string, array{code: string, message: string}>} $plan
     * @return array<string, array{code: string, message: string}>
     */
    private function blockingErrors(array $plan, bool $needsValidItem): array
    {
        return $needsValidItem ? $plan['errors'] : $plan['input_errors'];
    }

    /**
     * Grava campos, termos, faixa de preço e capa pelo pipeline do Core.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $plan
     */
    private function persist(CollectionPresetInterface $preset, int $id, array $fields, array $plan): void
    {
        // Só o que o agente enviou, mais os bool atuais (o pipeline trata bool omitido como checkbox desmarcada).
        $input = [];
        foreach (array_keys($plan['normalized']) as $key) {
            $input[$key] = $fields[$key];
        }
        foreach ($preset->fields() as $field) {
            if ($field->type === FieldType::Bool && ! array_key_exists($field->key, $input)) {
                $input[$field->key] = $this->reader->value($field, $id);
            }
        }
        $this->persistence->saveFields($preset, $id, $input);
        $this->persistence->saveTerms($preset, $id, $plan['selected'], $plan['new_terms']);
        $this->persistence->syncCover($preset, $id);
    }

    /**
     * Termos pedidos pelo agente: id numérico, slug ou nome. Termo novo só em taxonomias não fixas.
     *
     * @param array<string, mixed> $input
     * @return array{selected: array<string, list<int>>, new: array<string, array{name: string}>, errors: array<string, array{code: string, message: string}>}
     */
    private function resolveTaxonomies(CollectionPresetInterface $preset, array $input): array
    {
        $selected = [];
        $new = [];
        $errors = [];
        foreach ($input as $key => $raw) {
            $key = (string) $key;
            $taxonomy = $preset->taxonomy($key);
            $errKey = 'tax_' . $key;
            if ($taxonomy === null) {
                $errors[$errKey] = ['code' => 'unknown_taxonomy', 'message' => sprintf('Taxonomia desconhecida: "%s".', $key)];
                continue;
            }
            if ($taxonomy->auto) {
                $errors[$errKey] = ['code' => 'taxonomy_auto', 'message' => sprintf('"%s" é calculada automaticamente.', $taxonomy->singularLabel)];
                continue;
            }
            $values = is_array($raw) ? array_values($raw) : ($raw === '' || $raw === null ? [] : [$raw]);
            if ($taxonomy->single && count($values) > 1) {
                $errors[$errKey] = ['code' => 'too_many_terms', 'message' => sprintf('"%s" aceita um só valor.', $taxonomy->singularLabel)];
                continue;
            }
            $name = $taxonomy->taxonomyName($preset->postType());
            $ids = [];
            foreach ($values as $value) {
                if (! is_scalar($value)) {
                    $errors[$errKey] = ['code' => 'invalid_term', 'message' => 'Termo inválido.'];
                    continue 2;
                }
                $value = trim((string) $value);
                $existing = $value !== '' && ctype_digit($value) ? term_exists((int) $value, $name) : term_exists($value, $name);
                $termId = is_array($existing) ? (int) $existing['term_id'] : (is_numeric($existing) ? (int) $existing : 0);
                if ($termId > 0) {
                    $ids[] = $termId;
                    continue;
                }
                if ($taxonomy->locked || ($value !== '' && ctype_digit($value))) {
                    $errors[$errKey] = ['code' => 'unknown_term', 'message' => sprintf('Termo desconhecido em "%s": %s.', $taxonomy->singularLabel, $value)];
                    continue 2;
                }
                if (isset($new[$key])) {
                    $errors[$errKey] = ['code' => 'too_many_new_terms', 'message' => sprintf('Só é possível criar um termo novo de cada vez em "%s".', $taxonomy->singularLabel)];
                    continue 2;
                }
                $new[$key] = ['name' => $value];
            }
            $selected[$key] = $ids;
        }
        return ['selected' => $selected, 'new' => $new, 'errors' => $errors];
    }

    /** @return array<string, list<int>> chave da taxonomia => ids atuais */
    private function currentTerms(CollectionPresetInterface $preset, int $id): array
    {
        $out = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $ids = wp_get_object_terms($id, $taxonomy->taxonomyName($preset->postType()), ['fields' => 'ids']);
            $out[$taxonomy->key] = is_array($ids) ? array_values(array_map('intval', $ids)) : [];
        }
        return $out;
    }

    /** Enviar "", null ou [] limpa o campo de propósito. */
    private function isIntentionallyEmpty(mixed $raw): bool
    {
        return $raw === null || $raw === '' || $raw === [] || (is_string($raw) && trim($raw) === '');
    }

    private function checkStatus(CollectionPresetInterface $preset, string $status): ?\WP_Error
    {
        if (! in_array($status, self::WRITABLE_STATUSES, true)) {
            return $this->error('invalid_status', 'Estado inválido (draft, pending, private ou publish).', 422);
        }
        if ($status === 'publish' && ! $this->can('publish_' . $preset->capabilityPlural())) {
            return $this->error('forbidden_publish', 'Sem permissão para publicar nesta coleção.', 403);
        }
        return null;
    }

    /** @return \WP_Post|\WP_Error */
    private function ownedPost(CollectionPresetInterface $preset, int $id): \WP_Post|\WP_Error
    {
        $post = $id > 0 ? get_post($id) : null;
        // Mesma resposta para "não existe" e "é de outro tipo": não revela posts alheios.
        if (! $post instanceof \WP_Post || $post->post_type !== $preset->postType()) {
            return $this->error('item_not_found', 'Item não encontrado nesta coleção.', 404);
        }
        return $post;
    }

    /** @return array<string, mixed> */
    private function summary(CollectionPresetInterface $preset, \WP_Post $post): array
    {
        $values = $this->reader->read($preset, $post->ID, true);
        $fields = [];
        foreach ($preset->fields() as $field) {
            if ($field->adminColumn) {
                $fields[$field->key] = $values[$field->key] ?? $field->type->emptyValue();
            }
        }
        return $this->base($preset, $post) + ['fields' => $fields];
    }

    /** @return array<string, mixed> */
    private function detail(CollectionPresetInterface $preset, \WP_Post $post): array
    {
        $item = $this->base($preset, $post) + [
            'fields' => $this->reader->read($preset, $post->ID, true),
            'taxonomies' => $this->termsOf($preset, $post->ID),
        ];
        if (in_array('editor', $preset->supports(), true)) {
            $item['content'] = (string) $post->post_content;
        }
        return $item;
    }

    /** @return array<string, mixed> */
    private function base(CollectionPresetInterface $preset, \WP_Post $post): array
    {
        $language = function_exists('pll_get_post_language') ? pll_get_post_language($post->ID) : null;
        return [
            'id' => (int) $post->ID,
            'title' => (string) $post->post_title,
            'slug' => (string) $post->post_name,
            'status' => (string) $post->post_status,
            'collection' => $preset->key(),
            'language' => is_string($language) && $language !== '' ? $language : null,
            'modified' => (string) $post->post_modified_gmt,
        ];
    }

    /** @return array<string, list<array{id: int, slug: string, name: string}>> */
    private function termsOf(CollectionPresetInterface $preset, int $id): array
    {
        $out = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $terms = [];
            $name = $taxonomy->taxonomyName($preset->postType());
            foreach ($this->termObjects($id, $name) as $term) {
                $terms[] = ['id' => (int) $term['term_id'], 'slug' => (string) $term['slug'], 'name' => (string) $term['name']];
            }
            $out[$taxonomy->key] = $terms;
        }
        return $out;
    }

    /** @return list<array{term_id: int, slug: string, name: string}> */
    private function termObjects(int $id, string $taxonomy): array
    {
        $ids = wp_get_object_terms($id, $taxonomy, ['fields' => 'ids']);
        $slugs = wp_get_object_terms($id, $taxonomy, ['fields' => 'slugs']);
        $names = wp_get_object_terms($id, $taxonomy, ['fields' => 'names']);
        $out = [];
        foreach (is_array($ids) ? array_values($ids) : [] as $i => $termId) {
            $out[] = ['term_id' => (int) $termId, 'slug' => (string) ($slugs[$i] ?? ''), 'name' => (string) ($names[$i] ?? '')];
        }
        return $out;
    }

    private function can(string $capability, int ...$args): bool
    {
        return function_exists('current_user_can') && current_user_can($capability, ...$args);
    }

    /** @param array<string, mixed> $data */
    private function error(string $code, string $message, int $status, array $data = []): \WP_Error
    {
        return new \WP_Error($code, $message, ['status' => $status] + $data);
    }
}
