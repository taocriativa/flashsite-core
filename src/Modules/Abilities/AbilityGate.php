<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;

/**
 * Permissões das Abilities. Só capabilities que o Core já define; nada de manage_options.
 * Utilizador não autenticado nunca tem estas capabilities.
 *
 * @since 3.5.0
 */
final class AbilityGate
{
    public const MANAGE_BUSINESS = 'flashsite_manage_business_data';

    public function __construct(private CollectionRegistry $registry) {}

    public function manageCollections(): bool
    {
        return $this->can(CollectionCapabilities::MANAGE_CAP);
    }

    public function manageBusiness(): bool
    {
        return $this->can(self::MANAGE_BUSINESS);
    }

    /** Pode ver o contexto do site FlashSite: gere coleções, dados do negócio ou edita itens de alguma coleção. */
    public function anyFlashSite(): bool
    {
        if ($this->manageCollections() || $this->manageBusiness()) {
            return true;
        }
        foreach ($this->registry->all() as $preset) {
            if ($this->can('edit_' . $preset->capabilityPlural())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gate grosso das Abilities de itens. A decisão por item (edit_post, delete_post, publicar)
     * é refeita no CollectionItemService com o ID real.
     */
    public function items(mixed $input): bool
    {
        $key = is_array($input) ? (string) ($input['collection'] ?? '') : '';
        $preset = $key !== '' ? $this->registry->get($key) : null;
        if ($preset === null) {
            // Coleção desconhecida: quem gere o site recebe o erro estruturado em vez de "proibido".
            return $this->anyFlashSite();
        }
        return $this->can('edit_' . $preset->capabilityPlural());
    }

    private function can(string $capability): bool
    {
        return function_exists('current_user_can') && current_user_can($capability);
    }
}
