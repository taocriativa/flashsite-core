<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\PrivacyPolicy;

use FlashSite\Core\Core\Contracts\ModuleInterface;

/**
 * PrivacyPolicyModule
 *
 * Gere o conteúdo da Política de Privacidade diretamente no painel FlashSite.
 * Fornece editor TinyMCE, auto-datação a cada save, e shortcodes de output.
 *
 * Shortcodes:
 *   [flashsite_privacy_policy]         — conteúdo completo
 *   [flashsite_privacy_date]           — data da última atualização
 *   [flashsite_privacy_date format="d/m/Y"] — data com formato personalizado
 *
 * @since 2.5.0
 */
final class PrivacyPolicyModule implements ModuleInterface
{
    private const OPTION_CONTENT = 'flashsite_privacy_policy_content';
    private const OPTION_DATE    = 'flashsite_privacy_policy_updated';
    private const NONCE_ACTION   = 'flashsite_save_privacy_policy';

    public function register(): void
    {
        add_shortcode('flashsite_privacy_policy', [$this, 'renderPolicyShortcode']);
        add_shortcode('flashsite_privacy_date',   [$this, 'renderDateShortcode']);
        add_action('admin_post_flashsite_save_privacy_policy', [$this, 'handleSave']);
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'privacy-policy'; }

    // ── Shortcodes ────────────────────────────────────────────────────────────

    /** @param array<string, mixed>|string $atts */
    public function renderPolicyShortcode(array|string $atts = []): string
    {
        $content = get_option(self::OPTION_CONTENT, '');
        if ($content === '' || $content === false) {
            return '';
        }
        return wp_kses_post(wpautop((string) $content));
    }

    /** @param array<string, mixed>|string $atts */
    public function renderDateShortcode(array|string $atts = []): string
    {
        $atts   = shortcode_atts(['format' => 'd/m/Y'], is_array($atts) ? $atts : [], 'flashsite_privacy_date');
        $stored = get_option(self::OPTION_DATE, '');
        if ($stored === '' || $stored === false) {
            return '';
        }
        $ts = strtotime((string) $stored);
        if ($ts === false) {
            return esc_html((string) $stored);
        }
        return esc_html(date((string) $atts['format'], $ts));
    }

    // ── Save handler ─────────────────────────────────────────────────────────

    public function handleSave(): void
    {
        if (! current_user_can('flashsite_manage_business_data')) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE_ACTION);

        $content = isset($_POST['flashsite_privacy_content'])
            ? wp_kses_post(wp_unslash((string) $_POST['flashsite_privacy_content']))
            : '';

        update_option(self::OPTION_CONTENT, $content, false);
        update_option(self::OPTION_DATE, current_time('Y-m-d'), false);

        wp_safe_redirect(admin_url('admin.php?page=flashsite-privacy-policy&updated=1'));
        exit;
    }

    // ── Helpers públicos (usados pelo template) ───────────────────────────────

    public static function getContent(): string
    {
        return (string) get_option(self::OPTION_CONTENT, '');
    }

    public static function getUpdatedDate(string $format = 'd/m/Y'): string
    {
        $stored = get_option(self::OPTION_DATE, '');
        if ($stored === '' || $stored === false) {
            return '';
        }
        $ts = strtotime((string) $stored);
        return $ts !== false ? date($format, $ts) : (string) $stored;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }
}
