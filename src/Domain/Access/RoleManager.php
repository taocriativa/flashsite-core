<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Access;

final class RoleManager
{
    private const ROLE_KEY = 'flashsite_site_manager';

    /** Nome visível único: "Cliente" pertence ao papel `customer` do WooCommerce. */
    public const ROLE_LABEL = 'Gestor Flash Site';

    public function __construct(private string $capabilitiesConfigPath) {}

    public function ensureRole(): void
    {
        $capabilities = $this->loadCapabilities();
        $role = get_role(self::ROLE_KEY);

        if ($role === null) {
            add_role(self::ROLE_KEY, self::ROLE_LABEL, $this->buildRoleCapabilities($capabilities));
            $role = get_role(self::ROLE_KEY);
        }

        if ($role !== null) {
            foreach ($capabilities as $capability) {
                $role->add_cap($capability);
            }
        }

        $this->syncRoleLabel();
        $this->ensureAdministratorCapabilities($capabilities);
    }

    /**
     * Instalações existentes guardam o nome antigo ("Gestor de Site" ou "Cliente") na opção de papéis.
     * Só o nome visível muda: chave, capabilities e utilizadores ficam intactos. Não grava se já estiver certo.
     */
    public function syncRoleLabel(): void
    {
        $roles = function_exists('wp_roles') ? wp_roles() : null;
        if (! is_object($roles) || ! isset($roles->roles[self::ROLE_KEY]) || ($roles->roles[self::ROLE_KEY]['name'] ?? '') === self::ROLE_LABEL) {
            return;
        }
        $roles->roles[self::ROLE_KEY]['name'] = self::ROLE_LABEL;
        $roles->role_names[self::ROLE_KEY] = self::ROLE_LABEL;
        if ($roles->use_db) {
            update_option($roles->role_key, $roles->roles);
        }
    }

    public function ensureAdministratorCapabilities(?array $capabilities = null): void
    {
        $capabilities ??= $this->loadCapabilities();
        $administrator = get_role('administrator');

        if ($administrator === null) {
            return;
        }

        foreach ($capabilities as $capability) {
            $administrator->add_cap($capability);
        }
    }

    private function loadCapabilities(): array
    {
        $capabilities = require $this->capabilitiesConfigPath;
        return is_array($capabilities) ? array_values(array_filter($capabilities, 'is_string')) : [];
    }

    private function buildRoleCapabilities(array $capabilities): array
    {
        $base = ['read' => true];
        foreach ($capabilities as $capability) {
            $base[$capability] = true;
        }
        return $base;
    }
}
