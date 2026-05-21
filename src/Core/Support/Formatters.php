<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Support;

final class Formatters
{
    /**
     * Retorna apenas os dígitos do número — usado para hrefs tel: e wa.me/.
     */
    public static function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Sanitiza o número preservando caracteres de formatação comuns:
     * dígitos, +, espaço, parênteses, hífen e ponto.
     * Usado para salvar o campo de exibição sem destruir a formatação inserida.
     */
    public static function phoneSanitize(string $phone): string
    {
        return preg_replace('/[^0-9+\s().\-]/', '', trim($phone)) ?? '';
    }

    public static function phoneHref(string $phone): string
    {
        $digits = self::phoneDigits($phone);
        return $digits === '' ? '' : 'tel:' . $digits;
    }
}
