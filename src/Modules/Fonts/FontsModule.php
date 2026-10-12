<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Fonts;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Fonts\FontPlan;

/**
 * FlashSite › Fontes (@since 3.2.0-beta.6): fontes do site servidas pelo próprio site, sem troca
 * visível ao carregar.
 *
 * Ao preparar: lê as fontes das variáveis do Elementor (e da tipografia do kit), baixa do Google só
 * os pesos da hierarquia (títulos 700/800, texto 400/600/700, ajustáveis) e só o alfabeto latino,
 * guarda em uploads/flashsite-fonts/ e monta o CSS (e limpa a cache página a página). No site: pré-carrega a fonte do texto e a dos
 * títulos, escreve o CSS no <head> e desliga as fontes do Google do Elementor. Cada fonte ganha uma
 * reserva com as mesmas medidas (config/fonts/metrics.php). Desligar volta tudo ao que era.
 */
final class FontsModule implements ModuleInterface
{
    public const OPTION = 'flashsite_fonts';
    public const SLUG = 'flashsite-fonts';
    private const NONCE = 'flashsite_fonts_save';
    private const DIR = 'flashsite-fonts';
    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage'], 30);
        add_action('admin_post_' . self::NONCE, [$this, 'handleSave']);
        add_action('rest_api_init', [$this, 'registerRest']);
        add_action('wp_head', [$this, 'printHead'], 1);
        add_filter('elementor/frontend/print_google_fonts', [$this, 'googleFonts']);
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'fonts'; }

    /** @return array<string, mixed> */
    public static function state(): array
    {
        $state = get_option(self::OPTION, []);
        return is_array($state) ? $state : [];
    }

    public static function enabled(): bool
    {
        $state = self::state();
        return ! empty($state['enabled']) && ! empty($state['css']);
    }

    /** Com fontes locais, o Elementor não pede nada ao Google (nem Roboto). */
    public function googleFonts($print)
    {
        return self::enabled() ? false : $print;
    }

    public function printHead(): void
    {
        if (is_admin() || ! self::enabled()) {
            return;
        }
        $state = self::state();
        foreach (array_slice((array) ($state['preload'] ?? []), 0, 2) as $url) {
            printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url((string) $url));
        }
        echo '<style id="fs-fontes">' . wp_strip_all_tags((string) $state['css']) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /**
     * Fontes encontradas no site: variáveis do Elementor V4 (com o papel pela etiqueta) e tipografia do kit.
     *
     * @return array{variables: array<string, string>, families: array<string, string>}
     */
    public static function detect(): array
    {
        $kit = (int) get_option('elementor_active_kit');
        $variables = $kit > 0 ? FontPlan::fontVariables(get_post_meta($kit, '_elementor_global_variables', true)) : [];
        $families = [];
        foreach ($variables as $label => $family) {
            $role = FontPlan::roleFromLabel($label);
            if (! isset($families[$family]) || $role === FontPlan::ROLE_TITLE) {
                $families[$family] = $role;
            }
        }
        $settings = $kit > 0 ? get_post_meta($kit, '_elementor_page_settings', true) : [];
        foreach (['system_typography', 'custom_typography'] as $group) {
            foreach ((array) (is_array($settings) ? ($settings[$group] ?? []) : []) as $item) {
                $family = FontPlan::cleanFamily((string) ($item['typography_font_family'] ?? ''));
                if ($family !== '' && ! isset($families[$family])) {
                    $families[$family] = in_array($item['_id'] ?? '', ['primary', 'secondary'], true) ? FontPlan::ROLE_TITLE : FontPlan::ROLE_TEXT;
                }
            }
        }
        unset($families['Roboto'], $families['Roboto Slab']); // padrões do Elementor, só entram se o site os usar numa variável
        foreach ($variables as $family) {
            $families[$family] ??= FontPlan::ROLE_TEXT;
        }
        return ['variables' => $variables, 'families' => $families];
    }

    /**
     * Baixa as fontes e guarda o CSS. $weights: família => pesos (vazio = hierarquia padrão).
     *
     * @param array<string, list<int>> $weights
     * @return array{ok: bool, message: string}
     */
    public function prepare(array $weights = []): array
    {
        $found = self::detect();
        if ($found['families'] === []) {
            return ['ok' => false, 'message' => 'Não encontrei fontes nas variáveis do Elementor nem na tipografia do kit.'];
        }
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . self::DIR;
        $url = trailingslashit($upload['baseurl']) . self::DIR;
        if (! wp_mkdir_p($dir)) {
            return ['ok' => false, 'message' => 'Não foi possível criar a pasta das fontes em uploads.'];
        }
        $metrics = self::metrics();
        $families = [];
        $preload = [];
        $errors = [];
        foreach ($found['families'] as $family => $role) {
            $chosen = FontPlan::cleanWeights($weights[$family] ?? []) ?: FontPlan::DEFAULT_WEIGHTS[$role];
            $response = wp_remote_get(FontPlan::googleCssUrl($family, $chosen), ['timeout' => 20, 'user-agent' => self::UA]);
            $faces = is_wp_error($response) ? [] : FontPlan::parseGoogleCss((string) wp_remote_retrieve_body($response));
            if ($faces === []) {
                $errors[] = $family;
                continue;
            }
            $saved = [];
            foreach (FontPlan::groupFaces($faces) as $face) {
                $file = sanitize_title($family) . '-' . implode('-', $face['weights']) . '-' . substr(md5($face['url']), 0, 8) . '.woff2';
                $path = $dir . '/' . $file;
                if (! file_exists($path)) {
                    $font = wp_remote_get($face['url'], ['timeout' => 20, 'user-agent' => self::UA]);
                    $body = is_wp_error($font) ? '' : (string) wp_remote_retrieve_body($font);
                    if ($body === '' || file_put_contents($path, $body) === false) {
                        $errors[] = $family;
                        continue;
                    }
                }
                $saved[] = ['weights' => $face['weights'], 'style' => $face['style'], 'file_url' => $url . '/' . $file, 'range' => $face['range']];
            }
            if ($saved === []) {
                continue;
            }
            $families[$family] = ['role' => $role, 'weights' => $chosen, 'faces' => $saved];
            // Pré-carrega o ficheiro do peso principal: 400 no texto, o mais forte nos títulos.
            $main = $role === FontPlan::ROLE_TITLE ? max($chosen) : (in_array(400, $chosen, true) ? 400 : $chosen[0]);
            foreach ($saved as $face) {
                if (in_array($main, $face['weights'], true) || (count($face['weights']) > 1 && $main >= min($face['weights']) && $main <= max($face['weights']))) {
                    $preload[$role] ??= $face['file_url'];
                }
            }
        }
        if ($families === []) {
            return ['ok' => false, 'message' => 'Não foi possível baixar as fontes do Google: ' . implode(', ', array_unique($errors)) . '.'];
        }
        update_option(self::OPTION, [
            'enabled' => true,
            'families' => $families,
            'variables' => $found['variables'],
            'preload' => array_values(array_unique(array_filter([$preload[FontPlan::ROLE_TEXT] ?? '', $preload[FontPlan::ROLE_TITLE] ?? '']))),
            'css' => FontPlan::css($families, $found['variables'], $metrics),
            'updated' => gmdate('c'),
        ], true);
        self::purgePages();
        $message = 'Fontes preparadas: ' . implode(', ', array_keys($families)) . '.';
        return ['ok' => true, 'message' => $errors === [] ? $message : $message . ' Sem sucesso: ' . implode(', ', array_unique($errors)) . '.'];
    }

    public function disable(): void
    {
        $state = self::state();
        $state['enabled'] = false;
        update_option(self::OPTION, $state, true);
        self::purgePages();
    }

    /**
     * As fontes estão no <head> de todas as páginas: limpa a cache página a página (regra do processo:
     * nunca "limpar tudo" do LiteSpeed).
     */
    private static function purgePages(): void
    {
        $ids = get_posts(['post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'suppress_filters' => true]);
        foreach ((array) $ids as $id) {
            do_action('litespeed_purge_post', (int) $id);
        }
    }

    /** @return array<string, array{fallback: string, size_adjust: float, ascent: float, descent: float, line_gap: float}> */
    public static function metrics(): array
    {
        $file = FLASHSITE_CORE_PATH . 'config/fonts/metrics.php';
        $metrics = file_exists($file) ? require $file : [];
        return is_array($metrics) ? $metrics : [];
    }

    public function addPage(): void
    {
        add_submenu_page('flashsite-core', 'Fontes', 'Fontes', 'manage_options', self::SLUG, [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }
        $state = self::state();
        $found = self::detect();
        $metrics = self::metrics();
        $headerTitle = 'Fontes';
        $headerSubtitle = 'As fontes do site servidas pelo próprio site: carregam mais rápido e sem a troca de letra ao abrir a página.';
        $headerActions = [['label' => 'Ver o site', 'url' => home_url('/'), 'variant' => 'secondary']];
        echo '<div class="wrap flashsite-core-wrap">';
        include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php';
        if (isset($_GET['fs_msg'])) { // phpcs:ignore WordPress.Security.NonceVerification
            printf('<div class="notice notice-info is-dismissible"><p>%s</p></div>', esc_html(sanitize_text_field(wp_unslash((string) $_GET['fs_msg'])))); // phpcs:ignore WordPress.Security.NonceVerification
        }
        printf('<div class="fsc-card"><p><strong>Estado:</strong> %s</p>', self::enabled() ? 'ativas (desde ' . esc_html((string) ($state['updated'] ?? '')) . ')' : 'desligadas: o Elementor usa as fontes do Google');
        echo '<p class="description">Hierarquia padrão: títulos 700 e 800; texto 400, 600 e 700. Ajuste os pesos só se o layout pedir. Depois de mudar uma fonte no Elementor, prepare de novo.</p></div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::NONCE) . '">';
        wp_nonce_field(self::NONCE);
        echo '<div class="fsc-card"><table class="widefat striped"><thead><tr><th>Fonte</th><th>Papel</th><th>Pesos</th><th>Reserva</th></tr></thead><tbody>';
        if ($found['families'] === []) {
            echo '<tr><td colspan="4">Nenhuma fonte encontrada nas variáveis do Elementor.</td></tr>';
        }
        foreach ($found['families'] as $family => $role) {
            $current = (array) ($state['families'][$family]['weights'] ?? FontPlan::DEFAULT_WEIGHTS[$role]);
            $boxes = '';
            foreach (FontPlan::WEIGHTS as $weight) {
                $boxes .= sprintf('<label style="margin-right:8px"><input type="checkbox" name="fs_pesos[%s][]" value="%d"%s> %d</label>', esc_attr($family), $weight, in_array($weight, $current, true) ? ' checked' : '', $weight);
            }
            printf('<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html($family), $role === FontPlan::ROLE_TITLE ? 'Títulos' : 'Texto', $boxes, isset($metrics[$family]) ? esc_html($metrics[$family]['fallback'] . ' com medidas ajustadas') : 'Arial (sem ajuste de medidas)'); // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '</tbody></table><div class="fsc-card-actions">';
        submit_button('Preparar e ativar fontes locais', 'primary fsc-btn', 'fs_preparar', false);
        if (self::enabled()) {
            echo ' ';
            submit_button('Desligar (voltar às fontes do Google)', 'secondary', 'fs_desligar', false);
        }
        echo '</div></div></form></div>';
    }

    public function handleSave(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        if (isset($_POST['fs_desligar'])) {
            $this->disable();
            $message = 'Fontes locais desligadas.';
        } else {
            $weights = isset($_POST['fs_pesos']) && is_array($_POST['fs_pesos']) ? wp_unslash($_POST['fs_pesos']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            $message = $this->prepare(array_map(static fn ($w): array => (array) $w, (array) $weights))['message'];
        }
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'fs_msg' => rawurlencode($message)], admin_url('admin.php')));
        exit;
    }

    public function registerRest(): void
    {
        register_rest_route('flashsite/v1', '/fonts', [
            ['methods' => 'GET', 'callback' => fn () => ['enabled' => self::enabled(), 'detected' => self::detect(), 'state' => array_diff_key(self::state(), ['css' => 1])], 'permission_callback' => fn () => current_user_can('manage_options')],
            ['methods' => 'POST', 'callback' => [$this, 'restSave'], 'permission_callback' => fn () => current_user_can('manage_options')],
        ]);
    }

    /** @param \WP_REST_Request $request corpo: {"action": "prepare", "weights": {"Família": [400, 700]}} ou {"action": "disable"} */
    public function restSave($request): array
    {
        $in = (array) $request->get_json_params();
        if (($in['action'] ?? '') === 'disable') {
            $this->disable();
            return ['ok' => true, 'enabled' => false];
        }
        $result = $this->prepare((array) ($in['weights'] ?? []));
        return $result + ['enabled' => self::enabled(), 'state' => array_diff_key(self::state(), ['css' => 1])];
    }
}
