<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Hardening;

use FlashSite\Core\Core\Contracts\ModuleInterface;

/**
 * Segurança básica de todos os sites (sem painel; cada item desliga-se por filtro).
 *
 *   flashsite_core_hardening_headers   cabeçalhos no site público: nosniff, SAMEORIGIN,
 *                                      Referrer-Policy, Permissions-Policy e HSTS (só em HTTPS)
 *   flashsite_core_permissions_policy  valor do Permissions-Policy (padrão: camera=(), microphone=();
 *                                      sem payment, para não bloquear Apple Pay / Google Pay)
 *   flashsite_core_hardening_users     /wp/v2/users e ?author=N fechados a visitantes
 *                                      (com sessão ou senha de aplicação continuam a funcionar)
 *   flashsite_core_hardening_xmlrpc    XML-RPC desligado (fica ligado se o Jetpack estiver ativo)
 *   flashsite_core_security_txt        /.well-known/security.txt (contacto: flashsite_core_security_contact)
 *   flashsite_core_hidden_links        tira do HTML público os links ocultos (display:none ou
 *                                      visibility:hidden) para os domínios de
 *                                      flashsite_core_hidden_link_domains (padrão: full.services,
 *                                      que o plugin FULL.Cliente injeta no rodapé de todas as páginas)
 *
 * Ex.: add_filter('flashsite_core_hardening_xmlrpc', '__return_false');
 *
 * @since 2.6.0
 */
final class HardeningModule implements ModuleInterface
{
    public function register(): void
    {
        // Depois do tema, para que filtros em functions.php também contem.
        add_action('after_setup_theme', [$this, 'hook'], 20);
    }

    public function hook(): void
    {
        if (apply_filters('flashsite_core_hardening_headers', true)) {
            add_action('send_headers', [$this, 'sendHeaders']);
        }
        if (apply_filters('flashsite_core_hardening_users', true)) {
            add_filter('rest_endpoints', [$this, 'hideUserEndpoints']);
            add_action('template_redirect', [$this, 'blockAuthorScan'], 1);
        }
        // O Jetpack liga-se ao WordPress.com pelo XML-RPC: com ele ativo, o XML-RPC fica ligado.
        $jetpack = class_exists('Jetpack') || defined('JETPACK__VERSION');
        if (apply_filters('flashsite_core_hardening_xmlrpc', ! $jetpack)) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', [$this, 'removePingback']);
            add_filter('xmlrpc_methods', '__return_empty_array');
        }
        if (apply_filters('flashsite_core_security_txt', true)) {
            add_action('parse_request', [$this, 'securityTxt'], 0);
        }
        if (apply_filters('flashsite_core_hidden_links', true)) {
            // Último no template_redirect: o buffer apanha o HTML inteiro, incluindo o wp_footer.
            add_action('template_redirect', [$this, 'startHiddenLinkCleanup'], PHP_INT_MAX);
        }
    }

    public function boot(): void {}

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'hardening';
    }

    public function sendHeaders(): void
    {
        if (is_admin() || headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Sem payment nem geolocation: Apple Pay / Google Pay (WooPayments, Stripe) e mapas precisam deles.
        $policy = (string) apply_filters('flashsite_core_permissions_policy', 'camera=(), microphone=()');
        if ($policy !== '') {
            header('Permissions-Policy: ' . $policy);
        }
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    /**
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public function hideUserEndpoints(array $endpoints): array
    {
        if (is_user_logged_in()) {
            return $endpoints;
        }
        foreach (array_keys($endpoints) as $route) {
            if (str_starts_with((string) $route, '/wp/v2/users')) {
                unset($endpoints[$route]);
            }
        }
        return $endpoints;
    }

    public function blockAuthorScan(): void
    {
        if (is_user_logged_in()) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['author']) || is_author()) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public function removePingback(array $headers): array
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function startHiddenLinkCleanup(): void
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        $domains = (array) apply_filters('flashsite_core_hidden_link_domains', ['full.services']);
        if ($domains === []) {
            return;
        }
        ob_start(static fn (string $html): string => self::removeHiddenLinks($html, $domains));
    }

    /**
     * Tira os <a> que apontam para um dos domínios E estão escondidos por estilo inline.
     * Links visíveis para os mesmos domínios ficam intactos.
     *
     * @param array<int, string> $domains
     */
    public static function removeHiddenLinks(string $html, array $domains): string
    {
        $hosts = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain !== '' && stripos($html, $domain) !== false) {
                $hosts[] = preg_quote($domain, '#');
            }
        }
        if ($hosts === []) {
            return $html;
        }
        $pattern = '#<a\b'
            . '(?=[^>]*\bhref\s*=\s*["\']?https?://(?:[a-z0-9-]+\.)*(?:' . implode('|', $hosts) . ')(?:[/:?\#"\'\s>]|$))'
            . '(?=[^>]*\bstyle\s*=\s*["\'][^"\']*(?:display\s*:\s*none|visibility\s*:\s*hidden))'
            . '[^>]*>.*?</a>\s*#is';
        $clean = preg_replace($pattern, '', $html);
        return is_string($clean) ? $clean : $html;
    }

    public function securityTxt($wp): void
    {
        $path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $home = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($home !== '' && str_starts_with($path, $home . '/')) {
            $path = substr($path, strlen($home) + 1);
        }
        if ($path !== '.well-known/security.txt' && $path !== 'security.txt') {
            return;
        }
        $contact = (string) apply_filters('flashsite_core_security_contact', 'mailto:suporte@flashsite.pt');
        $expires = gmdate('Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS);
        $lines = [
            'Contact: ' . $contact,
            'Expires: ' . $expires,
            'Preferred-Languages: pt, en',
            'Canonical: ' . home_url('/.well-known/security.txt'),
        ];
        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo implode("\n", $lines) . "\n";
        exit;
    }
}
