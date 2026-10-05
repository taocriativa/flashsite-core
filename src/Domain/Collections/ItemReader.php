<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Lê os valores de um item a partir de post meta, com projeção pública ou completa.
 *
 * @since 2.6.0
 */
final class ItemReader
{
    /**
     * @return array<string, mixed> chave do campo => valor canónico
     */
    public function read(CollectionPresetInterface $preset, int $postId, bool $includePrivate = false): array
    {
        $values = [];
        foreach ($preset->fields() as $field) {
            if (! $field->public && ! $includePrivate) {
                continue;
            }
            $values[$field->key] = $this->value($field, $postId);
        }
        return $values;
    }

    public function value(FieldDefinition $field, int $postId): mixed
    {
        $raw = get_post_meta($postId, $field->metaKey(), true);
        if ($raw === '' || $raw === null || $raw === false) {
            // get_post_meta devolve '' quando não existe; false é válido para bool guardado.
            if ($field->type === FieldType::Bool) {
                return $raw === false ? false : $field->type->emptyValue();
            }
            return $field->type->emptyValue();
        }

        return match ($field->type) {
            FieldType::Number, FieldType::Money => is_numeric($raw) ? (float) $raw : null,
            FieldType::Bool => (bool) $raw,
            FieldType::Image, FieldType::File => (int) $raw,
            FieldType::Gallery => is_array($raw) ? array_values(array_map('intval', $raw)) : [],
            FieldType::Multiselect, FieldType::ListOfText => is_array($raw) ? array_values(array_map('strval', $raw)) : [],
            FieldType::Geo => is_array($raw) ? $raw : [],
            default => is_scalar($raw) ? (string) $raw : '',
        };
    }
}
