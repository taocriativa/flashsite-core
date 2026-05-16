<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Access;

final class RoleManager
{
    private const ROLE_KEY = 'flashsite_site_manager';

    public function __construct(private string $capabilitiesConfigPath) {}

    public function ensureRole(): void
    {
        $capabilities = $this->loadCapabilities();
        $role = get_role(self::ROLE_KEY);

        if ($role === null) {
            add_role(self::ROLE_KEY, 'Gestor de Site', $this->buildRoleCapabilities($capabilities));
            $role = get_role(self::ROLE_KEY);
        }

        if ($role !== null) {
            foreach ($capabilities as $capability) {
                $role->add_cap($capability);
            }
        }

        $this->ensureAdministratorCapabilities($capabilities);
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
