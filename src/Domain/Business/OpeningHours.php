<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;

/**
 * Texto completo dos horários, no idioma pedido (@since 3.3.0-beta.6).
 *
 * O idioma entra sempre como parâmetro (português por defeito): nada aqui lê o idioma da página.
 * Os valores de cada dia e a observação passam pelo $resolve do chamador, que é o
 * BusinessTranslations::resolver() e traz a regra de fallback para o idioma principal.
 * As abreviações dos dias e os conectivos vêm dos pacotes de idioma (config/i18n).
 *
 * Duas apresentações, as que já existiam:
 * - agrupado (Elementor "Horários (Texto Completo)", API): "Seg a Sex: 9h–18h\nSáb e Dom: 10h–13h";
 *   dois dias só se juntam se o texto final, já no idioma, for igual;
 * - um dia por linha (shortcode [flashsite_hours] e business.hours.formatted): "Seg: 9h–18h\nTer: …".
 */
final class OpeningHours
{
    /** @var array<string, string> chave do dia => abreviação de origem (português) */
    private const DAYS = [
        'monday' => 'Seg',
        'tuesday' => 'Ter',
        'wednesday' => 'Qua',
        'thursday' => 'Qui',
        'friday' => 'Sex',
        'saturday' => 'Sáb',
        'sunday' => 'Dom',
    ];

    /**
     * @param array<string, mixed> $hours monday…sunday, notes, legacy_text
     * @param (callable(string, string): string)|null $resolve (caminho "hours.monday", original) => valor no idioma
     */
    public static function format(array $hours, string $language = Language::PT_PT, ?callable $resolve = null, bool $grouped = true): string
    {
        $resolve ??= static fn (string $path, string $original): string => $original;

        $days = [];
        foreach (self::DAYS as $key => $abbreviation) {
            $days[Strings::t($abbreviation, $language)] = trim((string) $resolve('hours.' . $key, (string) ($hours[$key] ?? '')));
        }
        $lines = $grouped ? self::groupedLines($days, $language) : self::dailyLines($days);

        $notes = (string) $resolve('hours.notes', (string) ($hours['notes'] ?? ''));
        if ($grouped ? $notes !== '' : trim($notes) !== '') {
            $lines[] = $grouped ? $notes : trim($notes);
        }

        if ($lines === []) {
            $legacy = (string) ($hours['legacy_text'] ?? '');
            return $grouped ? ($legacy !== '' ? $legacy : '') : trim($legacy);
        }
        return implode("\n", $lines);
    }

    /**
     * @param array<string, string> $days etiqueta => valor
     * @return list<string>
     */
    private static function groupedLines(array $days, string $language): array
    {
        $groups = [];
        foreach ($days as $label => $value) {
            $last = count($groups) - 1;
            if ($value !== '' && $last >= 0 && $groups[$last]['value'] === $value && $groups[$last]['open']) {
                $groups[$last]['days'][] = $label;
                continue;
            }
            $groups[] = ['days' => [$label], 'value' => $value, 'open' => $value !== ''];
        }
        $lines = [];
        foreach ($groups as $group) {
            if (! $group['open']) {
                continue;
            }
            $d = $group['days'];
            $label = match (count($d)) {
                1 => $d[0],
                2 => $d[0] . Strings::t(' e ', $language) . $d[1],
                default => $d[0] . Strings::t(' a ', $language) . $d[count($d) - 1],
            };
            $lines[] = $label . ': ' . $group['value'];
        }
        return $lines;
    }

    /**
     * @param array<string, string> $days etiqueta => valor
     * @return list<string>
     */
    private static function dailyLines(array $days): array
    {
        $lines = [];
        foreach ($days as $label => $value) {
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }
        return $lines;
    }
}
