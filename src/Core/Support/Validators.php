<?php
declare(strict_types=1);

namespace FlashSite\Core\Core\Support;

final class Validators
{
    public static function isHexColor(string $value): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }

    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
