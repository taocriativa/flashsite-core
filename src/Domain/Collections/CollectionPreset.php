<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use InvalidArgumentException;

/**
 * Preset declarativo construído a partir de config/collections/<key>.php.
 *
 * @since 2.6.0
 */
final class CollectionPreset implements CollectionPresetInterface
{
    private const DEFAULT_SUPPORTS = ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields'];

    /**
     * @param array<string, string> $labels
     * @param list<TaxonomyDefinition> $taxonomies
     * @param list<FieldDefinition> $fields
     * @param array<string, string> $groups
     * @param list<string> $supports
     * @param array<string, mixed> $settings
     */
    private function __construct(
        private string $key,
        private string $postType,
        private string $slug,
        private array $labels,
        private array $taxonomies,
        private array $fields,
        private array $groups,
        private ?string $schemaType,
        private array $supports,
        private string $menuIcon,
        private array $settings,
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $key = (string) ($config['key'] ?? '');
        if (preg_match('/^[a-z][a-z0-9_]{1,16}$/', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('Preset "%s": a chave deve ser snake_case ASCII entre 2 e 17 caracteres.', $key));
        }

        $postType = (string) ($config['post_type'] ?? 'fs_' . $key);
        if (preg_match('/^[a-z0-9_]{1,20}$/', $postType) !== 1) {
            throw new InvalidArgumentException(sprintf('Preset "%s": post_type "%s" inválido (máx. 20 caracteres).', $key, $postType));
        }

        $slug = trim((string) ($config['slug'] ?? $key), '/');
        if (preg_match('/^[a-z0-9\-]+$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Preset "%s": slug "%s" inválido.', $key, $slug));
        }

        $labels = array_map('strval', (array) ($config['labels'] ?? []));
        foreach (['singular', 'plural'] as $required) {
            if (trim((string) ($labels[$required] ?? '')) === '') {
                throw new InvalidArgumentException(sprintf('Preset "%s": labels.%s obrigatório.', $key, $required));
            }
        }

        $groups = [];
        foreach ((array) ($config['groups'] ?? []) as $groupKey => $groupLabel) {
            $groups[(string) $groupKey] = (string) $groupLabel;
        }
        if ($groups === []) {
            $groups = ['principal' => 'Dados principais'];
        }

        $fields = [];
        $position = 0;
        foreach ((array) ($config['fields'] ?? []) as $fieldKey => $fieldConfig) {
            if (! is_array($fieldConfig)) {
                throw new InvalidArgumentException(sprintf('Preset "%s": campo "%s" mal definido.', $key, (string) $fieldKey));
            }
            $field = FieldDefinition::fromArray((string) $fieldKey, $fieldConfig, $position++);
            if (! array_key_exists($field->group, $groups)) {
                throw new InvalidArgumentException(sprintf('Preset "%s": campo "%s" usa grupo inexistente "%s".', $key, $field->key, $field->group));
            }
            $fields[$field->key] = $field;
        }
        if ($fields === []) {
            throw new InvalidArgumentException(sprintf('Preset "%s": tem de definir pelo menos um campo.', $key));
        }
        $fields = array_values($fields);
        usort($fields, static fn (FieldDefinition $a, FieldDefinition $b): int => $a->order <=> $b->order);

        $taxonomies = [];
        $position = 0;
        foreach ((array) ($config['taxonomies'] ?? []) as $taxKey => $taxConfig) {
            if (! is_array($taxConfig)) {
                throw new InvalidArgumentException(sprintf('Preset "%s": taxonomia "%s" mal definida.', $key, (string) $taxKey));
            }
            $taxonomy = TaxonomyDefinition::fromArray((string) $taxKey, $taxConfig, $position++);
            if (strlen($taxonomy->taxonomyName($postType)) > 32) {
                throw new InvalidArgumentException(sprintf('Preset "%s": nome da taxonomia "%s" excede 32 caracteres.', $key, $taxonomy->taxonomyName($postType)));
            }
            $taxonomies[] = $taxonomy;
        }
        usort($taxonomies, static fn (TaxonomyDefinition $a, TaxonomyDefinition $b): int => $a->order <=> $b->order);

        $supports = isset($config['supports']) ? array_values(array_map('strval', (array) $config['supports'])) : self::DEFAULT_SUPPORTS;
        // Revisões e custom-fields são obrigatórios: backup por item e meta na REST.
        foreach (['revisions', 'custom-fields'] as $mandatory) {
            if (! in_array($mandatory, $supports, true)) {
                $supports[] = $mandatory;
            }
        }

        $schemaType = isset($config['schema']) && $config['schema'] !== '' ? (string) $config['schema'] : null;

        return new self(
            $key,
            $postType,
            $slug,
            $labels,
            $taxonomies,
            $fields,
            $groups,
            $schemaType,
            $supports,
            (string) ($config['menu_icon'] ?? 'dashicons-screenoptions'),
            is_array($config['settings'] ?? null) ? $config['settings'] : [],
        );
    }

    public function key(): string { return $this->key; }
    public function postType(): string { return $this->postType; }
    public function slug(): string { return $this->slug; }
    public function labels(): array { return $this->labels; }
    public function taxonomies(): array { return $this->taxonomies; }
    public function fields(): array { return $this->fields; }
    public function groups(): array { return $this->groups; }
    public function schemaType(): ?string { return $this->schemaType; }
    public function supports(): array { return $this->supports; }
    public function menuIcon(): string { return $this->menuIcon; }
    public function capabilitySingular(): string { return $this->postType; }
    public function capabilityPlural(): string { return $this->postType . '_items'; }

    public function field(string $key): ?FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }
        return null;
    }

    public function publicFields(): array
    {
        return array_values(array_filter($this->fields, static fn (FieldDefinition $f): bool => $f->public));
    }

    public function taxonomy(string $key): ?TaxonomyDefinition
    {
        foreach ($this->taxonomies as $taxonomy) {
            if ($taxonomy->key === $key) {
                return $taxonomy;
            }
        }
        return null;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'post_type' => $this->postType,
            'slug' => $this->slug,
            'labels' => $this->labels,
            'groups' => $this->groups,
            'schema' => $this->schemaType,
            'supports' => $this->supports,
            'menu_icon' => $this->menuIcon,
            'settings' => $this->settings,
            'taxonomies' => array_map(static fn (TaxonomyDefinition $t): array => $t->toArray(), $this->taxonomies),
            'fields' => array_map(static fn (FieldDefinition $f): array => $f->toArray(), $this->fields),
        ];
    }
}
