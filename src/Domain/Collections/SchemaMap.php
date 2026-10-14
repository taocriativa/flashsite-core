<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * settings.schema_map: mapeia propriedades Schema.org para campos do preset, sem código por preset.
 *
 *   'schema_map' => [
 *       'jobTitle' => 'cargo',                                  // campo público de texto, data, url, e-mail, tel ou número
 *       'eventStatus' => ['taxonomy' => 'estado', 'map' => [    // termo da taxonomia -> valor fixo
 *           'cancelado' => 'https://schema.org/EventCancelled',
 *       ]],
 *   ],
 *
 * Regras:
 * - Só valores simples (texto, data, número). Sem objetos aninhados, listas nem transformações:
 *   quem precisar disso fica fora do schema_map (kit do job).
 * - Só campos públicos. Campo privado, inexistente ou de tipo não suportado é ignorado em silêncio.
 * - Propriedades já presentes no JSON-LD (name, url, image, description, offers, @…) não se substituem.
 * - Valor vazio não gera propriedade.
 * - Sem schema_map o JSON-LD é exatamente o de sempre.
 *
 * @since 3.4.0
 */
final class SchemaMap
{
    /** Propriedades que o SchemaOrgBuilder já produz: nunca são substituídas. */
    private const RESERVED = ['name', 'url', 'image', 'description', 'offers', 'datePosted'];

    private const SCALAR_TYPES = [
        FieldType::Text, FieldType::Textarea, FieldType::Url, FieldType::Email,
        FieldType::Tel, FieldType::Date, FieldType::Time, FieldType::Number,
    ];

    /**
     * Entradas válidas, já resolvidas contra o preset.
     *
     * @return array<string, array{field: ?FieldDefinition, taxonomy: ?string, map: array<string, string>}>
     */
    public static function entries(CollectionPresetInterface $preset): array
    {
        $raw = $preset->setting('schema_map');
        if (! is_array($raw)) {
            return [];
        }
        $entries = [];
        foreach ($raw as $property => $source) {
            $property = (string) $property;
            if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $property) !== 1 || in_array($property, self::RESERVED, true)) {
                continue;
            }
            if (is_string($source)) {
                $field = $preset->field($source);
                if ($field !== null && $field->public && in_array($field->type, self::SCALAR_TYPES, true)) {
                    $entries[$property] = ['field' => $field, 'taxonomy' => null, 'map' => []];
                }
                continue;
            }
            if (is_array($source) && isset($source['taxonomy'], $source['map']) && is_array($source['map'])) {
                $taxonomy = (string) $source['taxonomy'];
                $known = false;
                foreach ($preset->taxonomies() as $candidate) {
                    $known = $known || $candidate->key === $taxonomy;
                }
                $map = [];
                foreach ($source['map'] as $slug => $value) {
                    if (is_scalar($value) && trim((string) $value) !== '') {
                        $map[(string) $slug] = (string) $value;
                    }
                }
                if ($known && $map !== []) {
                    $entries[$property] = ['field' => null, 'taxonomy' => $taxonomy, 'map' => $map];
                }
            }
        }
        return $entries;
    }

    /**
     * Acrescenta ao JSON-LD as propriedades mapeadas que tenham valor.
     *
     * @param array<string, mixed> $item item serializado (só campos públicos)
     * @param array<string, mixed> $data JSON-LD já montado
     * @return array<string, mixed>
     */
    public static function apply(CollectionPresetInterface $preset, array $item, array $data): array
    {
        foreach (self::entries($preset) as $property => $entry) {
            if (array_key_exists($property, $data)) {
                continue;
            }
            $value = $entry['field'] !== null
                ? self::fieldValue($entry['field'], $item['fields'][$entry['field']->key] ?? null)
                : self::termValue($entry['map'], $item['terms'][$entry['taxonomy']] ?? []);
            if ($value !== null) {
                $data[$property] = $value;
            }
        }
        return $data;
    }

    private static function fieldValue(FieldDefinition $field, mixed $value): string|int|float|null
    {
        if ($field->type === FieldType::Number) {
            return is_int($value) || is_float($value) ? $value : null;
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if ($field->type === FieldType::Date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        return $value;
    }

    /**
     * @param array<string, string> $map
     * @param mixed $terms lista de ['slug' => ..., 'name' => ...]
     */
    private static function termValue(array $map, mixed $terms): ?string
    {
        foreach (is_array($terms) ? $terms : [] as $term) {
            $slug = is_array($term) ? (string) ($term['slug'] ?? '') : '';
            if ($slug !== '' && isset($map[$slug])) {
                return $map[$slug];
            }
        }
        return null;
    }
}
