<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

use FlashSite\Core\Domain\Access\RoleManager;

final class Plugin
{
    private static ?self $instance = null;
    private ?Application $application = null;
    private bool $booted = false;

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) { return; }

        $status = InstallationGuard::status();
        if (($status['path_status'] ?? 'canonical') !== 'canonical' || ($status['duplicate_candidates'] ?? []) !== []) {
            add_action('admin_notices', static function () use ($status): void {
                $details = [];
                if (($status['path_status'] ?? 'canonical') !== 'canonical') {
                    $details[] = 'instalação actual fora da pasta canónica <code>flashsite-core</code>';
                }
                if (($status['duplicate_candidates'] ?? []) !== []) {
                    $details[] = 'outra instalação candidata detectada: <code>' . esc_html((string) ($status['duplicate_candidates'][0]['file'] ?? 'desconhecida')) . '</code>';
                }
                echo '<div class="notice notice-error"><p><strong>FlashSite Core em modo de protecção:</strong> ' . wp_kses_post(implode('; ', $details)) . '. Operações sensíveis de escrita, purge e normalização destrutiva foram bloqueadas até restar apenas a instalação canónica. Remova a instalação legada antes de continuar.</p></div>';
            });
        }

        add_action('admin_init', [$this, 'handleHeroMigrationNoticeDismiss']);
        add_action('admin_notices', [$this, 'maybeRenderHeroMigrationNotice']);

        $this->application = new Application();
        $this->application->initialize();
        $this->booted = true;
    }

    public function activate(): void
    {
        $defaults = require FLASHSITE_CORE_PATH . 'config/defaults.php';
        foreach ($defaults as $key => $value) {
            if (get_option($key, null) === null) {
                update_option($key, $value, str_ends_with($key, '_version') || str_ends_with($key, '_activated_at') || str_ends_with($key, '_state'));
            }
        }
        update_option('flashsite_core_version', FLASHSITE_CORE_VERSION, true);
        if (get_option('flashsite_allow_data_deletion', null) === null) {
            update_option('flashsite_allow_data_deletion', false, true);
        }
        $storedDataVersion = get_option('flashsite_data_version', null);
        if (! is_string($storedDataVersion) || $storedDataVersion === '' || version_compare($storedDataVersion, FLASHSITE_DATA_VERSION, '<')) {
            update_option('flashsite_data_version', FLASHSITE_DATA_VERSION, true);
        }
        update_option('flashsite_core_activated_at', gmdate('c'), true);
        (new RoleManager(FLASHSITE_CORE_PATH . 'config/capabilities.php'))->ensureRole();
    }

    public function deactivate(): void {}

    public function application(): ?Application
    {
        return $this->application;
    }

    public function handleHeroMigrationNoticeDismiss(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        if (! isset($_GET['flashsite_dismiss_notice']) || $_GET['flashsite_dismiss_notice'] !== 'hero_migration') {
            return;
        }

        check_admin_referer('flashsite_dismiss_hero_migration');
        update_user_meta(get_current_user_id(), 'flashsite_notice_dismissed_hero_migration', '1');
        wp_safe_redirect(admin_url('admin.php?page=flashsite-core'));
        exit;
    }

    public function maybeRenderHeroMigrationNotice(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        if (($GLOBALS['pagenow'] ?? '') !== 'admin.php' || (string) ($_GET['page'] ?? '') !== 'flashsite-core') {
            return;
        }

        if ((string) get_user_meta(get_current_user_id(), 'flashsite_notice_dismissed_hero_migration', true) === '1') {
            return;
        }

        $heroState = get_option('flashsite_managed_hero', []);
        $hasLegacyHero = false;
        if (is_array($heroState)) {
            $slides = $heroState['slides'] ?? [];
            $hasLegacyHero = ! empty($slides) || ! empty(array_filter($heroState));
        }
        if (! $hasLegacyHero) {
            return;
        }

        $designActive = defined('FLASHSITE_DESIGN_VERSION') || function_exists('flashsite_design_version');

        // Once the official Design plugin is active, the migration notice is no longer useful.
        if ($designActive) {
            return;
        }

        $dismissUrl = wp_nonce_url(
            admin_url('admin.php?page=flashsite-core&flashsite_dismiss_notice=hero_migration'),
            'flashsite_dismiss_hero_migration'
        );

        echo '<div class="notice notice-info"><p><strong>FlashSite Core 2.0.1:</strong> Os Hero Banners já não fazem parte do FlashSite Core. Os dados antigos foram preservados. Instale o plugin FlashSite Design para voltar a gerir e renderizar este recurso. <a href="' . esc_url($dismissUrl) . '">Dispensar aviso</a></p></div>';
    }
}
