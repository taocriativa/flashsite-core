<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Access\RoleManager;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingState;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class OnboardingAndRoleManagerTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $repo = new OnboardingRepository(new OptionsStorage());

        $state = new OnboardingState([
            'started' => true,
            'completed' => false,
            'current_step' => 'business',
            'steps' => ['welcome' => true, 'business' => false],
        ]);

        $this->assertTrue($repo->saveState($state));
        $saved = $repo->getState()->toArray();
        $this->assertTrue($saved['started']);
        $this->assertSame('business', $saved['current_step']);

        $this->assertTrue($repo->reset());
        $reset = $repo->getState()->toArray();
        $this->assertFalse($reset['started']);
        $this->assertSame('welcome', $reset['current_step']);

        $manager = new RoleManager(dirname(__DIR__, 2) . '/config/capabilities.php');
        $manager->ensureRole();

        $siteManager = get_role('flashsite_site_manager');
        $admin = get_role('administrator');
        $this->assertTrue($siteManager instanceof FlashSiteTestRole);
        $this->assertTrue($admin instanceof FlashSiteTestRole);
        $this->assertArrayHasKey('flashsite_manage_business_data', $siteManager->caps);
        $this->assertArrayHasKey('flashsite_run_setup_wizard', $admin->caps);
        $this->roleLabel();
    }

    /** 3.3.0-beta.2: nome visível único, independente do papel `customer` do WooCommerce. */
    private function roleLabel(): void
    {
        $path = dirname(__DIR__, 2) . '/config/capabilities.php';
        $key = 'flashsite_site_manager';

        // Instalação nova.
        flashsite_reset_test_state();
        add_role('customer', 'Cliente', ['read' => true]);
        (new RoleManager($path))->ensureRole();
        $this->assertSame('Gestor Flash Site', wp_roles()->roles[$key]['name']);
        $this->assertSame('Gestor Flash Site', wp_roles()->role_names[$key]);

        // Instalações existentes: nome antigo "Gestor de Site" e nome intermédio "Cliente".
        foreach (['Gestor de Site', 'Cliente'] as $legacy) {
            flashsite_reset_test_state();
            add_role('customer', 'Cliente', ['read' => true]);
            add_role($key, $legacy, ['read' => true, 'flashsite_view_dashboard' => true, 'upload_files' => true]);
            $before = get_role($key)->caps;
            $this->assertSame($legacy, wp_roles()->roles[$key]['name']);

            (new RoleManager($path))->ensureRole();

            $this->assertSame('Gestor Flash Site', wp_roles()->roles[$key]['name'], 'Nome antigo ' . $legacy . ' deve ser atualizado.');
            $this->assertSame('Gestor Flash Site', wp_roles()->role_names[$key]);
            $this->assertSame('Gestor Flash Site', $GLOBALS['flashsite_test_options']['wp_user_roles'][$key]['name']);
            foreach (array_keys($before) as $cap) {
                $this->assertArrayHasKey($cap, get_role($key)->caps, 'Capability ' . $cap . ' não pode ser perdida.');
            }

            // Idempotente: segunda execução não grava de novo.
            unset($GLOBALS['flashsite_test_options']['wp_user_roles']);
            (new RoleManager($path))->ensureRole();
            $this->assertFalse(array_key_exists('wp_user_roles', $GLOBALS['flashsite_test_options']), 'Sem alteração de nome, nada deve ser gravado.');

            // O papel do WooCommerce não é tocado e não ganha capabilities do Core.
            $this->assertSame('Cliente', wp_roles()->roles['customer']['name']);
            $this->assertSame(['read' => true], get_role('customer')->caps);
        }

        // Nunca dois papéis com o mesmo nome visível.
        $names = array_map(static fn (array $r): string => $r['name'], wp_roles()->roles);
        $this->assertSame(count($names), count(array_unique($names)), 'Dois papéis com o mesmo nome visível.');
    }
}
