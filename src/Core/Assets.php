<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class Assets
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'registerFrontendAssets']);
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueueElementorPreviewHeroStyle']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueueElementorPreviewHeroScript']);
        add_action('elementor/editor/before_enqueue_styles', [$this, 'enqueueElementorEditorHeroStyle']);
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'enqueueElementorEditorHeroScript']);
        add_filter('admin_body_class', [$this, 'addAdminBodyClass']);
    }

    public function addAdminBodyClass(string $classes): string
    {
        $screen = get_current_screen();
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if (($screen && str_contains((string) $screen->id, 'flashsite')) || ($page !== '' && str_starts_with($page, 'flashsite-'))) {
            $classes .= ' flashsite-admin';
        }
        return $classes;
    }

    private function isFlashSiteAdminPage(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        return $page !== '' && str_starts_with($page, 'flashsite-');
    }


    public function registerFrontendAssets(): void
    {
        $heroStyleUrl = FLASHSITE_CORE_URL . 'assets/frontend/css/fsc-hero.css';
        $heroScriptUrl = FLASHSITE_CORE_URL . 'assets/frontend/js/fsc-hero.js';

        wp_register_style(
            'flashsite-core-hero',
            $heroStyleUrl,
            [],
            FLASHSITE_CORE_VERSION
        );

        wp_register_script(
            'flashsite-core-hero',
            $heroScriptUrl,
            [],
            FLASHSITE_CORE_VERSION,
            true
        );
    }

    public function registerAdminAssets(): void
    {
        $sharedStyleUrl = FLASHSITE_CORE_URL . 'assets/admin/css/flashsite-admin.css';
        $styleUrl = FLASHSITE_CORE_URL . 'assets/admin/css/fsc-admin.css';
        $adminScriptUrl = FLASHSITE_CORE_URL . 'assets/admin/js/fsc-admin.js';
        $wizardScriptUrl = FLASHSITE_CORE_URL . 'assets/admin/js/fsc-wizard.js';

        // Register shared design-system CSS (idempotent — first plugin to register wins, both point to identical asset).
        if (! wp_style_is('flashsite-admin-shared', 'registered')) {
            wp_register_style(
                'flashsite-admin-shared',
                $sharedStyleUrl,
                [],
                '4.0.0'
            );
        }

        wp_register_style(
            'flashsite-core-admin',
            $styleUrl,
            ['wp-color-picker', 'flashsite-admin-shared'],
            FLASHSITE_CORE_VERSION
        );

        wp_register_script(
            'flashsite-core-admin',
            $adminScriptUrl,
            ['jquery', 'wp-color-picker'],
            FLASHSITE_CORE_VERSION,
            true
        );

        wp_register_script(
            'flashsite-core-wizard',
            $wizardScriptUrl,
            ['flashsite-core-admin'],
            FLASHSITE_CORE_VERSION,
            true
        );

        // Fallback robusto: submenus adicionados por outro plugin ou por alterações de parent slug
        // podem gerar screen IDs diferentes. Qualquer página admin.php?page=flashsite-*
        // deve receber o design system base do painel FlashSite.
        if ($this->isFlashSiteAdminPage()) {
            // Necessário para wp.media() funcionar nos campos de logo e imagem.
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('flashsite-admin-shared');
            wp_enqueue_style('flashsite-core-admin');
            wp_enqueue_script('flashsite-core-admin');

            $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
            if ($page === 'flashsite-setup-wizard') {
                wp_enqueue_script('flashsite-core-wizard');
            }
        }
    }

    public function enqueueAdminForScreens(array $screenIds, bool $withWizard = false): void
    {
        add_action('admin_enqueue_scripts', static function () use ($screenIds, $withWizard): void {
            $screen = get_current_screen();
            if (! $screen || ! in_array($screen->id, $screenIds, true)) {
                return;
            }

            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('flashsite-admin-shared');
            wp_enqueue_style('flashsite-core-admin');
            wp_enqueue_script('flashsite-core-admin');

            if ($withWizard) {
                wp_enqueue_script('flashsite-core-wizard');
            }
        });
    }


    public function enqueueFrontendHero(): void
    {
        if (! wp_style_is('flashsite-core-hero', 'registered')) {
            $this->registerFrontendAssets();
        }

        wp_enqueue_style('flashsite-core-hero');
        wp_enqueue_script('flashsite-core-hero');
    }

    public function enqueueElementorPreviewHeroStyle(): void
    {
        if (! wp_style_is('flashsite-core-hero', 'registered')) {
            $this->registerFrontendAssets();
        }

        wp_enqueue_style('flashsite-core-hero');
    }

    public function enqueueElementorPreviewHeroScript(): void
    {
        if (! wp_script_is('flashsite-core-hero', 'registered')) {
            $this->registerFrontendAssets();
        }

        wp_enqueue_script('flashsite-core-hero');
    }

    public function enqueueElementorEditorHeroStyle(): void
    {
        $this->enqueueElementorPreviewHeroStyle();
    }

    public function enqueueElementorEditorHeroScript(): void
    {
        $this->enqueueElementorPreviewHeroScript();
    }

}
