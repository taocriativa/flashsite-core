<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\CreatorMode;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Access\CreatorPolicy;

/**
 * Modo Criador (@since 3.7.0): um nível acima do administrador.
 *
 * Liga-se só com a constante no wp-config.php (login ou e-mail, vários separados por vírgula):
 *   define('FLASHSITE_CRIADOR', 'flashsite-admin, ola@flashsite.pt');
 * Sem a constante, nada muda.
 *
 * Para quem não é Criador (o dono do site, mesmo administrador):
 * - plugins técnicos (CreatorPolicy::HIDDEN_PLUGINS) somem da lista de plugins, dos menus, dos avisos,
 *   do rodapé do painel, do Painel do WordPress e da barra superior; abrir a tela pela URL dá 403;
 * - plugins de base (CreatorPolicy::PROTECTED_PLUGINS) somem só da lista de plugins;
 * - não instala, não edita, não atualiza nem apaga plugins e temas, nem atualiza o WordPress;
 * - não edita, não exclui nem rebaixa o Criador, que também não aparece em Utilizadores;
 * - Painel do WordPress sem notícias e sem o painel de boas-vindas.
 *
 * Filtros: flashsite_core_creator_hidden_plugins, flashsite_core_creator_protected_plugins,
 * flashsite_core_creator_blocked_caps, flashsite_core_creator_clean_dashboard.
 * Não protege contra quem entra pela hospedagem, FTP ou base de dados (fora do WordPress).
 */
final class CreatorModeModule implements ModuleInterface
{
    /** Ganchos de onde se retiram as funções dos plugins ocultos. */
    private const STRIPPED_HOOKS = ['admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices', 'admin_footer'];

    /** @var list<string>|null */
    private ?array $creators = null;
    /** @var list<int>|null */
    private ?array $creatorIds = null;
    /** @var array<int, bool> */
    private array $creatorCache = [];
    /** @var list<string> páginas do painel (?page=) que pertencem a plugins ocultos */
    private array $blockedPages = [];

    public function register(): void
    {
        if ($this->creatorList() === []) {
            return;
        }
        add_filter('map_meta_cap', [$this, 'mapMetaCap'], PHP_INT_MAX, 4);
        add_filter('all_plugins', [$this, 'filterPlugins'], PHP_INT_MAX);
        add_action('admin_menu', [$this, 'trimMenus'], PHP_INT_MAX);
        add_action('admin_init', [$this, 'blockHiddenPages'], 0);
        add_action('in_admin_header', [$this, 'stripNotices'], PHP_INT_MAX);
        add_action('wp_dashboard_setup', [$this, 'cleanDashboard'], PHP_INT_MAX);
        add_action('add_admin_bar_menus', [$this, 'stripAdminBar'], PHP_INT_MAX);
        add_action('pre_get_users', [$this, 'hideCreatorsFromUsers']);
        add_filter('site_transient_update_plugins', [$this, 'filterUpdates'], PHP_INT_MAX);
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'creator-mode'; }

    // ---- Quem é quem ----------------------------------------------------------------------------

    /** @return list<string> */
    private function creatorList(): array
    {
        if ($this->creators === null) {
            $this->creators = defined('FLASHSITE_CRIADOR') ? CreatorPolicy::parseList((string) constant('FLASHSITE_CRIADOR')) : [];
        }
        return $this->creators;
    }

    public function isCreator(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (! isset($this->creatorCache[$userId])) {
            $user = function_exists('get_userdata') ? get_userdata($userId) : false;
            $this->creatorCache[$userId] = is_object($user)
                && CreatorPolicy::matchesUser((string) ($user->user_login ?? ''), (string) ($user->user_email ?? ''), $this->creatorList());
        }
        return $this->creatorCache[$userId];
    }

    /** Utilizador com sessão que não é Criador (cron, WP-CLI e visitantes ficam de fora). */
    private function restricted(): bool
    {
        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }
        $id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        return $id > 0 && ! $this->isCreator($id);
    }

    /** @return list<int> */
    private function creatorIds(): array
    {
        if ($this->creatorIds === null) {
            $ids = [];
            foreach ($this->creatorList() as $entry) {
                $user = get_user_by(str_contains($entry, '@') ? 'email' : 'login', $entry);
                if (is_object($user) && isset($user->ID)) {
                    $ids[] = (int) $user->ID;
                }
            }
            $this->creatorIds = array_values(array_unique($ids));
        }
        return $this->creatorIds;
    }

    private function hidden(): array
    {
        return (array) apply_filters('flashsite_core_creator_hidden_plugins', CreatorPolicy::HIDDEN_PLUGINS);
    }

    private function protectedPlugins(): array
    {
        return (array) apply_filters('flashsite_core_creator_protected_plugins', CreatorPolicy::PROTECTED_PLUGINS);
    }

    // ---- Permissões ------------------------------------------------------------------------------

    /** @param array<int, string> $caps */
    public function mapMetaCap(array $caps, string $cap, int $userId, array $args): array
    {
        if ($userId <= 0 || $this->isCreator($userId) || (defined('WP_CLI') && WP_CLI)) {
            return $caps;
        }
        $blocked = (array) apply_filters('flashsite_core_creator_blocked_caps', CreatorPolicy::BLOCKED_CAPS);
        $creatorIds = in_array($cap, CreatorPolicy::USER_CAPS, true) ? $this->creatorIds() : [];
        if (CreatorPolicy::shouldDeny($cap, $args, $creatorIds, $blocked, $this->hidden(), $this->protectedPlugins())) {
            return ['do_not_allow'];
        }
        return $caps;
    }

    public function filterPlugins(array $plugins): array
    {
        return $this->restricted() ? CreatorPolicy::filterPluginList($plugins, $this->hidden(), $this->protectedPlugins()) : $plugins;
    }

    /** Sem avisos de atualização dos plugins que o dono não vê. */
    public function filterUpdates(mixed $value): mixed
    {
        if (! $this->restricted() || ! is_object($value) || ! isset($value->response) || ! is_array($value->response)) {
            return $value;
        }
        $copy = clone $value;
        $copy->response = CreatorPolicy::filterPluginList($value->response, $this->hidden(), $this->protectedPlugins());
        return $copy;
    }

    public function hideCreatorsFromUsers(object $query): void
    {
        if (! $this->restricted() || ! is_admin() || ! method_exists($query, 'get') || ! method_exists($query, 'set')) {
            return;
        }
        $ids = $this->creatorIds();
        if ($ids === []) {
            return;
        }
        $exclude = (array) $query->get('exclude');
        $query->set('exclude', array_values(array_unique(array_merge(array_map('intval', $exclude), $ids))));
    }

    // ---- Painel ----------------------------------------------------------------------------------

    public function trimMenus(): void
    {
        if (! $this->restricted()) {
            return;
        }
        global $menu, $submenu;
        $hidden = $this->hidden();

        foreach ((array) $submenu as $parent => $items) {
            foreach ((array) $items as $item) {
                $slug = (string) ($item[2] ?? '');
                if ($slug !== '' && $this->pageOwnedByHidden($slug, (string) $parent, $hidden)) {
                    $this->blockedPages[] = $slug;
                    remove_submenu_page((string) $parent, $slug);
                }
            }
        }
        foreach ((array) $menu as $item) {
            $slug = (string) ($item[2] ?? '');
            if ($slug !== '' && $this->pageOwnedByHidden($slug, '', $hidden)) {
                $this->blockedPages[] = $slug;
                remove_menu_page($slug);
            }
        }
    }

    /** Tela de plugin oculto aberta pela URL: 403. */
    public function blockHiddenPages(): void
    {
        global $plugin_page;
        if (! $this->restricted() || ! is_string($plugin_page ?? null) || $plugin_page === '') {
            return;
        }
        if (in_array($plugin_page, $this->blockedPages, true)) {
            wp_die(esc_html__('Esta área é gerida pela Flash Site.', 'flashsite-core'), '', ['response' => 403]);
        }
    }

    public function stripNotices(): void
    {
        if (! $this->restricted()) {
            return;
        }
        foreach (self::STRIPPED_HOOKS as $hook) {
            $this->stripHook($hook);
        }
    }

    public function stripAdminBar(): void
    {
        if ($this->restricted()) {
            $this->stripHook('admin_bar_menu');
        }
    }

    public function cleanDashboard(): void
    {
        if (! $this->restricted()) {
            return;
        }
        global $wp_meta_boxes;
        $hidden = $this->hidden();
        foreach ((array) ($wp_meta_boxes['dashboard'] ?? []) as $context => $priorities) {
            foreach ((array) $priorities as $priority => $boxes) {
                foreach ((array) $boxes as $id => $box) {
                    if (is_array($box) && isset($box['callback']) && CreatorPolicy::slugMatches($this->owner($box['callback']), $hidden)) {
                        unset($wp_meta_boxes['dashboard'][$context][$priority][$id]);
                    }
                }
            }
        }
        if (apply_filters('flashsite_core_creator_clean_dashboard', true)) {
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            remove_action('welcome_panel', 'wp_welcome_panel');
        }
    }

    // ---- Dono de cada função ---------------------------------------------------------------------

    /** Tira de um gancho as funções que vêm de plugins ocultos. */
    private function stripHook(string $hook): void
    {
        global $wp_filter;
        if (! isset($wp_filter[$hook]) || ! is_object($wp_filter[$hook]) || ! isset($wp_filter[$hook]->callbacks)) {
            return;
        }
        $hidden = $this->hidden();
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ((array) $callbacks as $callback) {
                $function = $callback['function'] ?? null;
                if ($function !== null && CreatorPolicy::slugMatches($this->owner($function), $hidden)) {
                    remove_filter($hook, $function, (int) $priority);
                }
            }
        }
    }

    private function pageOwnedByHidden(string $slug, string $parent, array $hidden): bool
    {
        global $wp_filter;
        if (! function_exists('get_plugin_page_hookname')) {
            return false;
        }
        $hookname = (string) get_plugin_page_hookname($slug, $parent);
        if ($hookname === '' || ! isset($wp_filter[$hookname]->callbacks)) {
            return false;
        }
        foreach ($wp_filter[$hookname]->callbacks as $callbacks) {
            foreach ((array) $callbacks as $callback) {
                if (CreatorPolicy::slugMatches($this->owner($callback['function'] ?? null), $hidden)) {
                    return true;
                }
            }
        }
        return false;
    }

    /** Pasta do plugin onde a função foi escrita ('' se não for de um plugin). */
    private function owner(mixed $callable): string
    {
        if (! defined('WP_PLUGIN_DIR')) {
            return '';
        }
        try {
            if ($callable instanceof \Closure || (is_string($callable) && function_exists($callable))) {
                $file = (new \ReflectionFunction($callable))->getFileName();
            } elseif (is_array($callable) && count($callable) === 2 && is_string($callable[1])) {
                $file = (new \ReflectionMethod($callable[0], $callable[1]))->getFileName();
            } elseif (is_string($callable) && str_contains($callable, '::')) {
                $file = (new \ReflectionMethod($callable))->getFileName();
            } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
                $file = (new \ReflectionMethod($callable, '__invoke'))->getFileName();
            } else {
                return '';
            }
        } catch (\ReflectionException) {
            return '';
        }
        return is_string($file) ? CreatorPolicy::slugFromFile($file, WP_PLUGIN_DIR) : '';
    }
}
