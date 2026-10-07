<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Faixa de preço automática (taxonomia "auto") a partir do preço e de uma taxonomia de grupo.
 *
 * Permite filtrar por intervalo de preço com o Taxonomy Filter nativo do Elementor,
 * que só filtra taxonomias.
 *
 * Configuração em settings.price_bands do preset:
 *   field, flag (bool "sob consulta"), taxonomy (auto), group_taxonomy (ex.: finalidade),
 *   groups: [grupo => [label, per (ex.: "/mês"), limits: [int, ...]]]
 *
 * Os nomes usam a moeda do site: "Venda · Até 100 mil €" / "Venda · Até R$ 100 mil".
 *
 * @since 2.6.0
 */
final class PriceBandResolver
{
    public const CONSULT_SLUG = 'sob-consulta';

    /** @return array<string, mixed>|null */
    public static function config(CollectionPresetInterface $preset): ?array
    {
        $config = $preset->setting('price_bands');
        if (! is_array($config) || ! isset($config['field'], $config['taxonomy'], $config['groups']) || ! is_array($config['groups'])) {
            return null;
        }
        return $config;
    }

    /**
     * Todos os termos possíveis, pela ordem lógica (para semear a taxonomia).
     *
     * @return array<string, string> slug => nome
     */
    public static function allTerms(CollectionPresetInterface $preset, ?Currency $currency = null): array
    {
        $config = self::config($preset);
        if ($config === null) {
            return [];
        }
        $terms = [];
        foreach ($config['groups'] as $group => $groupConfig) {
            $limits = self::limits($groupConfig);
            $count = count($limits);
            for ($i = 0; $i <= $count; $i++) {
                [$slug, $name] = self::band((string) $group, $groupConfig, $limits, $i, $currency);
                $terms[$slug] = $name;
            }
        }
        if (! empty($config['flag'])) {
            $terms[self::CONSULT_SLUG] = 'Preço sob consulta';
        }
        return $terms;
    }

    /**
     * @return array{0: string, 1: string}|null [slug, nome] ou null se não houver faixa
     */
    public static function resolve(CollectionPresetInterface $preset, ?float $price, bool $onRequest, ?string $groupSlug, ?Currency $currency = null): ?array
    {
        $config = self::config($preset);
        if ($config === null) {
            return null;
        }
        if ($onRequest && ! empty($config['flag'])) {
            return [self::CONSULT_SLUG, 'Preço sob consulta'];
        }
        if ($price === null || $groupSlug === null || ! isset($config['groups'][$groupSlug])) {
            return null;
        }

        $groupConfig = $config['groups'][$groupSlug];
        $limits = self::limits($groupConfig);
        $index = count($limits);
        foreach ($limits as $i => $limit) {
            if ($price <= $limit) {
                $index = $i;
                break;
            }
        }
        return self::band($groupSlug, $groupConfig, $limits, $index, $currency);
    }

    /** @return list<int|float> */
    private static function limits(mixed $groupConfig): array
    {
        $limits = is_array($groupConfig) ? array_values(array_filter((array) ($groupConfig['limits'] ?? []), 'is_numeric')) : [];
        $limits = array_map(static fn ($v) => $v + 0, $limits);
        sort($limits);
        return $limits;
    }

    /**
     * @param array<string, mixed> $groupConfig
     * @param list<int|float> $limits
     * @return array{0: string, 1: string}
     */
    private static function band(string $group, array $groupConfig, array $limits, int $index, ?Currency $currency = null): array
    {
        $label = (string) ($groupConfig['label'] ?? ucfirst($group));
        $currency ??= Currency::of(Currency::DEFAULT);
        $per = (string) ($groupConfig['per'] ?? '');
        $money = static fn (string $amount): string => $currency->wrap($amount) . $per;
        $count = count($limits);

        if ($count === 0) {
            return [$group . '-todos', $label];
        }
        if ($index === 0) {
            return [
                sprintf('%s-ate-%s', $group, self::slugNum($limits[0])),
                sprintf('%s · Até %s', $label, $money(self::human($limits[0], $currency))),
            ];
        }
        if ($index >= $count) {
            return [
                sprintf('%s-mais-%s', $group, self::slugNum($limits[$count - 1])),
                sprintf('%s · Mais de %s', $label, $money(self::human($limits[$count - 1], $currency))),
            ];
        }
        $from = $limits[$index - 1];
        $to = $limits[$index];
        return [
            sprintf('%s-%s-%s', $group, self::slugNum($from), self::slugNum($to)),
            sprintf('%s · %s a %s', $label, self::rangeHuman($from, $to, $currency), $money(self::human($to, $currency))),
        ];
    }

    private static function slugNum(int|float $value): string
    {
        return (string) (int) round($value);
    }

    /** "100 mil", "1 milhão", "1,5 milhões"; abaixo de 10 000 fica o número, com separador de milhar da moeda. */
    public static function human(int|float $value, ?Currency $currency = null): string
    {
        if ($value >= 1000000) {
            $m = $value / 1000000;
            $text = rtrim(rtrim(number_format($m, 1, ',', ''), '0'), ',');
            return $text . ($m > 1 ? ' milhões' : ' milhão');
        }
        if ($value >= 10000) {
            $k = $value / 1000;
            return rtrim(rtrim(number_format($k, 1, ',', ''), '0'), ',') . ' mil';
        }
        return $currency !== null ? $currency->number((float) $value, 0) : (string) (int) round($value);
    }

    /**
     * No início de um intervalo omite a unidade quando é igual à do fim: "100 a 200 mil".
     * Moeda com símbolo antes do valor repete o símbolo: "R$ 3.000 a R$ 5.000/mês".
     */
    private static function rangeHuman(int|float $from, int|float $to, ?Currency $currency = null): string
    {
        if ($currency !== null && $currency->symbolBefore()) {
            return $currency->wrap(self::human($from, $currency));
        }
        $fromText = self::human($from, $currency);
        if ($from >= 10000 && $from < 1000000 && $to >= 10000 && $to < 1000000) {
            return str_replace(' mil', '', $fromText);
        }
        return $fromText;
    }
}
