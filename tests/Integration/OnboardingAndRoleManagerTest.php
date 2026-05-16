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
    }
}
