<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

use FlashSite\Core\Domain\Business\BusinessTranslations;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * FlashSite › Dados por idioma (@since 3.2.0-beta.5). Só aparece com o Polylang ativo.
 *
 * Mostra, para cada idioma além do principal, os campos de texto dos Dados do negócio que mudam
 * de idioma (frase curta, título, horários…). Vazio = mostra o do idioma principal.
 * REST flashsite/v1/business-translations (GET/POST) para o MCP.
 */
final class BusinessTranslationsPage
{
    public const SLUG = 'flashsite-business-languages';
    public const CAP = 'flashsite_manage_business_data';
    private const NONCE = 'flashsite_business_languages_save';

    private BusinessTranslations $translations;

    public function __construct()
    {
        $this->translations = new BusinessTranslations(new OptionsStorage());
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage'], 16);
        add_action('admin_post_' . self::NONCE, [$this, 'handleSave']);
        add_action('rest_api_init', [$this, 'registerRest']);
    }

    public function addPage(): void
    {
        add_submenu_page('flashsite-core', 'Dados por idioma', 'Dados por idioma', self::CAP, self::SLUG, [$this, 'render']);
    }

    /** @return list<string> idiomas do site além do principal */
    public static function otherLanguages(): array
    {
        $context = Strings::context();
        if (! $context instanceof LanguageContextInterface) {
            return [];
        }
        return array_values(array_diff($context->available(), [$context->primary()]));
    }

    public function render(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die('Sem permissão.');
        }
        $languages = self::otherLanguages();
        $values = $this->translations->all();
        $original = (array) get_option('flashsite_business_profile', []);
        $headerTitle = 'Dados por idioma';
        $headerSubtitle = 'Textos dos Dados do negócio nos outros idiomas do site. Nome, contactos e morada são os mesmos em todos. Campo vazio mostra o texto do idioma principal.';
        $headerActions = [['label' => 'Dados do negócio', 'url' => admin_url('admin.php?page=flashsite-business-data'), 'variant' => 'secondary']];
        echo '<div class="wrap flashsite-core-wrap">';
        include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php';
        if (isset($_GET['fs_saved'])) { // phpcs:ignore WordPress.Security.NonceVerification
            echo '<div class="notice notice-success is-dismissible"><p>Guardado. Já está no site.</p></div>';
        }
        if ($languages === []) {
            echo '<div class="fsc-card"><p>O site tem um idioma só. Para usar esta tela, adicione idiomas no Polylang.</p></div></div>';
            return;
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::NONCE) . '">';
        wp_nonce_field(self::NONCE);
        echo '<div class="fsc-card-grid" style="grid-template-columns:1fr;">';
        foreach ($languages as $language) {
            printf('<div class="fsc-card"><h2>%s</h2><table class="form-table" role="presentation">', esc_html(Language::SUPPORTED[$language] ?? $language));
            foreach (BusinessTranslations::PATHS as $path => $label) {
                [$group, $key] = explode('.', $path, 2);
                $placeholder = (string) ($original[$group][$key] ?? '');
                $id = 'fs-bt-' . $language . '-' . str_replace('.', '-', $path);
                $value = (string) ($values[$language][$path] ?? '');
                $field = $path === 'hours.notes' || $path === 'identity.tagline'
                    ? sprintf('<textarea id="%s" name="fs_bt[%s][%s]" rows="2" class="large-text" placeholder="%s">%s</textarea>', esc_attr($id), esc_attr($language), esc_attr($path), esc_attr($placeholder), esc_textarea($value))
                    : sprintf('<input type="text" id="%s" name="fs_bt[%s][%s]" class="regular-text" placeholder="%s" value="%s">', esc_attr($id), esc_attr($language), esc_attr($path), esc_attr($placeholder), esc_attr($value));
                printf('<tr><th scope="row"><label for="%s">%s</label></th><td>%s</td></tr>', esc_attr($id), esc_html($label), $field); // phpcs:ignore WordPress.Security.EscapeOutput
            }
            echo '</table></div>';
        }
        echo '<div class="fsc-card-actions">';
        submit_button('Guardar', 'primary fsc-btn', 'submit', false);
        echo '</div></div></form></div>';
    }

    public function handleSave(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        $input = isset($_POST['fs_bt']) && is_array($_POST['fs_bt']) ? wp_unslash($_POST['fs_bt']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $this->translations->save(array_intersect_key($input, array_flip(self::otherLanguages())));
        do_action('flashsite/business_translations/changed');
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'fs_saved' => 1], admin_url('admin.php')));
        exit;
    }

    public function registerRest(): void
    {
        register_rest_route('flashsite/v1', '/business-translations', [
            ['methods' => 'GET', 'callback' => fn () => ['languages' => self::otherLanguages(), 'fields' => BusinessTranslations::PATHS, 'values' => $this->translations->all()], 'permission_callback' => fn () => current_user_can(self::CAP)],
            ['methods' => 'POST', 'callback' => [$this, 'restSave'], 'permission_callback' => fn () => current_user_can(self::CAP)],
        ]);
    }

    /** @param \WP_REST_Request $request corpo: {"en": {"hours.notes": "…"}, "fr": {…}} */
    public function restSave($request): array
    {
        $input = (array) $request->get_json_params();
        $this->translations->save(array_intersect_key($input, array_flip(self::otherLanguages())));
        do_action('flashsite/business_translations/changed');
        return ['values' => $this->translations->all()];
    }
}
