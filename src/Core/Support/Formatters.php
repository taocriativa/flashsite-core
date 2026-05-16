<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Support;

final class Formatters
{
    public static function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function phoneHref(string $phone): string
    {
        $digits = self::phoneDigits($phone);
        return $digits === '' ? '' : 'tel:' . $digits;
    }
}
