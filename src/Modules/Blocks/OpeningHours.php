<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

use FlashSite\Core\Domain\Business\BusinessData;

/**
 * Lê o horário em texto dos Dados do Negócio ("12h30–15h e 19h30–23h", "Encerrado") e devolve
 * intervalos em minutos por dia da semana. Se algum dia não for legível, devolve null.
 */
final class OpeningHours
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /** @return array<string, list<array{0:int,1:int}>>|null */
    public static function fromBusinessData(): ?array
    {
        $business = BlocksModule::service(BusinessData::class);
        if (! $business instanceof BusinessData) {
            return null;
        }
        $texts = [];
        foreach (self::DAYS as $day) {
            $texts[$day] = (string) $business->get('hours.' . $day, '');
        }
        return self::parseWeek($texts);
    }

    /**
     * @param array<string, string> $texts
     * @return array<string, list<array{0:int,1:int}>>|null
     */
    public static function parseWeek(array $texts): ?array
    {
        $week = [];
        $any = false;
        foreach (self::DAYS as $day) {
            $ranges = self::parseDay((string) ($texts[$day] ?? ''));
            if ($ranges === null) {
                return null;
            }
            $week[$day] = $ranges;
            $any = $any || $ranges !== [];
        }
        return $any ? $week : null;
    }

    /** @return list<array{0:int,1:int}>|null [] = fechado; null = não legível */
    public static function parseDay(string $text): ?array
    {
        $t = trim(mb_strtolower($text));
        if ($t === '' || preg_match('/^(encerrado|fechado|closed|folga)\.?$/u', $t)) {
            return [];
        }
        if (! preg_match_all('/(\d{1,2})(?:[h:](\d{2})?)\s*(?:–|-|—|às|a|até)\s*(\d{1,2})(?:[h:](\d{2})?)?/u', $t, $m, PREG_SET_ORDER)) {
            return null;
        }
        $ranges = [];
        foreach ($m as $r) {
            $from = (int) $r[1] * 60 + (int) ($r[2] ?? 0);
            $to = (int) $r[3] * 60 + (int) ($r[4] ?? 0);
            if ($from > 24 * 60 || $to > 24 * 60) {
                return null;
            }
            if ($to <= $from) {
                $to += 24 * 60; // fecha depois da meia-noite
            }
            $ranges[] = [$from, $to];
        }
        // Sobra texto com números que não foi lido (ex.: "almoço 12h30"): melhor não arriscar.
        $rest = preg_replace('/(\d{1,2})(?:[h:](\d{2})?)\s*(?:–|-|—|às|a|até)\s*(\d{1,2})(?:[h:](\d{2})?)?/u', '', $t) ?? '';
        if (preg_match('/\d/', $rest)) {
            return null;
        }
        return $ranges;
    }
}
