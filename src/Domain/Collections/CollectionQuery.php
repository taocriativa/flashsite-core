<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Junta ordenação (OrderResolver) e janela temporal (TemporalResolver) aos argumentos de uma consulta.
 * É o único ponto usado pelo arquivo, pelo shortcode, pelos Query IDs do Loop Grid e pela REST.
 *
 * Com regras vazias e sem when, devolve os argumentos exatamente como vieram.
 *
 * @since 3.4.0
 */
final class CollectionQuery
{
    /**
     * @param array<string, mixed> $args argumentos do WP_Query
     * @param list<array{by: string, dir: string, missing: string}> $rules
     * @return array<string, mixed>
     */
    public static function apply(array $args, CollectionPresetInterface $preset, ?string $when, array $rules, ?string $today = null): array
    {
        $clauses = [];
        $temporal = TemporalResolver::clause($preset, $when, $today);
        if ($temporal !== null) {
            $clauses[] = $temporal;
        }
        if ($rules !== []) {
            $fragment = OrderResolver::fragment($preset, $rules);
            array_push($clauses, ...$fragment['meta_query']);
            $args['orderby'] = $fragment['orderby'];
            unset($args['order']);
        }
        if ($clauses === []) {
            return $args;
        }
        $args['meta_query'] = self::mergeMetaQuery(isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [], $clauses);
        return $args;
    }

    /**
     * Versão para um objeto WP_Query já criado (pre_get_posts e Query IDs do Elementor).
     *
     * @param object $query
     * @param list<array{by: string, dir: string, missing: string}> $rules
     */
    public static function applyToQuery($query, CollectionPresetInterface $preset, ?string $when, array $rules): void
    {
        if (! is_object($query) || ! method_exists($query, 'get') || ! method_exists($query, 'set')) {
            return;
        }
        $before = $query->get('meta_query');
        $before = is_array($before) ? $before : [];
        $args = self::apply(['meta_query' => $before], $preset, $when, $rules);
        if ($args['meta_query'] !== $before) {
            $query->set('meta_query', $args['meta_query']);
        }
        if (isset($args['orderby'])) {
            $query->set('orderby', $args['orderby']);
        }
    }

    /**
     * @param array<int|string, mixed> $existing
     * @param list<array<int|string, mixed>> $clauses
     * @return array<int|string, mixed>
     */
    private static function mergeMetaQuery(array $existing, array $clauses): array
    {
        if ($existing === []) {
            return count($clauses) === 1 ? $clauses : array_merge(['relation' => 'AND'], $clauses);
        }
        $relation = strtoupper((string) ($existing['relation'] ?? 'AND'));
        if ($relation === 'AND') {
            return array_merge($existing, $clauses);
        }
        return array_merge(['relation' => 'AND', $existing], $clauses);
    }
}
