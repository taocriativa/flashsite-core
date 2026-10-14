<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Ordenação declarativa por campo (settings.order e settings.order_past).
 *
 *   'order' => [
 *       ['by' => 'data_inicio', 'dir' => 'ASC', 'missing' => 'keep'],
 *       ['by' => '@title', 'dir' => 'ASC'],
 *   ],
 *   'order_past' => [['by' => 'data_inicio', 'dir' => 'DESC']],   // opcional: usada só com when=past
 *
 * - by: chave de um campo público (date, time, number, money, text, select) ou built-in "@date",
 *   "@title", "@menu_order". Campo privado nunca ordena (ordenar revelaria o valor).
 * - dir: ASC (padrão) ou DESC.
 * - missing: "keep" (padrão) mantém os itens sem valor, onde o MySQL os coloca (NULL primeiro em ASC e
 *   último em DESC); "hide" tira da lista os itens sem valor. Não há first/last: exigiria SQL próprio.
 *
 * Sem settings.order nada disto corre: as listas mantêm a ordenação de sempre. Nenhuma inversão
 * automática: o que está declarado é o que vale (past usa order_past se existir, senão order).
 *
 * @since 3.4.0
 */
final class OrderResolver
{
    public const BUILTINS = ['@date' => 'date', '@title' => 'title', '@menu_order' => 'menu_order'];

    /** @return list<array{by: string, dir: string, missing: string}> */
    public static function rules(CollectionPresetInterface $preset, string $setting = 'order'): array
    {
        $raw = $preset->setting($setting);
        if (! is_array($raw)) {
            return [];
        }
        $rules = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $rule = self::normalize($preset, (string) ($item['by'] ?? ''), (string) ($item['dir'] ?? 'ASC'), (string) ($item['missing'] ?? 'keep'));
            if ($rule !== null) {
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    /** Regras do contexto: com when=past usa order_past, se existir. */
    public static function rulesFor(CollectionPresetInterface $preset, ?string $when = null): array
    {
        if ($when === TemporalResolver::PAST) {
            $past = self::rules($preset, 'order_past');
            if ($past !== []) {
                return $past;
            }
        }
        return self::rules($preset);
    }

    /**
     * Regra única pedida por quem consulta (shortcode e REST): "field:<chave>", "@date", "@title", "@menu_order".
     *
     * @return list<array{by: string, dir: string, missing: string}>
     */
    public static function fromToken(CollectionPresetInterface $preset, string $token, string $dir = 'ASC'): array
    {
        $by = str_starts_with($token, 'field:') ? substr($token, 6) : $token;
        $rule = self::normalize($preset, $by, $dir, 'keep');
        return $rule === null ? [] : [$rule];
    }

    /** @return array{by: string, dir: string, missing: string}|null */
    private static function normalize(CollectionPresetInterface $preset, string $by, string $dir, string $missing): ?array
    {
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $missing = $missing === 'hide' ? 'hide' : 'keep';
        if (isset(self::BUILTINS[$by])) {
            return ['by' => $by, 'dir' => $dir, 'missing' => 'keep'];
        }
        $field = $preset->field($by);
        if ($field === null || ! $field->public || self::metaType($field->type) === null) {
            return null;
        }
        return ['by' => $by, 'dir' => $dir, 'missing' => $missing];
    }

    private static function metaType(FieldType $type): ?string
    {
        return match ($type) {
            FieldType::Date => 'DATE',
            FieldType::Time => 'TIME',
            FieldType::Number, FieldType::Money => 'NUMERIC',
            FieldType::Text, FieldType::Select => 'CHAR',
            default => null,
        };
    }

    /**
     * Pedaço de argumentos do WP_Query: meta_query (cláusulas nomeadas) e orderby.
     * Desempate final: menu_order ASC, data DESC (a ordem de sempre das coleções).
     *
     * @param list<array{by: string, dir: string, missing: string}> $rules
     * @return array{meta_query: list<array<int|string, mixed>>, orderby: array<string, string>}
     */
    public static function fragment(CollectionPresetInterface $preset, array $rules): array
    {
        $metaQuery = [];
        $orderby = [];
        foreach ($rules as $index => $rule) {
            if (isset(self::BUILTINS[$rule['by']])) {
                $orderby[self::BUILTINS[$rule['by']]] ??= $rule['dir'];
                continue;
            }
            $field = $preset->field($rule['by']);
            $type = $field !== null ? self::metaType($field->type) : null;
            if ($field === null || $type === null) {
                continue;
            }
            $name = 'fs_ord_' . $index . '_' . $field->key;
            $exists = ['key' => $field->metaKey(), 'compare' => 'EXISTS', 'type' => $type];
            $metaQuery[] = $rule['missing'] === 'hide'
                ? [$name => $exists]
                : ['relation' => 'OR', $name => $exists, ['key' => $field->metaKey(), 'compare' => 'NOT EXISTS']];
            $orderby[$name] = $rule['dir'];
        }
        $orderby['menu_order'] ??= 'ASC';
        $orderby['date'] ??= 'DESC';
        return ['meta_query' => $metaQuery, 'orderby' => $orderby];
    }
}
