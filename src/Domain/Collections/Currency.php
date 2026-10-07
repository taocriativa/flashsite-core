<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Moedas suportadas nos preços das Coleções, com a convenção de escrita de cada uma.
 *
 * EUR "285.000,00 €" · BRL "R$ 285.000,00" · USD "$285,000.00" · GBP "£285,000.00" ·
 * CHF "CHF 285'000.00" · AOA "285.000,00 Kz" · MZN "285.000,00 MT" · CVE "285.000,00 CVE"
 *
 * @since 2.6.0
 */
final class Currency
{
    public const DEFAULT = 'EUR';

    /** @var array<string, array{label: string, symbol: string, before: bool, space: bool, thousands: string, decimal: string}> */
    private const DEFINITIONS = [
        'EUR' => ['label' => 'Euro (€)', 'symbol' => '€', 'before' => false, 'space' => true, 'thousands' => '.', 'decimal' => ','],
        'BRL' => ['label' => 'Real brasileiro (R$)', 'symbol' => 'R$', 'before' => true, 'space' => true, 'thousands' => '.', 'decimal' => ','],
        'USD' => ['label' => 'Dólar americano ($)', 'symbol' => '$', 'before' => true, 'space' => false, 'thousands' => ',', 'decimal' => '.'],
        'GBP' => ['label' => 'Libra esterlina (£)', 'symbol' => '£', 'before' => true, 'space' => false, 'thousands' => ',', 'decimal' => '.'],
        'CHF' => ['label' => 'Franco suíço (CHF)', 'symbol' => 'CHF', 'before' => true, 'space' => true, 'thousands' => "'", 'decimal' => '.'],
        'AOA' => ['label' => 'Kwanza (Kz)', 'symbol' => 'Kz', 'before' => false, 'space' => true, 'thousands' => '.', 'decimal' => ','],
        'MZN' => ['label' => 'Metical (MT)', 'symbol' => 'MT', 'before' => false, 'space' => true, 'thousands' => '.', 'decimal' => ','],
        'CVE' => ['label' => 'Escudo cabo-verdiano (CVE)', 'symbol' => 'CVE', 'before' => false, 'space' => true, 'thousands' => '.', 'decimal' => ','],
    ];

    private function __construct(public readonly string $code) {}

    public static function of(string $code): self
    {
        $code = strtoupper(trim($code));
        return new self(isset(self::DEFINITIONS[$code]) ? $code : self::DEFAULT);
    }

    public static function isValid(string $code): bool
    {
        return isset(self::DEFINITIONS[strtoupper(trim($code))]);
    }

    /** @return array<string, string> código => label */
    public static function options(): array
    {
        return array_map(static fn (array $d): string => $d['label'], self::DEFINITIONS);
    }

    public function symbol(): string
    {
        return self::DEFINITIONS[$this->code]['symbol'];
    }

    public function format(float $value, int $decimals): string
    {
        return $this->wrap($this->number($value, $decimals));
    }

    /** Só o número, com os separadores desta moeda (sem símbolo). */
    public function number(float $value, int $decimals = 2): string
    {
        $d = self::DEFINITIONS[$this->code];
        return number_format($value, $decimals, $d['decimal'], $d['thousands']);
    }

    /** Símbolo antes do valor ("R$ 100") ou depois ("100 €"). */
    public function symbolBefore(): bool
    {
        return self::DEFINITIONS[$this->code]['before'];
    }

    /** Coloca o símbolo no sítio certo de um texto já formatado ("100 mil" → "100 mil €" / "R$ 100 mil"). */
    public function wrap(string $amount): string
    {
        $d = self::DEFINITIONS[$this->code];
        $gap = $d['space'] ? ' ' : '';
        return $d['before'] ? $d['symbol'] . $gap . $amount : $amount . $gap . $d['symbol'];
    }

    /** Separadores do número para este código (usado também no input do painel). */
    public function decimalSeparator(): string
    {
        return self::DEFINITIONS[$this->code]['decimal'];
    }
}
