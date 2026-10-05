<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * settings.hide_field: um campo bool que, ligado, esconde o item do site sem o apagar
 * (ex.: prato "Esgotado hoje"). Usado na listagem, nas tags de lista e no Loop Grid.
 *
 * @since 2.6.0
 */
final class Visibility
{
    /** @return array<int|string, mixed>|null meta_query que exclui os itens escondidos */
    public static function metaQuery(CollectionPresetInterface $preset): ?array
    {
        $key = $preset->setting('hide_field');
        $field = is_string($key) ? $preset->field($key) : null;
        if ($field === null || $field->type !== FieldType::Bool) {
            return null;
        }
        return [
            'relation' => 'OR',
            ['key' => $field->metaKey(), 'compare' => 'NOT EXISTS'],
            ['key' => $field->metaKey(), 'value' => '1', 'compare' => '!='],
        ];
    }

    /** Junta a regra de visibilidade a um meta_query existente. */
    public static function apply(CollectionPresetInterface $preset, mixed $metaQuery): array
    {
        $metaQuery = is_array($metaQuery) ? $metaQuery : [];
        $hidden = self::metaQuery($preset);
        if ($hidden !== null) {
            $metaQuery[] = $hidden;
        }
        return $metaQuery;
    }
}
