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

    /**
     * O update_option() devolve false quando o valor gravado já é igual ao novo: isso não é falha.
     * Só devolvemos false se, depois de reler, o valor persistido continuar diferente do desejado.
     */
    public function update(string $optionKey, mixed $value, bool $autoload = false): bool
    {
        if (update_option($optionKey, $value, $autoload)) {
            return true;
        }

        return maybe_serialize(get_option($optionKey, '__flashsite_missing__')) === maybe_serialize($value);
    }

    public function delete(string $optionKey): bool
    {
        return delete_option($optionKey);
    }
}
