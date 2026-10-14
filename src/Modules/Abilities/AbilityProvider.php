<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

/**
 * Grupo de Abilities do FlashSite. Cada provider devolve só definições (argumentos de
 * wp_register_ability); o registo é feito pelo AbilitiesModule.
 *
 * @since 3.5.0
 */
interface AbilityProvider
{
    /** @return array<string, array<string, mixed>> nome ("flashsite/...") => argumentos de wp_register_ability() */
    public function definitions(): array;
}
