<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Markets;

use FlashSite\Core\Core\Contracts\ModuleInterface;

/**
 * FlashSite › Mercados do site: a mesma página em versões para Portugal e para o Brasil
 * (ex.: flashsite.pt/ e flashsite.pt/br/), sem plugin de tradução.
 *
 *   hreflang      cada página de um par aponta para si e para a outra (pt-PT, pt-BR, x-default)
 *   seletor       shortcode [flashsite_market_switch] (ex.: no cabeçalho)
 *   aviso         o navegador do visitante (idioma e fuso horário, sem IP) sugere a outra versão;
 *                 nunca redireciona. Feito em JavaScript, por isso não mexe no HTML em cache.
 *   escolha       cookie flashsite_region (PT | BR), 180 dias, com prioridade sobre a deteção
 *   medição       eventos region_prompt_shown e region_switch no dataLayer, se existir
 *
 * Ordem de decisão no navegador: URL do mercado > escolha guardada > deteção > nada.
 *
 * @since 3.0.0
 */
final class MarketsModule implements ModuleInterface
{
    public const OPTION = 'flashsite_markets_settings';
    public const CAP = 'flashsite_manage_business_data';
    public const COOKIE = 'flashsite_region';
    public const MAX_PAIRS = 5;
    private const NONCE = 'flashsite_markets_save';

    public const MARKETS = [
        'PT' => ['label' => 'Portugal', 'flag' => '🇵🇹', 'hreflang' => 'pt-PT'],
        'BR' => ['label' => 'Brasil', 'flag' => '🇧🇷', 'hreflang' => 'pt-BR'],
    ];

    public const DEFAULTS = [
        'enabled' => '0',
        'default_market' => 'PT',
        'prompt_br' => '1',
        'prompt_pt' => '0',
        'br_title' => 'Está no Brasil?',
        'br_text' => 'Veja preços e condições em reais.',
        'br_go' => 'Ver versão Brasil',
        'br_stay' => 'Continuar em Portugal',
        'pt_title' => 'Está em Portugal?',
        'pt_text' => 'Veja preços e condições em euros.',
        'pt_go' => 'Ver versão Portugal',
        'pt_stay' => 'Continuar no Brasil',
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu'], 26);
        add_action('admin_post_flashsite_markets_save', [$this, 'handleSave']);
        add_action('wp_head', [$this, 'printHreflang'], 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
        add_shortcode('flashsite_market_switch', [$this, 'renderSwitch']);
        add_action('rest_api_init', [$this, 'registerRest']);
    }

    /** GET/POST flashsite/v1/markets (só administradores): ler e gravar a configuração, ex.: pelo MCP. */
    public function registerRest(): void
    {
        register_rest_route('flashsite/v1', '/markets', [
            ['methods' => 'GET', 'callback' => fn () => self::config(), 'permission_callback' => fn () => current_user_can('manage_options')],
            ['methods' => 'POST', 'callback' => [$this, 'restSave'], 'permission_callback' => fn () => current_user_can('manage_options')],
        ]);
    }

    /** @param \WP_REST_Request $request */
    public function restSave($request): array
    {
        $in = (array) $request->get_json_params();
        $current = self::config();
        $out = $current['settings'];
        foreach (self::DEFAULTS as $key => $default) {
            if (! array_key_exists($key, $in)) {
                continue;
            }
            $value = sanitize_text_field((string) $in[$key]);
            if (in_array($key, ['enabled', 'prompt_br', 'prompt_pt'], true)) {
                $value = $value === '1' ? '1' : '0';
            } elseif ($key === 'default_market') {
                $value = isset(self::MARKETS[$value]) ? $value : 'PT';
            } elseif ($value === '') {
                $value = $default;
            }
            $out[$key] = $value;
        }
        $pairs = $current['pairs'];
        if (isset($in['pairs']) && is_array($in['pairs'])) {
            $pairs = [];
            foreach ($in['pairs'] as $pair) {
                $pt = absint($pair['PT'] ?? 0);
                $br = absint($pair['BR'] ?? 0);
                if ($pt > 0 && $br > 0 && $pt !== $br) {
                    $pairs[] = ['PT' => $pt, 'BR' => $br];
                }
            }
        }
        $out['pairs'] = array_slice($pairs, 0, self::MAX_PAIRS);
        update_option(self::OPTION, $out, false);
        return self::config();
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'markets'; }

    /** @return array{settings: array<string, string>, pairs: list<array{PT: int, BR: int}>} */
    public static function config(): array
    {
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        $settings = array_merge(self::DEFAULTS, array_intersect_key(array_map('strval', array_filter($saved, 'is_scalar')), self::DEFAULTS));
        $pairs = [];
        foreach ((array) ($saved['pairs'] ?? []) as $pair) {
            $pt = (int) ($pair['PT'] ?? 0);
            $br = (int) ($pair['BR'] ?? 0);
            if ($pt > 0 && $br > 0 && $pt !== $br) {
                $pairs[] = ['PT' => $pt, 'BR' => $br];
            }
        }
        return ['settings' => $settings, 'pairs' => array_slice($pairs, 0, self::MAX_PAIRS)];
    }

    /**
     * Mercado da página e as suas versões. Puro (testável).
     *
     * @param list<array{PT: int, BR: int}> $pairs
     * @return array{market: string, pages: array{PT: int, BR: int}}|null
     */
    public static function pairFor(array $pairs, int $pageId): ?array
    {
        if ($pageId <= 0) {
            return null;
        }
        foreach ($pairs as $pair) {
            foreach (['PT', 'BR'] as $market) {
                if ((int) $pair[$market] === $pageId) {
                    return ['market' => $market, 'pages' => ['PT' => (int) $pair['PT'], 'BR' => (int) $pair['BR']]];
                }
            }
        }
        return null;
    }

    /**
     * Linhas hreflang de um par. Puro (testável).
     *
     * @param array{PT: string, BR: string} $urls
     * @return list<array{hreflang: string, href: string}>
     */
    public static function hreflangLinks(array $urls, string $defaultMarket): array
    {
        $links = [];
        foreach (self::MARKETS as $market => $info) {
            if (($urls[$market] ?? '') !== '') {
                $links[] = ['hreflang' => $info['hreflang'], 'href' => $urls[$market]];
            }
        }
        $default = $urls[$defaultMarket] ?? '';
        if (count($links) === 2 && $default !== '') {
            $links[] = ['hreflang' => 'x-default', 'href' => $default];
        }
        return count($links) >= 2 ? $links : [];
    }

    /** Par da página atual com os dois lados publicados; null se não houver. */
    private function currentPair(): ?array
    {
        $config = self::config();
        if ($config['settings']['enabled'] !== '1' || ! is_singular()) {
            return null;
        }
        $pair = self::pairFor($config['pairs'], (int) get_queried_object_id());
        if ($pair === null) {
            return null;
        }
        $urls = [];
        foreach ($pair['pages'] as $market => $id) {
            if (get_post_status($id) !== 'publish') {
                return null;
            }
            $urls[$market] = (string) get_permalink($id);
        }
        return $pair + ['urls' => $urls, 'settings' => $config['settings']];
    }

    public function printHreflang(): void
    {
        $pair = $this->currentPair();
        if ($pair === null) {
            return;
        }
        foreach (self::hreflangLinks($pair['urls'], $pair['settings']['default_market']) as $link) {
            printf('<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr($link['hreflang']), esc_url($link['href']));
        }
    }

    public function enqueue(): void
    {
        if (is_admin() || isset($_GET['elementor-preview'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        $pair = $this->currentPair();
        if ($pair === null) {
            return;
        }
        $s = $pair['settings'];
        $other = $pair['market'] === 'PT' ? 'BR' : 'PT';
        $key = strtolower($other);
        wp_enqueue_script('flashsite-core-markets', FLASHSITE_CORE_URL . 'assets/frontend/js/fsc-markets.js', [], FLASHSITE_CORE_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
        wp_add_inline_script('flashsite-core-markets', 'window.fscMarkets=' . wp_json_encode([
            'market' => $pair['market'],
            'urls' => $pair['urls'],
            'cookie' => self::COOKIE,
            'prompt' => $s['prompt_' . $key] === '1',
            'texts' => [
                'title' => $s[$key . '_title'],
                'text' => $s[$key . '_text'],
                'go' => $s[$key . '_go'],
                'stay' => $s[$key . '_stay'],
                'flag' => self::MARKETS[$other]['flag'],
            ],
        ]) . ';', 'before');
    }

    /** [flashsite_market_switch] → "🇵🇹 Portugal · 🇧🇷 Brasil", com o mercado atual marcado. */
    public function renderSwitch(array|string $atts = []): string
    {
        $pair = $this->currentPair();
        $config = self::config();
        if ($config['settings']['enabled'] !== '1') {
            return '';
        }
        $urls = $pair['urls'] ?? [];
        if ($urls === [] && $config['pairs'] !== []) {
            // Página sem par (ex.: /demos/): leva às páginas do primeiro par.
            foreach ($config['pairs'][0] as $market => $id) {
                $urls[$market] = get_post_status($id) === 'publish' ? (string) get_permalink($id) : '';
            }
        }
        $current = $pair['market'] ?? '';
        $parts = [];
        foreach (self::MARKETS as $market => $info) {
            if (($urls[$market] ?? '') === '') {
                continue;
            }
            $parts[] = sprintf(
                '<a class="fs-market-switch__link%s" href="%s" hreflang="%s" data-fs-market="%s"%s>%s %s</a>',
                $market === $current ? ' is-current' : '',
                esc_url($urls[$market]),
                esc_attr($info['hreflang']),
                esc_attr($market),
                $market === $current ? ' aria-current="page"' : '',
                $info['flag'],
                esc_html($info['label'])
            );
        }
        if (count($parts) < 2) {
            return '';
        }
        static $styled = false;
        $style = '';
        if (! $styled) {
            $styled = true;
            // Herda cor e fonte do sítio onde for colocado (ex.: cabeçalho escuro ou claro).
            $style = '<style>.fs-market-switch{display:inline-flex;align-items:center;gap:8px;font-size:14px;white-space:nowrap}'
                . '.fs-market-switch__link{color:inherit;text-decoration:none;opacity:.7}.fs-market-switch__link:hover,.fs-market-switch__link.is-current{opacity:1}'
                . '.fs-market-switch__link.is-current{font-weight:700}.fs-market-switch__sep{opacity:.5}</style>';
        }
        return $style . '<nav class="fs-market-switch" aria-label="País">' . implode('<span class="fs-market-switch__sep" aria-hidden="true">·</span>', $parts) . '</nav>';
    }

    // ── Admin ────────────────────────────────────────────────────────────────

    public function addMenu(): void
    {
        add_submenu_page('flashsite-core', 'Mercados do site', 'Mercados do site', self::CAP, 'flashsite-markets', [$this, 'renderPage']);
    }

    public function renderPage(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die('Sem permissão.');
        }
        $config = self::config();
        $s = $config['settings'];
        $pages = get_pages(['post_status' => 'publish,draft', 'sort_column' => 'post_title']);
        $headerTitle = 'Mercados do site';
        $headerSubtitle = 'A mesma página em versão Portugal e versão Brasil, com o país certo para o Google e um aviso discreto para quem chega do outro país.';
        $headerActions = [['label' => 'Voltar ao dashboard', 'url' => admin_url('admin.php?page=flashsite-core'), 'variant' => 'secondary']];
        echo '<div class="wrap flashsite-core-wrap">';
        include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php';
        if (isset($_GET['fs_saved'])) { // phpcs:ignore WordPress.Security.NonceVerification
            echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="flashsite_markets_save">';
        wp_nonce_field(self::NONCE);
        echo '<div class="fsc-card-grid" style="grid-template-columns:1fr;">';

        echo '<div class="fsc-card"><h2>1. Ligar</h2><table class="form-table" role="presentation">';
        printf('<tr><th scope="row">Mercados do site</th><td><label><input type="checkbox" name="enabled" value="1"%s> Ligado</label></td></tr>', checked($s['enabled'], '1', false));
        printf('<tr><th scope="row"><label for="default_market">Versão principal</label></th><td><select id="default_market" name="default_market"><option value="PT"%s>Portugal</option><option value="BR"%s>Brasil</option></select><p class="description">A que o Google mostra a quem não é de nenhum dos dois países (x-default).</p></td></tr>', selected($s['default_market'], 'PT', false), selected($s['default_market'], 'BR', false));
        echo '</table></div>';

        echo '<div class="fsc-card"><h2>2. Páginas</h2><p>Escolha as páginas que são a mesma página nos dois países. As restantes (exemplos, política, etc.) ficam iguais para todos. Só funciona quando as duas páginas do par estão publicadas.</p><table class="form-table" role="presentation">';
        for ($i = 0; $i < self::MAX_PAIRS; $i++) {
            $pair = $config['pairs'][$i] ?? ['PT' => 0, 'BR' => 0];
            echo '<tr><th scope="row">Par ' . ($i + 1) . '</th><td>';
            foreach (['PT', 'BR'] as $market) {
                printf('<label style="margin-right:12px">%s %s <select name="pairs[%d][%s]"><option value="0">—</option>', self::MARKETS[$market]['flag'], esc_html(self::MARKETS[$market]['label']), $i, $market);
                foreach ($pages as $page) {
                    printf('<option value="%d"%s>%s%s</option>', (int) $page->ID, selected((int) $pair[$market], (int) $page->ID, false), esc_html($page->post_title ?: '(sem título)'), $page->post_status !== 'publish' ? ' (rascunho)' : '');
                }
                echo '</select></label>';
            }
            echo '</td></tr>';
        }
        echo '</table></div>';

        echo '<div class="fsc-card"><h2>3. Aviso para quem chega do outro país</h2><p>O navegador do visitante (idioma e fuso horário) indica o país. O aviso só sugere: nunca muda de página sozinho, e a escolha fica guardada.</p><table class="form-table" role="presentation">';
        foreach (['br' => 'Na versão Portugal, para visitantes do Brasil', 'pt' => 'Na versão Brasil, para visitantes de Portugal'] as $key => $label) {
            printf('<tr><th scope="row">%s</th><td><label><input type="checkbox" name="prompt_%s" value="1"%s> Mostrar</label>', esc_html($label), $key, checked($s['prompt_' . $key], '1', false));
            foreach (['title' => 'Título', 'text' => 'Texto', 'go' => 'Botão para mudar', 'stay' => 'Botão para ficar'] as $field => $fieldLabel) {
                printf('<p><label>%s<br><input type="text" class="regular-text" name="%s_%s" value="%s"></label></p>', esc_html($fieldLabel), $key, $field, esc_attr($s[$key . '_' . $field]));
            }
            echo '</td></tr>';
        }
        echo '</table></div>';

        echo '<div class="fsc-card fsc-card--soft"><h2>Seletor de país</h2><p>Para pôr no cabeçalho, use o shortcode <code>[flashsite_market_switch]</code> (widget Shortcode). Mostra 🇵🇹 Portugal · 🇧🇷 Brasil e guarda a escolha.</p></div>';
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
        $in = wp_unslash($_POST);
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = isset($in[$key]) ? sanitize_text_field((string) $in[$key]) : '';
            if (in_array($key, ['enabled', 'prompt_br', 'prompt_pt'], true)) {
                $value = $value === '1' ? '1' : '0';
            } elseif ($key === 'default_market') {
                $value = isset(self::MARKETS[$value]) ? $value : 'PT';
            } elseif ($value === '') {
                $value = $default;
            }
            $out[$key] = $value;
        }
        $pairs = [];
        foreach ((array) ($in['pairs'] ?? []) as $pair) {
            $pt = absint($pair['PT'] ?? 0);
            $br = absint($pair['BR'] ?? 0);
            if ($pt > 0 && $br > 0 && $pt !== $br) {
                $pairs[] = ['PT' => $pt, 'BR' => $br];
            }
        }
        $out['pairs'] = array_slice($pairs, 0, self::MAX_PAIRS);
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg('fs_saved', '1', admin_url('admin.php?page=flashsite-markets')));
        exit;
    }
}
