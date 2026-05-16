<?php
declare(strict_types=1);

namespace FlashSite\Core\Infrastructure\Storage;

final class OptionsStorage
{
    public function exists(string $optionKey): bool
    {
        return get_option($optionKey, '__flashsite_missing__') !== '__flashsite_missing__';
    }

    public function get(string $optionKey, mixed $default = null): mixed
    {
        return get_option($optionKey, $default);
    }

    public function update(string $optionKey, mixed $value, bool $autoload = false): bool
    {
        return update_option($optionKey, $value, $autoload);
    }

    public function delete(string $optionKey): bool
    {
        return delete_option($optionKey);
    }
}
