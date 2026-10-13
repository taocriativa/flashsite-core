<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;

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
 * O nome que se grava (termos) é sempre o português, sem depender do idioma da página ou do painel.
 * Para o visitante, localizedName() refaz o nome no idioma dele a partir do slug (@since 3.3.0-beta.5):
 * EN "For sale · Up to €100k", FR "Vente · Jusqu'à 100 k€", ES "Venta · Hasta 100 mil €".
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
                [$slug, $name] = self::band((string) $group, $groupConfig, $limits, $i, $currency, Language::PT_PT);
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
    public static function resolve(CollectionPresetInterface $preset, ?float $price, bool $onRequest, ?string $groupSlug, ?Currency $currency = null, ?string $language = null): ?array
    {
        $config = self::config($preset);
        if ($config === null) {
            return null;
        }
        $language ??= Language::PT_PT;
        if ($onRequest && ! empty($config['flag'])) {
            return [self::CONSULT_SLUG, Strings::t('Preço sob consulta', $language)];
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
        return self::band($groupSlug, $groupConfig, $limits, $index, $currency, $language);
    }

    /**
     * Nome da faixa no idioma do visitante, refeito a partir do slug (o termo gravado não muda).
     * Null se o slug não é de uma faixa deste preset.
     */
    public static function localizedName(CollectionPresetInterface $preset, string $slug, ?Currency $currency, string $language): ?string
    {
        $config = self::config($preset);
        if ($config === null) {
            return null;
        }
        if ($slug === self::CONSULT_SLUG) {
            return ! empty($config['flag']) ? Strings::t('Preço sob consulta', $language) : null;
        }
        foreach ($config['groups'] as $group => $groupConfig) {
            $limits = self::limits($groupConfig);
            for ($i = 0, $count = count($limits); $i <= $count; $i++) {
                if (self::band((string) $group, $groupConfig, $limits, $i, $currency, Language::PT_PT)[0] === $slug) {
                    return self::band((string) $group, $groupConfig, $limits, $i, $currency, $language)[1];
                }
            }
        }
        return null;
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
    private static function band(string $group, array $groupConfig, array $limits, int $index, ?Currency $currency = null, string $language = Language::PT_PT): array
    {
        $label = Strings::t((string) ($groupConfig['label'] ?? ucfirst($group)), $language);
        $currency ??= Currency::of(Currency::DEFAULT);
        $per = Strings::t((string) ($groupConfig['per'] ?? ''), $language);
        $money = static fn (int|float $value): string => self::money($value, $currency, $language) . $per;
        $count = count($limits);

        if ($count === 0) {
            return [$group . '-todos', $label];
        }
        if ($index === 0) {
            return [
                sprintf('%s-ate-%s', $group, self::slugNum($limits[0])),
                sprintf('%s · %s %s', $label, Strings::t('Até', $language), $money($limits[0])),
            ];
        }
        if ($index >= $count) {
            return [
                sprintf('%s-mais-%s', $group, self::slugNum($limits[$count - 1])),
                sprintf('%s · %s %s', $label, Strings::t('Mais de', $language), $money($limits[$count - 1])),
            ];
        }
        $from = $limits[$index - 1];
        $to = $limits[$index];
        return [
            sprintf('%s-%s-%s', $group, self::slugNum($from), self::slugNum($to)),
            sprintf('%s · %s%s%s', $label, self::rangeHuman($from, $to, $currency, $language), Strings::t(' a ', $language), $money($to)),
        ];
    }

    private static function slugNum(int|float $value): string
    {
        return (string) (int) round($value);
    }

    /**
     * Unidades e decimal de cada idioma. PT "100 mil", "1 milhão", "1,5 milhões"; ES "100 mil", "1 millón",
     * "2 millones"; EN "100k", "1.5M"; FR "100 k", "1,5 M" (com o símbolo colado: "100 k€").
     *
     * @return array{k: string, one: string, many: string, decimal: string, glue: bool}
     */
    private static function units(string $language): array
    {
        return match (true) {
            $language === Language::EN => ['k' => 'k', 'one' => 'M', 'many' => 'M', 'decimal' => '.', 'glue' => false],
            $language === Language::FR => ['k' => ' k', 'one' => ' M', 'many' => ' M', 'decimal' => ',', 'glue' => true],
            $language === Language::ES => ['k' => ' mil', 'one' => ' millón', 'many' => ' millones', 'decimal' => ',', 'glue' => false],
            default => ['k' => ' mil', 'one' => ' milhão', 'many' => ' milhões', 'decimal' => ',', 'glue' => false],
        };
    }

    private static function decimalText(float $value, string $decimal): string
    {
        return rtrim(rtrim(number_format($value, 1, $decimal, ''), '0'), $decimal);
    }

    /** "100 mil", "1 milhão", "1,5 milhões"; abaixo de 10 000 fica o número, com separador de milhar da moeda. */
    public static function human(int|float $value, ?Currency $currency = null, string $language = Language::PT_PT): string
    {
        $u = self::units($language);
        if ($value >= 1000000) {
            $m = $value / 1000000;
            return self::decimalText($m, $u['decimal']) . ($m > 1 ? $u['many'] : $u['one']);
        }
        if ($value >= 10000) {
            return self::decimalText($value / 1000, $u['decimal']) . $u['k'];
        }
        return $currency !== null ? $currency->displayNumber((float) $value, 0, $language) : (string) (int) round($value);
    }

    /** Valor com o símbolo da moeda; em francês, com unidade, o símbolo vai colado ("100 k€"). */
    private static function money(int|float $value, Currency $currency, string $language): string
    {
        $text = self::human($value, $currency, $language);
        if ($value >= 10000 && self::units($language)['glue'] && ! $currency->symbolBeforeFor($language)) {
            return $text . $currency->symbol();
        }
        return $currency->wrap($text, $language);
    }

    /**
     * No início de um intervalo omite a unidade quando é igual à do fim: "100 a 200 mil".
     * Moeda com símbolo antes do valor repete o símbolo: "R$ 3.000 a R$ 5.000/mês", "€100k to €200k".
     */
    private static function rangeHuman(int|float $from, int|float $to, Currency $currency, string $language): string
    {
        if ($currency->symbolBeforeFor($language)) {
            return self::money($from, $currency, $language);
        }
        $fromText = self::human($from, $currency, $language);
        if ($from >= 10000 && $from < 1000000 && $to >= 10000 && $to < 1000000) {
            return str_replace(self::units($language)['k'], '', $fromText);
        }
        return $fromText;
    }
}
