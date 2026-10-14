<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;

/**
 * Regista as Abilities do FlashSite na Abilities API oficial do WordPress (6.9+).
 *
 * Não implementa MCP: o transporte, a descoberta e a autenticação são do plugin oficial
 * WordPress MCP Adapter, instalado à parte. O Core não precisa de saber se ele existe.
 *
 * Em WordPress anterior a 6.9 os hooks da API nunca disparam e este módulo não faz nada.
 * Não há polyfill.
 *
 * @since 3.5.0
 */
final class AbilitiesModule implements ModuleInterface
{
    public const CATEGORY_HOOK = 'wp_abilities_api_categories_init';
    public const ABILITIES_HOOK = 'wp_abilities_api_init';

    /** @param list<AbilityProvider> $providers */
    public function __construct(private array $providers, private LoggerInterface $logger) {}

    public function register(): void
    {
        add_action(self::CATEGORY_HOOK, [$this, 'registerCategory']);
        add_action(self::ABILITIES_HOOK, [$this, 'registerAbilities']);
    }

    public function boot(): void {}

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'abilities';
    }

    public function registerCategory(): void
    {
        if (! function_exists('wp_register_ability_category')) {
            return;
        }
        wp_register_ability_category(AbilityMeta::CATEGORY, [
            'label' => 'FlashSite',
            'description' => 'Ações estruturadas do FlashSite Core: coleções, itens e Dados do Negócio.',
        ]);
    }

    public function registerAbilities(): void
    {
        if (! function_exists('wp_register_ability')) {
            return;
        }
        foreach ($this->definitions() as $name => $args) {
            try {
                wp_register_ability($name, $args);
            } catch (\Throwable $e) {
                // Uma ability com problema nunca derruba o site nem as restantes.
                $this->logger->error('Ability FlashSite não registada.', ['ability' => $name, 'error' => $e->getMessage()]);
            }
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        $all = [];
        foreach ($this->providers as $provider) {
            $all += $provider->definitions();
        }
        return $all;
    }
}
