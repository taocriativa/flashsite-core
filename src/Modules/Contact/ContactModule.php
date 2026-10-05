<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Contact;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Business\BusinessData;

/**
 * FlashSite › WhatsApp e formulários.
 *
 * 1. Formulários → WhatsApp: depois de um envio com sucesso (formulário atómico do Elementor 4
 *    ou formulário clássico do Elementor Pro), a mesma aba abre o WhatsApp do negócio com o
 *    resumo dos campos preenchidos. Os campos são lidos sozinhos (rótulo: valor); não há nada
 *    a configurar por formulário. Navegação na aba atual: nunca é bloqueada como pop-up.
 * 2. Formulários → e-mail: ação nativa "E-mail" do formulário, com o destinatário na tag
 *    "FlashSite: E-mail para pedidos" (contact.email_admin). A tag só devolve o e-mail durante
 *    o envio do formulário, por isso nunca aparece no HTML do site.
 * 3. Botão flutuante de WhatsApp (opcional), com as cores do site (variáveis fs-*).
 *
 * @since 2.6.0
 */
final class ContactModule implements ModuleInterface
{
    public const OPTION = 'flashsite_contact_settings';
    public const CAP = 'flashsite_manage_business_data';
    private const NONCE = 'flashsite_contact_save';

    public const DEFAULTS = [
        'forms_whatsapp' => 'all',      // all | marked (só formulários com a classe fs-form-whatsapp) | off
        'forms_intro' => 'Olá! Acabei de enviar este pedido pelo site:',
        'float_enabled' => '0',
        'float_position' => 'right',    // right | left
        'float_where' => 'all',         // all | home | not_home
        'float_device' => 'all',        // all | mobile | desktop
        'float_style' => 'brand',       // brand (fs-cor-destaque) | whatsapp (verde)
        'float_label' => '',
        'float_message' => 'Olá! Vim pelo site e gostava de mais informações.',
    ];

    private const CHOICES = [
        'forms_whatsapp' => ['all', 'marked', 'off'],
        'float_position' => ['right', 'left'],
        'float_where' => ['all', 'home', 'not_home'],
        'float_device' => ['all', 'mobile', 'desktop'],
        'float_style' => ['brand', 'whatsapp'],
    ];

    public function __construct(private ?BusinessData $business = null) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu'], 25);
        add_action('admin_post_flashsite_contact_save', [$this, 'handleSave']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
        add_action('wp_footer', [$this, 'renderFloatButton'], 20);
        add_action('elementor/dynamic_tags/register', [$this, 'registerTag'], 20);
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'contact'; }

    /** @return array<string, string> */
    public static function settings(): array
    {
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        return array_merge(self::DEFAULTS, array_intersect_key(array_map('strval', $saved), self::DEFAULTS));
    }

    /** Número do WhatsApp do negócio só com dígitos (com indicativo). */
    public function whatsappDigits(): string
    {
        if ($this->business === null) {
            return '';
        }
        $number = preg_replace('/\D+/', '', (string) $this->business->get('contact.whatsapp.number', '')) ?: '';
        if ($number === '') {
            $link = (string) $this->business->get('contact.whatsapp.link', '');
            if (preg_match('/(?:wa\.me\/|phone=)(\d{8,15})/', $link, $m)) {
                $number = $m[1];
            }
        }
        if ($number !== '' && strlen($number) === 9 && str_starts_with($number, '9')) {
            $number = '351' . $number; // número português sem indicativo
        }
        return $number;
    }

    /** E-mail que recebe os pedidos: contact.email_admin, senão o e-mail público, senão o do WordPress. */
    public function recipientEmail(): string
    {
        $email = $this->business !== null ? trim((string) $this->business->get('contact.email_admin', '')) : '';
        if ($email === '' && $this->business !== null) {
            $email = trim((string) $this->business->get('contact.email_public', ''));
        }
        return is_email($email) ? $email : (string) get_option('admin_email');
    }

    // ── Frontend ─────────────────────────────────────────────────────────────

    public function enqueue(): void
    {
        if (is_admin() || isset($_GET['elementor-preview'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        $s = self::settings();
        $digits = $this->whatsappDigits();
        if ($digits === '') {
            return;
        }
        if ($s['forms_whatsapp'] !== 'off') {
            wp_enqueue_script('flashsite-core-forms-whatsapp', FLASHSITE_CORE_URL . 'assets/frontend/js/fsc-forms-whatsapp.js', [], FLASHSITE_CORE_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
            wp_localize_script('flashsite-core-forms-whatsapp', 'flashsiteFormsWhatsapp', [
                'number' => $digits,
                'mode' => $s['forms_whatsapp'],
                'intro' => $s['forms_intro'],
            ]);
        }
        if ($this->showFloat($s)) {
            wp_enqueue_style('flashsite-core-whatsapp-float', FLASHSITE_CORE_URL . 'assets/frontend/css/fsc-whatsapp-float.css', [], FLASHSITE_CORE_VERSION);
        }
    }

    /** @param array<string, string> $s */
    private function showFloat(array $s): bool
    {
        if ($s['float_enabled'] !== '1' || $this->whatsappDigits() === '') {
            return false;
        }
        $home = is_front_page();
        return match ($s['float_where']) {
            'home' => $home,
            'not_home' => ! $home,
            default => true,
        };
    }

    public function renderFloatButton(): void
    {
        if (is_admin() || isset($_GET['elementor-preview'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        $s = self::settings();
        if (! $this->showFloat($s)) {
            return;
        }
        $url = 'https://wa.me/' . $this->whatsappDigits() . ($s['float_message'] !== '' ? '?text=' . rawurlencode($s['float_message']) : '');
        $label = trim($s['float_label']);
        printf(
            '<a class="fs-wa-float fs-wa-float--%1$s fs-wa-float--%2$s fs-wa-float--%3$s%4$s" href="%5$s" target="_blank" rel="noopener" aria-label="%6$s">'
            . '<svg class="fs-wa-float__icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3C8.86 3 3.04 8.82 3.04 16c0 2.3.6 4.53 1.74 6.5L3 29l6.68-1.75A12.94 12.94 0 0 0 16.04 29C23.22 29 29 23.18 29 16S23.22 3 16.04 3Zm0 23.6c-2.02 0-4-.54-5.72-1.57l-.41-.24-3.96 1.04 1.06-3.86-.27-.4A10.56 10.56 0 0 1 5.4 16c0-5.86 4.77-10.62 10.64-10.62 5.86 0 10.6 4.76 10.6 10.62 0 5.85-4.74 10.6-10.6 10.6Zm5.83-7.95c-.32-.16-1.89-.93-2.18-1.04-.29-.1-.5-.16-.72.16-.21.32-.82 1.04-1 1.25-.19.21-.37.24-.69.08-.32-.16-1.35-.5-2.57-1.59-.95-.85-1.59-1.9-1.78-2.22-.19-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.18.21-.32.32-.53.1-.21.05-.4-.03-.56-.08-.16-.72-1.73-.98-2.37-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.65s1.14 3.08 1.3 3.29c.16.21 2.24 3.42 5.43 4.8.76.33 1.35.52 1.81.67.76.24 1.45.21 2 .13.61-.09 1.89-.77 2.15-1.52.27-.75.27-1.39.19-1.52-.08-.13-.29-.21-.61-.37Z"/></svg>%7$s</a>',
            esc_attr($s['float_position']),
            esc_attr($s['float_style']),
            esc_attr($s['float_device']),
            $label !== '' ? ' fs-wa-float--label' : '',
            esc_url($url),
            esc_attr($label !== '' ? $label : 'Falar no WhatsApp'),
            $label !== '' ? '<span class="fs-wa-float__label">' . esc_html($label) . '</span>' : ''
        );
    }

    // ── Tag do destinatário (só no envio) ───────────────────────────────────

    public function registerTag($dynamicTags): void
    {
        if (! class_exists('\\Elementor\\Core\\DynamicTags\\Tag') || ! is_object($dynamicTags) || ! method_exists($dynamicTags, 'register')) {
            return;
        }
        require_once __DIR__ . '/FormsRecipientTag.php';
        FormsRecipientTag::setResolver(fn (): string => $this->recipientEmail());
        $dynamicTags->register(new FormsRecipientTag());
    }

    // ── Admin ────────────────────────────────────────────────────────────────

    public function addMenu(): void
    {
        add_submenu_page('flashsite-core', 'WhatsApp e formulários', 'WhatsApp e formulários', self::CAP, 'flashsite-contact', [$this, 'renderPage']);
    }

    public function renderPage(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die('Sem permissão.');
        }
        $s = self::settings();
        $digits = $this->whatsappDigits();
        $saved = isset($_GET['fs_saved']); // phpcs:ignore WordPress.Security.NonceVerification
        echo '<div class="wrap flashsite-admin"><h1>WhatsApp e formulários</h1>';
        if ($saved) {
            echo '<div class="notice notice-success is-dismissible"><p>Guardado.</p></div>';
        }
        if ($digits === '') {
            printf('<div class="notice notice-warning"><p>Falta o número de WhatsApp em <a href="%s">Dados do Negócio</a>. Sem ele, nada desta página funciona.</p></div>', esc_url(admin_url('admin.php?page=flashsite-business-data')));
        } else {
            printf('<p>WhatsApp do negócio: <strong>+%s</strong> · E-mail que recebe os pedidos: <strong>%s</strong> (<a href="%s">alterar em Dados do Negócio</a>)</p>', esc_html($digits), esc_html($this->recipientEmail()), esc_url(admin_url('admin.php?page=flashsite-business-data')));
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="flashsite_contact_save">';
        wp_nonce_field(self::NONCE);

        echo '<h2>1. Formulários</h2>';
        echo '<p>Depois de enviar, o visitante vê o WhatsApp a abrir com o resumo do que preencheu, pronto a enviar para o seu número. O e-mail com o mesmo resumo é enviado pelo próprio formulário.</p>';
        echo '<table class="form-table" role="presentation">';
        $this->selectRow('forms_whatsapp', 'Abrir o WhatsApp depois do envio', ['all' => 'Em todos os formulários', 'marked' => 'Só nos formulários marcados (classe fs-form-whatsapp)', 'off' => 'Desligado'], $s);
        printf('<tr><th scope="row"><label for="forms_intro">Primeira linha da mensagem</label></th><td><input type="text" class="large-text" id="forms_intro" name="forms_intro" value="%s"></td></tr>', esc_attr($s['forms_intro']));
        echo '</table>';

        echo '<h2>2. Botão flutuante de WhatsApp</h2>';
        echo '<table class="form-table" role="presentation">';
        printf('<tr><th scope="row">Mostrar o botão</th><td><label><input type="checkbox" name="float_enabled" value="1"%s> Ligado</label></td></tr>', checked($s['float_enabled'], '1', false));
        $this->selectRow('float_position', 'Posição', ['right' => 'Canto inferior direito', 'left' => 'Canto inferior esquerdo'], $s);
        $this->selectRow('float_where', 'Páginas', ['all' => 'Todo o site', 'home' => 'Só na página inicial', 'not_home' => 'Todo o site menos a inicial'], $s);
        $this->selectRow('float_device', 'Dispositivos', ['all' => 'Todos', 'mobile' => 'Só telemóvel e tablet', 'desktop' => 'Só computador'], $s);
        $this->selectRow('float_style', 'Cor', ['brand' => 'Cor de destaque do site', 'whatsapp' => 'Verde do WhatsApp'], $s);
        printf('<tr><th scope="row"><label for="float_label">Texto ao lado do ícone</label></th><td><input type="text" class="regular-text" id="float_label" name="float_label" value="%s" placeholder="(vazio: só o ícone)"></td></tr>', esc_attr($s['float_label']));
        printf('<tr><th scope="row"><label for="float_message">Mensagem inicial</label></th><td><input type="text" class="large-text" id="float_message" name="float_message" value="%s"></td></tr>', esc_attr($s['float_message']));
        echo '</table>';
        submit_button('Guardar');
        echo '</form>';

        echo '<h2>Como chegam os e-mails</h2><p>Os e-mails saem do servidor do site. Para não caírem no spam, é preciso ligar o envio a uma conta de e-mail (SMTP): o e-mail do domínio do cliente ou uma conta Gmail com palavra-passe de aplicação. É uma configuração única, feita na entrega.</p>';
        echo '</div>';
    }

    /** @param array<string, string> $options @param array<string, string> $s */
    private function selectRow(string $key, string $label, array $options, array $s): void
    {
        printf('<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><select id="%1$s" name="%1$s">', esc_attr($key), esc_html($label));
        foreach ($options as $value => $text) {
            printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($s[$key], $value, false), esc_html($text));
        }
        echo '</select></td></tr>';
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
            if ($key === 'float_enabled') {
                $value = $value === '1' ? '1' : '0';
            } elseif (isset(self::CHOICES[$key]) && ! in_array($value, self::CHOICES[$key], true)) {
                $value = $default;
            }
            $out[$key] = $value;
        }
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg('fs_saved', '1', admin_url('admin.php?page=flashsite-contact')));
        exit;
    }
}
