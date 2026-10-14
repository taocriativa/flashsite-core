<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Primitiva temporal mínima (settings.temporal): decide se um item é "próximo", "passado" ou qualquer um.
 * Nada desaparece por padrão: sem pedido explícito de when, a lista traz tudo.
 *
 *   'temporal' => [
 *       'field' => 'data_inicio',     // campo date (obrigatório)
 *       'end_field' => 'data_fim',    // campo date (opcional): o item é "próximo" até ao fim
 *       'undated' => 'upcoming',      // upcoming (padrão) | past | none: onde entram itens sem data
 *       'archive_when' => 'upcoming', // when padrão do arquivo (all se omitido)
 *   ],
 *
 * when: upcoming | past | all (contrato técnico, independente de idioma).
 * - data de referência = end_field se o item o tiver, senão field;
 * - upcoming: referência >= hoje; past: referência < hoje; all: sem filtro;
 * - hoje = dia atual no fuso do WordPress (os campos date são dias de calendário, sem fuso);
 * - sem recorrência, reservas, ocorrências nem calendário.
 *
 * Só se aplica quando a consulta o pede (arquivo, shortcode, Query IDs, REST). Ao contrário de
 * settings.hide_field, não age em consultas que não o pediram.
 *
 * @since 3.4.0
 */
final class TemporalResolver
{
    public const UPCOMING = 'upcoming';
    public const PAST = 'past';
    public const ALL = 'all';
    private const UNDATED = ['upcoming', 'past', 'none'];

    /** @return array{field: FieldDefinition, end: ?FieldDefinition, undated: string, archive_when: string}|null */
    public static function config(CollectionPresetInterface $preset): ?array
    {
        $raw = $preset->setting('temporal');
        if (! is_array($raw)) {
            return null;
        }
        $field = $preset->field((string) ($raw['field'] ?? ''));
        if ($field === null || $field->type !== FieldType::Date || ! $field->public) {
            return null;
        }
        $end = isset($raw['end_field']) ? $preset->field((string) $raw['end_field']) : null;
        if ($end !== null && ($end->type !== FieldType::Date || ! $end->public)) {
            $end = null;
        }
        $undated = (string) ($raw['undated'] ?? 'upcoming');
        return [
            'field' => $field,
            'end' => $end,
            'undated' => in_array($undated, self::UNDATED, true) ? $undated : 'upcoming',
            'archive_when' => self::normalizeWhen($raw['archive_when'] ?? null) ?? self::ALL,
        ];
    }

    /** upcoming | past | all, ou null se o valor não for um deles. */
    public static function normalizeWhen(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';
        return in_array($value, [self::UPCOMING, self::PAST, self::ALL], true) ? $value : null;
    }

    /** Dia atual (AAAA-MM-DD) no fuso do WordPress. */
    public static function today(): string
    {
        if (function_exists('current_datetime')) {
            return current_datetime()->format('Y-m-d');
        }
        return gmdate('Y-m-d');
    }

    /**
     * Cláusula de meta_query para a janela pedida; null quando não há filtro (all, ou preset sem temporal).
     *
     * @return array<int|string, mixed>|null
     */
    public static function clause(CollectionPresetInterface $preset, ?string $when, ?string $today = null): ?array
    {
        $when = self::normalizeWhen($when);
        $config = self::config($preset);
        if ($config === null || $when === null || $when === self::ALL) {
            return null;
        }
        $today ??= self::today();
        $op = $when === self::UPCOMING ? '>=' : '<';
        $start = $config['field']->metaKey();
        $end = $config['end']?->metaKey();
        $dated = static fn (string $key): array => ['key' => $key, 'value' => $today, 'compare' => $op, 'type' => 'DATE'];
        $missing = static fn (string $key): array => ['key' => $key, 'compare' => 'NOT EXISTS'];
        $takesUndated = $config['undated'] === $when;

        $branches = [];
        if ($end === null) {
            $branches[] = $dated($start);
            if ($takesUndated) {
                $branches[] = $missing($start);
            }
        } else {
            $branches[] = $dated($end);
            $branches[] = ['relation' => 'AND', $missing($end), $dated($start)];
            if ($takesUndated) {
                $branches[] = ['relation' => 'AND', $missing($end), $missing($start)];
            }
        }
        return ['relation' => 'OR'] + $branches;
    }
}
