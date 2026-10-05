<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use InvalidArgumentException;

/**
 * Definição declarativa de um campo de uma coleção.
 *
 * Campos públicos usam a meta key `fs_<key>` (legível no "Post Custom Field" do Elementor).
 * Campos privados usam `_fs_<key>` (meta protegida, nunca exposta em REST nem output).
 *
 * @since 2.6.0
 */
final class FieldDefinition
{
    /**
     * @param array<string, string> $options valor => label
     */
    private function __construct(
        public readonly string $key,
        public readonly FieldType $type,
        public readonly string $label,
        public readonly string $group,
        public readonly int $order,
        public readonly bool $required,
        public readonly bool $public,
        public readonly array $options,
        public readonly ?float $min,
        public readonly ?float $max,
        public readonly ?int $maxLength,
        public readonly string $help,
        public readonly string $placeholder,
        public readonly string $unit,
        public readonly string $placement,
        public readonly bool $adminColumn,
        public readonly string $columnLabel,
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(string $key, array $config, int $position = 0): self
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,40}$/', $key) !== 1) {
            throw new InvalidArgumentException(sprintf('Campo "%s": a chave deve ser snake_case ASCII (máx. 41 caracteres).', $key));
        }

        $type = FieldType::tryFrom((string) ($config['type'] ?? ''));
        if ($type === null) {
            throw new InvalidArgumentException(sprintf('Campo "%s": tipo "%s" desconhecido.', $key, (string) ($config['type'] ?? '')));
        }

        $label = trim((string) ($config['label'] ?? ''));
        if ($label === '') {
            throw new InvalidArgumentException(sprintf('Campo "%s": label obrigatória.', $key));
        }

        $options = [];
        foreach ((array) ($config['options'] ?? []) as $value => $optionLabel) {
            $value = is_int($value) ? (string) $optionLabel : (string) $value;
            $options[self::normalizeOptionValue($value)] = (string) $optionLabel;
        }
        if ($type->hasOptions() && $options === []) {
            throw new InvalidArgumentException(sprintf('Campo "%s": tipo %s exige "options".', $key, $type->value));
        }

        return new self(
            $key,
            $type,
            $label,
            (string) ($config['group'] ?? 'principal'),
            (int) ($config['order'] ?? $position),
            (bool) ($config['required'] ?? false),
            // Por defeito um campo é público; dados sensíveis têm de ser marcados explicitamente.
            (bool) ($config['public'] ?? true),
            $options,
            isset($config['min']) ? (float) $config['min'] : null,
            isset($config['max']) ? (float) $config['max'] : null,
            isset($config['max_length']) ? max(1, (int) $config['max_length']) : null,
            (string) ($config['help'] ?? ''),
            (string) ($config['placeholder'] ?? ''),
            (string) ($config['unit'] ?? ''),
            // "side" mostra o campo na coluna lateral da ficha (ex.: destaque).
            ($config['placement'] ?? 'main') === 'side' ? 'side' : 'main',
            (bool) ($config['admin_column'] ?? false),
            (string) ($config['column_label'] ?? $label),
        );
    }

    public function metaKey(): string
    {
        return ($this->public ? 'fs_' : '_fs_') . $this->key;
    }

    public static function normalizeOptionValue(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9_+\-]+/', '_', $value);
        return trim($value, '_');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type->value,
            'label' => $this->label,
            'group' => $this->group,
            'order' => $this->order,
            'required' => $this->required,
            'public' => $this->public,
            'options' => $this->options,
            'min' => $this->min,
            'max' => $this->max,
            'max_length' => $this->maxLength,
            'help' => $this->help,
            'placeholder' => $this->placeholder,
            'unit' => $this->unit,
            'placement' => $this->placement,
            'admin_column' => $this->adminColumn,
            'meta_key' => $this->metaKey(),
        ];
    }
}
