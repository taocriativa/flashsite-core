<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use InvalidArgumentException;

/**
 * Taxonomia de uma coleção (tudo o que for filtrável).
 *
 * `locked = true` significa estrutura fixa do preset (ex.: finalidade, estado):
 * o cliente atribui termos mas não cria, edita nem apaga termos.
 * `locked = false` (ex.: zona, categoria do menu) deixa o cliente gerir os termos.
 * `auto = true` é atribuída pelo sistema (ex.: faixa de preço) e não aparece na ficha.
 * `default` é o slug do termo usado quando o item é gravado sem escolha.
 *
 * @since 2.6.0
 */
final class TaxonomyDefinition
{
    /**
     * @param array<string, string> $terms slug => nome
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $singularLabel,
        public readonly bool $hierarchical,
        public readonly bool $locked,
        public readonly bool $filterable,
        public readonly bool $single,
        public readonly array $terms,
        public readonly string $slug,
        public readonly int $order,
        public readonly bool $auto,
        public readonly bool $required,
        public readonly string $default,
        public readonly bool $adminColumn,
        public readonly string $help,
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(string $key, array $config, int $position = 0): self
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,15}$/', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('Taxonomia "%s": a chave deve ser snake_case ASCII (máx. 16 caracteres).', $key));
        }

        $label = trim((string) ($config['label'] ?? ''));
        if ($label === '') {
            throw new InvalidArgumentException(sprintf('Taxonomia "%s": label obrigatória.', $key));
        }

        $terms = [];
        foreach ((array) ($config['terms'] ?? []) as $slug => $name) {
            $slug = is_int($slug) ? FieldDefinition::normalizeOptionValue((string) $name) : FieldDefinition::normalizeOptionValue((string) $slug);
            $terms[str_replace('_', '-', $slug)] = (string) $name;
        }

        $auto = (bool) ($config['auto'] ?? false);
        $locked = $auto || (bool) ($config['locked'] ?? $terms !== []);
        $default = str_replace('_', '-', FieldDefinition::normalizeOptionValue((string) ($config['default'] ?? '')));
        if ($default !== '' && ! array_key_exists($default, $terms)) {
            throw new InvalidArgumentException(sprintf('Taxonomia "%s": termo por defeito "%s" não existe em "terms".', $key, $default));
        }

        return new self(
            $key,
            $label,
            trim((string) ($config['singular'] ?? $label)),
            (bool) ($config['hierarchical'] ?? false),
            $locked,
            (bool) ($config['filterable'] ?? true),
            (bool) ($config['single'] ?? false),
            $terms,
            (string) ($config['slug'] ?? str_replace('_', '-', $key)),
            (int) ($config['order'] ?? $position),
            $auto,
            (bool) ($config['required'] ?? false),
            $default,
            (bool) ($config['admin_column'] ?? false),
            (string) ($config['help'] ?? ''),
        );
    }

    /** Nome da taxonomia no WP (máx. 32 caracteres). */
    public function taxonomyName(string $postType): string
    {
        return $postType . '_' . $this->key;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'singular' => $this->singularLabel,
            'hierarchical' => $this->hierarchical,
            'locked' => $this->locked,
            'filterable' => $this->filterable,
            'single' => $this->single,
            'terms' => $this->terms,
            'slug' => $this->slug,
            'order' => $this->order,
            'auto' => $this->auto,
            'required' => $this->required,
            'default' => $this->default,
            'admin_column' => $this->adminColumn,
            'help' => $this->help,
        ];
    }
}
