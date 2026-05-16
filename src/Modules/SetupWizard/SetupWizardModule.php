<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SetupWizard;

use FlashSite\Core\Core\Assets;
use FlashSite\Core\Core\InstallationGuard;
use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Core\PluginChecker;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessValidator;
use FlashSite\Core\Domain\Dependencies\DependencyRegistry;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Modules\SetupWizard\Steps\BusinessDataStep;
use FlashSite\Core\Modules\SetupWizard\Steps\DependenciesStep;
use FlashSite\Core\Modules\SetupWizard\Steps\SummaryStep;
use FlashSite\Core\Modules\SetupWizard\Steps\WelcomeStep;

final class SetupWizardModule implements ModuleInterface
{
    public function __construct(
        private BusinessRepository $businessRepository,
        private BusinessValidator $businessValidator,
        private OnboardingRepository $onboardingRepository,
        private DependencyRegistry $dependencyRegistry,
        private PluginChecker $pluginChecker,
        private Assets $assets,
        private LoggerInterface $logger
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerWizardPage']);
        add_action('admin_post_flashsite_save_wizard_step', [$this, 'handleWizardSave']);
        $this->assets->enqueueAdminForScreens(['flashsite-core_page_flashsite-setup-wizard'], true);
    }

    public function boot(): void {}
    public function isActive(): bool { return is_admin(); }
    public function getSlug(): string { return 'setup-wizard'; }

    public function registerWizardPage(): void
    {
        add_submenu_page('flashsite-core', 'Setup Wizard', 'Setup Wizard', 'flashsite_run_setup_wizard', 'flashsite-setup-wizard', [$this, 'renderWizardPage']);
    }

    public function renderWizardPage(): void
    {
        $state = $this->onboardingRepository->getState();
        $requestedStep = isset($_GET['step']) ? sanitize_key((string) $_GET['step']) : '';
        $steps = $this->buildSteps();
        $currentStep = isset($steps[$requestedStep]) ? $requestedStep : $state->getCurrentStep();
        $completedSteps = $state->getSteps();
        $businessProfile = $this->businessRepository->getProfile();
        $dependencyState = get_option('flashsite_dependency_state', []);
        $installationStatus = InstallationGuard::status();
        include FLASHSITE_CORE_PATH . 'templates/admin/wizard.php';
    }

    public function handleWizardSave(): void
    {
        if (! current_user_can('flashsite_run_setup_wizard') && ! current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('flashsite_save_wizard_step');
        if (! InstallationGuard::canWriteSensitive()) {
            wp_safe_redirect(admin_url('admin.php?page=flashsite-setup-wizard&updated=installation-protected'));
            exit;
        }

        $step = sanitize_key((string) ($_POST['step'] ?? 'welcome'));
        $steps = $this->buildSteps();

        if (! isset($steps[$step])) {
            wp_safe_redirect(admin_url('admin.php?page=flashsite-setup-wizard'));
            exit;
        }

        $handler = $steps[$step];
        $payload = is_array($_POST) ? $_POST : [];
        $errors = $handler->validate($payload);

        if ($errors !== []) {
            set_transient('flashsite_wizard_errors', $errors, 60);
            wp_safe_redirect(admin_url('admin.php?page=flashsite-setup-wizard&step=' . $step));
            exit;
        }

        $handler->save($payload);
        $this->logger->info('Wizard step saved.', ['step' => $step]);
        $nextStep = $this->resolveNextStep($step);
        wp_safe_redirect(admin_url('admin.php?page=flashsite-setup-wizard&step=' . $nextStep));
        exit;
    }

    private function buildSteps(): array
    {
        return [
            'welcome' => new WelcomeStep($this->onboardingRepository),
            'business-data' => new BusinessDataStep($this->businessRepository, $this->businessValidator, $this->onboardingRepository),
            'dependencies' => new DependenciesStep($this->dependencyRegistry, $this->pluginChecker, $this->onboardingRepository),
            'summary' => new SummaryStep($this->onboardingRepository),
        ];
    }

    private function resolveNextStep(string $currentStep): string
    {
        return match ($currentStep) {
            'welcome' => 'business-data',
            'business-data' => 'dependencies',
            'dependencies' => 'summary',
            default => 'summary',
        };
    }
}
