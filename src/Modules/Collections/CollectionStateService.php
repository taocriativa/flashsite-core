<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionRegistry;

/**
 * Único fluxo de ligar/desligar Coleções (painel FlashSite › Coleções e Abilities).
 *
 * Gravar a opção não basta: o efeito completo (capabilities, termos fixos e regras de endereço)
 * acontece quando a ação "flashsite_collections_changed" corre (CollectionsModule::onActivationChanged).
 * Desativar nunca apaga itens.
 *
 * @since 3.5.0
 */
final class CollectionStateService
{
    public const CHANGED_ACTION = 'flashsite_collections_changed';

    public function __construct(private CollectionRegistry $registry, private ActivationRepository $activation) {}

    /**
     * @return array{key: string, active: bool, changed: bool}|null null quando a chave não existe no Registry
     */
    public function activate(string $key): ?array
    {
        return $this->set($key, true);
    }

    /** @return array{key: string, active: bool, changed: bool}|null */
    public function deactivate(string $key): ?array
    {
        return $this->set($key, false);
    }

    /**
     * Substitui o conjunto ativo (formulário do painel). A ação de mudança é disparada por quem chama,
     * depois de gravar também o país, a moeda e os idiomas.
     *
     * @param list<string> $keys
     */
    public function replace(array $keys): bool
    {
        return $this->activation->replace($keys, $this->registry->keys());
    }

    public static function notifyChanged(): void
    {
        do_action(self::CHANGED_ACTION);
    }

    /** @return array{key: string, active: bool, changed: bool}|null */
    private function set(string $key, bool $active): ?array
    {
        if (! $this->registry->has($key)) {
            return null;
        }
        $changed = $this->activation->isActive($key) !== $active;
        if ($changed) {
            $saved = $active ? $this->activation->activate($key) : $this->activation->deactivate($key);
            if (! $saved) {
                return null;
            }
            self::notifyChanged();
        }
        return ['key' => $key, 'active' => $this->activation->isActive($key), 'changed' => $changed];
    }
}
