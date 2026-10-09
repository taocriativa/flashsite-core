<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\ClientPanel;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\SiteTexts\SiteTextsRegistry;
use FlashSite\Core\Modules\Collections\CollectionsModule;

/**
 * Painel do cliente (@since 3.1.0).
 *
 * - "O que pode editar": primeira tela do painel, com atalhos para tudo o que o cliente muda.
 * - Perfil "Cliente" (papel flashsite_site_manager): entra direto nessa tela, sem o Painel do
 *   WordPress, sem menus e avisos de plugins e sem as telas técnicas do FlashSite.
 */
final class ClientPanelModule implements ModuleInterface
{
    public const SLUG = 'flashsite-edit';
    public const CAP = 'flashsite_view_dashboard';
    public const ROLE = CollectionCapabilities::SITE_MANAGER_ROLE;
    public const ROLE_NAME = 'Cliente';

    /** Telas do FlashSite que o cliente não vê (técnicas ou só para a Flash Site). */
    public const HIDDEN_SUBMENUS = ['flashsite-core', 'flashsite-shortcodes', 'flashsite-setup-wizard', 'flashsite-data-safety', 'flashsite-markets', 'flashsite-collections'];

    /** Itens da barra superior que somem para o cliente. */
    /** Botões flutuantes de assistentes da hospedagem (ex.: Kodee, "Pergunte à IA" da Hostinger). */
    public const HIDDEN_WIDGETS = ['.kodee-fab', '[class^="kodee-"]', '[id^="kodee"]', '#hostinger-ai-assistant'];

    public const HIDDEN_BAR_NODES = ['wp-logo', 'comments', 'new-content', 'updates', 'elementor_edit_page', 'elementor', 'litespeed-menu', 'rank-math', 'hostinger_admin_bar', 'hostinger-ai-assistant', 'search', 'customize', 'edit', 'cookie-law-info'];

    private MediaGuard $media;

    public function __construct(private CollectionsModule $collections, private SiteTextsRegistry $texts, private ?CollectionSettings $settings = null)
    {
        $this->media = new MediaGuard();
    }

    public function register(): void
    {
        add_action('init', [$this, 'ensureRoleName']);
        add_action('admin_menu', [$this, 'addMenu'], 9);
        add_action('admin_menu', [$this, 'trimMenusForClient'], 999);
        add_action('admin_init', [$this, 'redirectDashboard']);
        add_filter('login_redirect', [$this, 'loginRedirect'], 10, 3);
        add_action('admin_bar_menu', [$this, 'trimAdminBar'], 999);
        add_action('in_admin_header', [$this, 'silenceNotices'], 1);
        add_action('admin_head', [$this, 'hideAssistants']);
        add_action('admin_notices', [$this, 'adminWarning']);
        add_action('wp_head', [$this, 'hideAssistants']);
        add_action('current_screen', [$this, 'maybeTranslateScreen']);
        add_filter('map_meta_cap', [$this, 'protectMediaInUse'], 10, 4);
        foreach (['save_post', 'deleted_post', 'elementor/document/after_save'] as $hook) {
            add_action($hook, [MediaGuard::class, 'forget']);
        }
        foreach (['update_option_site_icon', 'update_option_flashsite_business_profile'] as $hook) {
            add_action($hook, [MediaGuard::class, 'forget']);
        }
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'client-panel'; }

    public static function isClient(?object $user = null): bool
    {
        $user ??= function_exists('wp_get_current_user') ? wp_get_current_user() : null;
        if (! is_object($user) || ! isset($user->roles) || ! is_array($user->roles)) {
            return false;
        }
        return in_array(self::ROLE, $user->roles, true) && ! in_array('administrator', $user->roles, true);
    }

    /** O papel antigo "Gestor de Site" passa a chamar-se "Cliente". */
    public function ensureRoleName(): void
    {
        $roles = function_exists('wp_roles') ? wp_roles() : null;
        if (! is_object($roles) || ! isset($roles->roles[self::ROLE]) || $roles->roles[self::ROLE]['name'] === self::ROLE_NAME) {
            return;
        }
        $roles->roles[self::ROLE]['name'] = self::ROLE_NAME;
        $roles->role_names[self::ROLE] = self::ROLE_NAME;
        if ($roles->use_db) {
            update_option($roles->role_key, $roles->roles);
        }
    }

    public function addMenu(): void
    {
        add_menu_page('O que pode editar', 'O que pode editar', self::CAP, self::SLUG, [$this, 'renderPage'], 'dashicons-edit', 2);
    }

    public function trimMenusForClient(): void
    {
        if (! self::isClient()) {
            return;
        }
        foreach (['index.php', 'edit-comments.php', 'tools.php', 'edit.php'] as $menu) {
            remove_menu_page($menu);
        }
        foreach (self::HIDDEN_SUBMENUS as $submenu) {
            remove_submenu_page('flashsite-core', $submenu);
        }
    }

    /** O cliente não usa o Painel do WordPress: vai para "O que pode editar". */
    public function redirectDashboard(): void
    {
        global $pagenow;
        if (! self::isClient() || wp_doing_ajax() || ($pagenow ?? '') !== 'index.php') {
            return;
        }
        wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG));
        exit;
    }

    /**
     * @param string $redirectTo
     * @param string $requested
     * @param mixed $user
     */
    public function loginRedirect($redirectTo, $requested, $user): string
    {
        if (is_object($user) && self::isClient($user) && (str_ends_with((string) $redirectTo, '/wp-admin/') || $redirectTo === admin_url())) {
            return admin_url('admin.php?page=' . self::SLUG);
        }
        return (string) $redirectTo;
    }

    /** @param mixed $bar */
    public function trimAdminBar($bar): void
    {
        if (! self::isClient() || ! is_object($bar) || ! method_exists($bar, 'remove_node')) {
            return;
        }
        foreach (self::HIDDEN_BAR_NODES as $node) {
            $bar->remove_node($node);
        }
    }

    /** Cliente não vê assistentes de IA da hospedagem (não editam o site do jeito certo). */
    public function hideAssistants(): void
    {
        if (! self::isClient() || (! is_admin() && ! (function_exists('is_admin_bar_showing') && is_admin_bar_showing()))) {
            return;
        }
        echo '<style id="fsc-hide-assistants">' . implode(',', self::HIDDEN_WIDGETS) . '{display:none!important}</style>';
    }

    /** Telas onde o administrador vê o aviso de acesso administrativo (@since 3.1.0-beta.9). */
    public const ADMIN_WARNING_SCREENS = ['dashboard', 'plugins', 'themes', 'theme-editor', 'plugin-editor', 'options-general', 'users', 'toplevel_page_flashsite-edit'];

    /**
     * Site entregue (há um usuário Cliente): o administrador vê, nas telas sensíveis, que este
     * acesso mexe na base do site e que o dia a dia é pelo acesso de gestão.
     */
    public function adminWarning(): void
    {
        if (self::isClient() || ! current_user_can('manage_options') || ! apply_filters('flashsite/admin_warning', true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! is_object($screen) || ! in_array((string) $screen->id, self::ADMIN_WARNING_SCREENS, true)) {
            return;
        }
        if (get_users(['role' => self::ROLE, 'number' => 1, 'fields' => 'ID']) === []) {
            return;
        }
        echo '<div class="notice notice-error fsc-admin-warning"><p><strong>Acesso administrativo.</strong> Alterações feitas aqui mexem na base do site e podem comprometer o seu funcionamento. Para textos, fotos e itens, use o acesso de gestão.</p></div>';
    }

    /** Avisos de plugins (licenças, promoções, atualizações) não são para o cliente. */
    public function silenceNotices(): void
    {
        if (! self::isClient()) {
            return;
        }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (is_object($screen) && in_array((string) $screen->id, ['upload', 'attachment'], true)) {
            add_action('admin_notices', [$this, 'mediaNotice']);
        }
    }

    /**
     * Cliente: arquivo em uso no site não pode ser excluído (ver MediaGuard).
     *
     * @param list<string> $caps
     * @param string $cap
     * @param int $userId
     * @param array<int, mixed> $args
     * @return list<string>
     */
    public function protectMediaInUse($caps, $cap, $userId, $args): array
    {
        $caps = (array) $caps;
        if ($cap !== 'delete_post' || empty($args[0]) || get_post_type((int) $args[0]) !== 'attachment') {
            return $caps;
        }
        $user = get_userdata((int) $userId);
        if ($user && self::isClient($user) && $this->media->isInUse((int) $args[0])) {
            $caps[] = 'do_not_allow';
        }
        return $caps;
    }

    public function mediaNotice(): void
    {
        $names = [];
        foreach ($this->collections->activePresets() as $preset) {
            $object = get_post_type_object($preset->postType());
            if ($object !== null) {
                $names[] = mb_strtolower((string) $object->labels->name);
            }
        }
        $items = $names !== [] ? ' e fotos de ' . implode(', ', $names) : '';
        printf(
            '<div class="notice notice-info"><p><strong>Fotos em uso no site ficam protegidas</strong> e não podem ser excluídas daqui (logos, ícones, imagens das páginas%s). %sPode adicionar fotos novas e excluir as que não estão em uso.</p></div>',
            esc_html($items),
            $names !== [] ? 'Para trocar ou tirar a foto de um item, faça na ficha dele. ' : ''
        );
    }

    /**
     * Sites BR: as telas do FlashSite e das coleções saem em PT-BR (ver PtBrAdmin).
     *
     * @param mixed $screen
     */
    public function maybeTranslateScreen($screen): void
    {
        if ($this->settings === null || $this->settings->market() !== 'BR' || ! is_object($screen)) {
            return;
        }
        $postTypes = array_map(static fn ($preset): string => $preset->postType(), $this->collections->activePresets());
        $id = (string) ($screen->id ?? '');
        $isCollection = in_array((string) ($screen->post_type ?? ''), $postTypes, true);
        if (str_contains($id, 'flashsite') || $isCollection) {
            ob_start([PtBrAdmin::class, 'convert']);
        }
    }

    public function renderPage(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die(esc_html__('Sem permissão.', 'flashsite-core'));
        }
        $cards = $this->cards();
        include FLASHSITE_CORE_PATH . 'templates/admin/client-panel.php';
    }

    /**
     * Atalhos da tela "O que pode editar", pela ordem de uso.
     *
     * @return list<array{icon: string, title: string, text: string, links: list<array{label: string, url: string, primary?: bool}>}>
     */
    public function cards(): array
    {
        $cards = [];
        foreach ($this->collections->activePresets() as $preset) {
            $postType = $preset->postType();
            $object = get_post_type_object($postType);
            if ($object === null || ! current_user_can($object->cap->edit_posts)) {
                continue;
            }
            $count = wp_count_posts($postType);
            $published = (int) ($count->publish ?? 0);
            $drafts = (int) ($count->draft ?? 0) + (int) ($count->pending ?? 0);
            $text = sprintf('%d publicado(s)', $published) . ($drafts > 0 ? sprintf(' · %d por publicar', $drafts) : '') . '. Fotos, textos e detalhes de cada um.';
            $cards[] = [
                'icon' => $preset->menuIcon() !== '' ? $preset->menuIcon() : 'dashicons-portfolio',
                'title' => (string) $object->labels->name,
                'text' => $text,
                'links' => [
                    ['label' => (string) $object->labels->add_new_item, 'url' => admin_url('post-new.php?post_type=' . $postType), 'primary' => true],
                    ['label' => 'Ver e editar todos', 'url' => admin_url('edit.php?post_type=' . $postType)],
                ],
            ];
        }
        if ($this->texts->fields() !== [] && current_user_can('flashsite_manage_business_data')) {
            $cards[] = [
                'icon' => 'dashicons-editor-paragraph',
                'title' => 'Textos do site',
                'text' => 'Títulos e textos das páginas. O layout fica protegido.',
                'links' => [['label' => 'Mudar textos', 'url' => admin_url('admin.php?page=flashsite-site-texts'), 'primary' => true]],
            ];
        }
        if (current_user_can('flashsite_manage_business_data')) {
            $cards[] = [
                'icon' => 'dashicons-id',
                'title' => 'Dados do negócio',
                'text' => 'Telefone, WhatsApp, e-mail, endereço, horários e redes sociais. Mudam em todo o site.',
                'links' => [['label' => 'Editar dados', 'url' => admin_url('admin.php?page=flashsite-business-data'), 'primary' => true]],
            ];
            $cards[] = [
                'icon' => 'dashicons-format-chat',
                'title' => 'WhatsApp e formulários',
                'text' => 'Para onde vão as mensagens do site.',
                'links' => [['label' => 'Configurar', 'url' => admin_url('admin.php?page=flashsite-contact')]],
            ];
            $cards[] = [
                'icon' => 'dashicons-shield',
                'title' => 'Política de privacidade',
                'text' => 'O texto legal do site. A data de atualização muda sozinha.',
                'links' => [['label' => 'Editar política', 'url' => admin_url('admin.php?page=flashsite-privacy-policy')]],
            ];
        }
        if (current_user_can('upload_files')) {
            $cards[] = [
                'icon' => 'dashicons-format-image',
                'title' => 'Fotos e ficheiros',
                'text' => 'Todas as imagens do site. Prefira fotos na horizontal e com boa luz.',
                'links' => [['label' => 'Abrir biblioteca', 'url' => admin_url('upload.php')]],
            ];
        }
        $cards[] = [
            'icon' => 'dashicons-admin-users',
            'title' => 'A sua conta',
            'text' => 'Nome, e-mail de acesso e palavra-passe.',
            'links' => [['label' => 'Editar conta', 'url' => admin_url('profile.php')]],
        ];
        return $cards;
    }
}
