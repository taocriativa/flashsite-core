<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Formata valores de itens para o site (pt-PT).
 *
 * Dinheiro na moeda do site (FlashSite › Coleções): "285.000,00 €", "R$ 285.000,00", "$285,000.00".
 * Sempre com cêntimos por defeito (decisão do Ted: formato 00,00); estilo "auto" omite os
 * cêntimos quando o valor é inteiro.
 *
 * Settings do preset usados:
 *   price_bands.flag           campo bool "sob consulta"
 *   price_suffix               ['taxonomy' => 'finalidade', 'terms' => ['arrendamento' => '/mês']]
 *
 * @since 2.6.0
 */
final class FieldFormatter
{
    public const MONEY_CENTS = 'cents';
    public const MONEY_AUTO = 'auto';

    public const ON_REQUEST_TEXT = 'Preço sob consulta';

    private const MONTHS = ['jan.', 'fev.', 'mar.', 'abr.', 'mai.', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];

    public function __construct(private ItemReader $reader, private ?CollectionSettings $settings = null) {}

    public static function money(float $value, string $style = self::MONEY_CENTS, ?Currency $currency = null): string
    {
        $decimals = ($style === self::MONEY_AUTO && abs($value - round($value)) < 0.005) ? 0 : 2;
        return ($currency ?? Currency::of(Currency::DEFAULT))->format($value, $decimals);
    }

    public function currency(): Currency
    {
        return $this->settings?->currency() ?? Currency::of(Currency::DEFAULT);
    }

    public static function number(float $value): string
    {
        $text = number_format($value, 2, ',', '.');
        return rtrim(rtrim($text, '0'), ',');
    }

    public static function date(string $isoDate): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $isoDate, $m) !== 1) {
            return $isoDate;
        }
        return sprintf('%d %s %s', (int) $m[3], self::MONTHS[(int) $m[2] - 1] ?? '', $m[1]);
    }

    /** Valor formatado de um campo para mostrar no site. Campos privados devolvem sempre ''. */
    public function field(CollectionPresetInterface $preset, FieldDefinition $field, int $postId, string $moneyStyle = self::MONEY_CENTS): string
    {
        if (! $field->public) {
            return '';
        }
        if ($field->type === FieldType::Money && $this->isPriceField($preset, $field)) {
            return $this->price($preset, $postId, $moneyStyle);
        }
        return $this->value($field, $this->reader->value($field, $postId), $moneyStyle);
    }

    public function value(FieldDefinition $field, mixed $value, string $moneyStyle = self::MONEY_CENTS): string
    {
        $unit = $field->unit !== '' && $field->type !== FieldType::Money ? ' ' . $field->unit : '';

        return match ($field->type) {
            FieldType::Money => is_float($value) ? self::money($value, $moneyStyle, $this->currency()) : '',
            FieldType::Number => is_float($value) ? self::number($value) . $unit : '',
            FieldType::Bool => $value === true ? 'Sim' : '',
            FieldType::Select => (string) ($field->options[(string) $value] ?? ''),
            FieldType::Multiselect => implode(', ', array_filter(array_map(static fn ($v) => $field->options[(string) $v] ?? '', (array) $value))),
            FieldType::Date => is_string($value) && $value !== '' ? self::date($value) : '',
            FieldType::ListOfText => implode(', ', array_map('strval', (array) $value)),
            FieldType::Geo => isset($value['lat'], $value['lng']) ? $value['lat'] . ', ' . $value['lng'] : '',
            FieldType::Image, FieldType::File => (int) $value > 0 ? (string) wp_get_attachment_url((int) $value) : '',
            FieldType::Gallery => (string) count((array) $value),
            default => is_scalar($value) ? (string) $value . ((string) $value !== '' ? $unit : '') : '',
        };
    }

    /** Preço com "sob consulta" e sufixo por termo (ex.: "/mês" no arrendamento). */
    public function price(CollectionPresetInterface $preset, int $postId, string $moneyStyle = self::MONEY_CENTS): string
    {
        $config = PriceBandResolver::config($preset);
        $priceKey = (string) ($config['field'] ?? 'preco');
        $flagKey = (string) ($config['flag'] ?? '');

        $flag = $flagKey !== '' ? $preset->field($flagKey) : null;
        if ($flag !== null && $this->reader->value($flag, $postId) === true) {
            return self::ON_REQUEST_TEXT;
        }

        $field = $preset->field($priceKey);
        if ($field === null) {
            return '';
        }
        $value = $this->reader->value($field, $postId);
        if (! is_float($value)) {
            return '';
        }
        return self::money($value, $moneyStyle, $this->currency()) . $this->priceSuffix($preset, $postId);
    }

    public function isOnRequest(CollectionPresetInterface $preset, int $postId): bool
    {
        $flagKey = (string) (PriceBandResolver::config($preset)['flag'] ?? '');
        $flag = $flagKey !== '' ? $preset->field($flagKey) : null;
        return $flag !== null && $this->reader->value($flag, $postId) === true;
    }

    /**
     * Nomes dos termos de uma taxonomia do preset. Hierárquicas: do mais específico para o geral
     * ("Alvalade, Lisboa").
     */
    public function terms(CollectionPresetInterface $preset, string $taxonomyKey, int $postId, string $separator = ', '): string
    {
        $taxonomy = null;
        foreach ($preset->taxonomies() as $candidate) {
            if ($candidate->key === $taxonomyKey) {
                $taxonomy = $candidate;
            }
        }
        if ($taxonomy === null) {
            return '';
        }
        $terms = wp_get_object_terms($postId, $taxonomy->taxonomyName($preset->postType()));
        if (! is_array($terms) || $terms === []) {
            return '';
        }
        if ($taxonomy->hierarchical) {
            usort($terms, static fn ($a, $b) => ((int) $b->parent > 0 ? 1 : 0) <=> ((int) $a->parent > 0 ? 1 : 0));
        }
        return implode($separator, array_map(static fn ($t) => (string) $t->name, $terms));
    }

    /** @return list<string> slugs dos termos de uma taxonomia do preset */
    public function termSlugs(CollectionPresetInterface $preset, string $taxonomyKey, int $postId): array
    {
        foreach ($preset->taxonomies() as $taxonomy) {
            if ($taxonomy->key === $taxonomyKey) {
                $slugs = wp_get_object_terms($postId, $taxonomy->taxonomyName($preset->postType()), ['fields' => 'slugs']);
                return is_array($slugs) ? array_values(array_map('strval', $slugs)) : [];
            }
        }
        return [];
    }

    private function priceSuffix(CollectionPresetInterface $preset, int $postId): string
    {
        $config = $preset->setting('price_suffix');
        if (! is_array($config) || ! isset($config['taxonomy'], $config['terms']) || ! is_array($config['terms'])) {
            return '';
        }
        foreach ($this->termSlugs($preset, (string) $config['taxonomy'], $postId) as $slug) {
            if (isset($config['terms'][$slug])) {
                return (string) $config['terms'][$slug];
            }
        }
        return '';
    }

    private function isPriceField(CollectionPresetInterface $preset, FieldDefinition $field): bool
    {
        $config = PriceBandResolver::config($preset);
        return $field->key === (string) ($config['field'] ?? 'preco');
    }
}
