<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\AdminUX;

use FlashSite\Core\Core\Assets;
use FlashSite\Core\Core\InstallationGuard;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Safety\DataSafetyManager;

final class AdminUXModule implements ModuleInterface
{
    public function __construct(
        private BusinessRepository $businessRepository,
        private OnboardingRepository $onboardingRepository,
        private Assets $assets,
        private DataSafetyManager $dataSafety
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_flashsite_toggle_delete_flag', [$this, 'handleToggleDeleteFlag']);
        add_action('admin_post_flashsite_restore_backup', [$this, 'handleRestoreBackup']);
        add_action('admin_post_flashsite_backup_now', [$this, 'handleBackupNow']);
        $this->assets->enqueueAdminForScreens(['toplevel_page_flashsite-core', 'flashsite-core_page_flashsite-business-data', 'flashsite-core_page_flashsite-data-safety']);
    }

    public function boot(): void {}
    public function isActive(): bool { return is_admin(); }
    public function getSlug(): string { return 'admin-ux'; }

    public function registerMenu(): void
    {
        add_menu_page('FlashSite', 'FlashSite', 'flashsite_view_dashboard', 'flashsite-core', [$this, 'renderDashboard'], 'dashicons-admin-generic', 58);
        add_submenu_page('flashsite-core', 'Visão Geral', 'Visão Geral', 'flashsite_view_dashboard', 'flashsite-core', [$this, 'renderDashboard']);
        add_submenu_page('flashsite-core', 'Dados do Negócio', 'Dados do Negócio', 'flashsite_manage_business_data', 'flashsite-business-data', [$this, 'renderBusinessDataPage']);
        add_submenu_page('flashsite-core', 'Segurança de Dados', 'Segurança de Dados', 'flashsite_manage_business_data', 'flashsite-data-safety', [$this, 'renderDataSafetyPage']);
    }

    public function renderDashboard(): void
    {
        $profile = $this->businessRepository->getProfile();
        $state = $this->onboardingRepository->getState();
        include FLASHSITE_CORE_PATH . 'templates/admin/dashboard.php';
    }

    public function renderBusinessDataPage(): void
    {
        $profile = $this->businessRepository->getProfile();
        include FLASHSITE_CORE_PATH . 'templates/admin/business-data.php';
    }

    public function renderDataSafetyPage(): void
    {
        $profile = $this->businessRepository->getProfile();
        $backupMeta = $this->dataSafety->latestBackupMeta();
        $hasBackup = $this->dataSafety->hasBackup();
        $allowDeletion = $this->dataSafety->isDeletionAllowed();
        $installationStatus = InstallationGuard::status();
        include FLASHSITE_CORE_PATH . 'templates/admin/data-safety.php';
    }

    public function handleToggleDeleteFlag(): void
    {
        $this->assertManageDataRequest('flashsite_toggle_delete_flag');
        if (! InstallationGuard::canWriteSensitive()) {
            wp_safe_redirect(admin_url('admin.php?page=flashsite-data-safety&updated=installation-protected'));
            exit;
        }

        $allowed = isset($_POST['allow_data_deletion']) && $_POST['allow_data_deletion'] === '1';
        $this->dataSafety->setDeletionAllowed($allowed);
        wp_safe_redirect(admin_url('admin.php?page=flashsite-data-safety&updated=delete-flag'));
        exit;
    }

    public function handleBackupNow(): void
    {
        $this->assertManageDataRequest('flashsite_backup_now');
        $saved = $this->dataSafety->backupProfile($this->businessRepository->getRaw(), 'manual_backup');
        wp_safe_redirect(admin_url('admin.php?page=flashsite-data-safety&updated=' . ($saved ? 'backup-saved' : 'backup-skipped')));
        exit;
    }

    public function handleRestoreBackup(): void
    {
        $this->assertManageDataRequest('flashsite_restore_backup');
        $restored = $this->businessRepository->restoreLatestBackup();
        wp_safe_redirect(admin_url('admin.php?page=flashsite-data-safety&updated=' . ($restored ? 'backup-restored' : 'backup-missing')));
        exit;
    }

    private function assertManageDataRequest(string $action): void
    {
        if (! current_user_can('flashsite_manage_business_data')) {
            wp_die('Sem permissão.');
        }
        check_admin_referer($action);
    }

}
