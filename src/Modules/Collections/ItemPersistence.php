<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Domain\Collections\TaxonomyDefinition;

/**
 * Grava um item de coleção: campos (post meta), termos, defaults, faixa de preço e capa.
 *
 * Os valores são sempre gravados (o cliente nunca perde o que escreveu); a validação
 * devolve erros que impedem a publicação.
 *
 * @since 2.6.0
 */
final class ItemPersistence
{
    public function __construct(
        private ItemSanitizer $sanitizer,
        private ItemValidator $validator,
        private ItemReader $reader,
        private ?CollectionSettings $settings = null,
    ) {}

    /**
     * @param array<string, mixed> $input chave do campo => valor bruto
     * @return array<string, string> erros de validação
     */
    public function saveFields(CollectionPresetInterface $preset, int $postId, array $input): array
    {
        $clean = $this->sanitizer->sanitizeAll($preset, $input);

        foreach ($clean as $key => $value) {
            $field = $preset->field($key);
            if ($field === null) {
                continue;
            }
            if (ItemValidator::isEmpty($field, $value) && $field->type !== FieldType::Bool) {
                delete_post_meta($postId, $field->metaKey());
                continue;
            }
            update_post_meta($postId, $field->metaKey(), $value);
        }

        // Valida o estado completo do item (campos não enviados contam com o valor guardado).
        $current = $this->reader->read($preset, $postId, true);
        return $this->validator->validate($preset, array_merge($current, $clean));
    }

    /**
     * Valida o que vai ser gravado, sem escrever nada (usado antes de publicar).
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $selected
     * @return array<string, string>
     */
    public function validateInput(CollectionPresetInterface $preset, int $postId, array $input, array $selected = []): array
    {
        $current = $postId > 0 ? $this->reader->read($preset, $postId, true) : [];
        $errors = $this->validator->validate($preset, array_merge($current, $this->sanitizer->sanitizeAll($preset, $input)));
        foreach ($preset->taxonomies() as $taxonomy) {
            if (! $taxonomy->required || $taxonomy->auto || $taxonomy->default !== '') {
                continue;
            }
            $raw = $selected[$taxonomy->key] ?? [];
            $values = array_filter(is_array($raw) ? $raw : [$raw], static fn ($v) => is_scalar($v) && absint($v) > 0);
            if ($values === []) {
                $errors['tax_' . $taxonomy->key] = sprintf('Escolha "%s".', $taxonomy->singularLabel);
            }
        }
        return $errors;
    }

    /**
     * @param array<string, mixed> $selected chave da taxonomia => term_id|list<term_id>
     * @param array<string, array{name?: string, parent?: int|string}> $newTerms termos novos (taxonomias não fixas)
     * @return array<string, string> erros
     */
    public function saveTerms(CollectionPresetInterface $preset, int $postId, array $selected, array $newTerms = []): array
    {
        $errors = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            if ($taxonomy->auto) {
                continue;
            }
            $name = $taxonomy->taxonomyName($preset->postType());
            $ids = $this->validTermIds($name, $selected[$taxonomy->key] ?? []);

            $new = $newTerms[$taxonomy->key] ?? [];
            $newName = is_array($new) ? sanitize_text_field((string) ($new['name'] ?? '')) : '';
            if ($newName !== '' && ! $taxonomy->locked) {
                $parent = $taxonomy->hierarchical ? absint($new['parent'] ?? 0) : 0;
                $termId = $this->ensureTerm($name, $newName, '', $parent);
                if ($termId > 0) {
                    $ids[] = $termId;
                } else {
                    $errors['tax_' . $taxonomy->key] = sprintf('Não foi possível criar "%s".', $newName);
                }
            }

            if ($taxonomy->single && count($ids) > 1) {
                $ids = [end($ids)];
            }
            if ($ids === [] && $taxonomy->default !== '') {
                $defaultId = $this->ensureTerm($name, $taxonomy->terms[$taxonomy->default], $taxonomy->default);
                if ($defaultId > 0) {
                    $ids = [$defaultId];
                }
            }
            if ($ids === [] && $taxonomy->required) {
                $errors['tax_' . $taxonomy->key] = sprintf('Escolha "%s".', $taxonomy->singularLabel);
            }

            wp_set_object_terms($postId, array_values(array_unique($ids)), $name, false);
        }

        $this->syncPriceBand($preset, $postId);
        return $errors;
    }

    /** Atribui a faixa de preço automática. Seguro chamar em qualquer gravação (painel, REST, import). */
    public function syncPriceBand(CollectionPresetInterface $preset, int $postId): void
    {
        $config = PriceBandResolver::config($preset);
        if ($config === null) {
            return;
        }
        $bandTaxonomy = $this->taxonomyByKey($preset, (string) $config['taxonomy']);
        if ($bandTaxonomy === null) {
            return;
        }
        $bandName = $bandTaxonomy->taxonomyName($preset->postType());

        $priceField = $preset->field((string) $config['field']);
        $price = $priceField !== null ? $this->reader->value($priceField, $postId) : null;
        $flagField = isset($config['flag']) ? $preset->field((string) $config['flag']) : null;
        $onRequest = $flagField !== null && $this->reader->value($flagField, $postId) === true;

        $groupSlug = null;
        $groupTaxonomy = isset($config['group_taxonomy']) ? $this->taxonomyByKey($preset, (string) $config['group_taxonomy']) : null;
        if ($groupTaxonomy !== null) {
            $terms = wp_get_object_terms($postId, $groupTaxonomy->taxonomyName($preset->postType()), ['fields' => 'slugs']);
            $groupSlug = is_array($terms) && $terms !== [] ? (string) $terms[0] : null;
        }

        $band = PriceBandResolver::resolve($preset, is_float($price) ? $price : null, $onRequest, $groupSlug, $this->settings?->currency());
        if ($band === null) {
            wp_set_object_terms($postId, [], $bandName, false);
            return;
        }
        $termId = $this->ensureTerm($bandName, $band[1], $band[0], 0, true);
        wp_set_object_terms($postId, $termId > 0 ? [$termId] : [], $bandName, false);
    }

    /** A primeira foto da galeria passa a ser a imagem de capa (settings.cover_from). */
    public function syncCover(CollectionPresetInterface $preset, int $postId): void
    {
        $fieldKey = $preset->setting('cover_from');
        $field = is_string($fieldKey) ? $preset->field($fieldKey) : null;
        if ($field === null) {
            return;
        }
        $value = $this->reader->value($field, $postId);
        $first = match ($field->type) {
            FieldType::Gallery => (int) ($value[0] ?? 0),
            FieldType::Image => (int) $value,
            default => 0,
        };
        if ($first > 0) {
            set_post_thumbnail($postId, $first);
        } else {
            delete_post_thumbnail($postId);
        }
    }

    /** Garante os termos fixos e as faixas de preço dos presets indicados. */
    public function seedTerms(CollectionPresetInterface $preset): void
    {
        foreach ($preset->taxonomies() as $taxonomy) {
            $name = $taxonomy->taxonomyName($preset->postType());
            $terms = $taxonomy->auto ? PriceBandResolver::allTerms($preset, $this->settings?->currency()) : $taxonomy->terms;
            foreach ($terms as $slug => $label) {
                // Faixas automáticas: o nome acompanha a moeda do site.
                $this->ensureTerm($name, $label, $slug, 0, $taxonomy->auto);
            }
        }
    }

    public function ensureTerm(string $taxonomy, string $name, string $slug = '', int $parent = 0, bool $syncName = false): int
    {
        $existing = term_exists($slug !== '' ? $slug : $name, $taxonomy, $parent ?: null);
        $existingId = is_array($existing) ? (int) $existing['term_id'] : ((is_int($existing) || (is_string($existing) && is_numeric($existing))) ? (int) $existing : 0);
        if ($existingId > 0) {
            if ($syncName && function_exists('get_term') && function_exists('wp_update_term')) {
                $term = get_term($existingId, $taxonomy);
                if (is_object($term) && (string) $term->name !== $name) {
                    wp_update_term($existingId, $taxonomy, ['name' => $name]);
                }
            }
            return $existingId;
        }
        $args = ['parent' => $parent];
        if ($slug !== '') {
            $args['slug'] = $slug;
        }
        $created = wp_insert_term($name, $taxonomy, $args);
        if (is_wp_error($created) || ! is_array($created)) {
            return 0;
        }
        return (int) $created['term_id'];
    }

    /** @return list<int> */
    private function validTermIds(string $taxonomy, mixed $raw): array
    {
        $values = is_array($raw) ? $raw : ($raw === '' || $raw === null ? [] : [$raw]);
        $ids = [];
        foreach ($values as $value) {
            $id = is_scalar($value) ? absint($value) : 0;
            if ($id > 0 && term_exists($id, $taxonomy)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    private function taxonomyByKey(CollectionPresetInterface $preset, string $key): ?TaxonomyDefinition
    {
        foreach ($preset->taxonomies() as $taxonomy) {
            if ($taxonomy->key === $key) {
                return $taxonomy;
            }
        }
        return null;
    }
}
