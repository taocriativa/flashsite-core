<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class WhatsAppLinkTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-whatsapp-link'; }
    protected function getTagTitle(): string { return 'FlashSite: WhatsApp (Link)'; }
    protected function getTagPath(): string { return 'contact.whatsapp.link'; }

    /** @since 3.0.0 Mensagem pronta (?text=). Aceita {titulo}, {referencia} e {link} da página ou item atual. */
    protected function register_controls(): void
    {
        if (! class_exists('\\Elementor\\Controls_Manager')) {
            return;
        }
        $this->add_control('message', [
            'label' => 'Mensagem pronta',
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => '',
            'description' => 'Opcional. Pode usar {titulo}, {referencia} e {link}.',
        ]);
    }

    public function get_value(array $options = []): string
    {
        $url = parent::get_value($options);
        $message = trim((string) ($this->get_settings('message') ?? ''));
        if ($url === '' || $message === '') {
            return $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . 'text=' . rawurlencode(self::fillPlaceholders($message));
    }

    public static function fillPlaceholders(string $message): string
    {
        if (! str_contains($message, '{') || ! function_exists('get_the_ID')) {
            return $message;
        }
        $postId = (int) get_the_ID();
        $reference = '';
        if ($postId > 0) {
            foreach (['fs_referencia', 'fs_ref'] as $key) {
                $reference = trim((string) get_post_meta($postId, $key, true));
                if ($reference !== '') {
                    break;
                }
            }
        }
        $values = [
            '{titulo}' => $postId > 0 ? html_entity_decode((string) get_the_title($postId), ENT_QUOTES, 'UTF-8') : '',
            '{referencia}' => $reference,
            '{link}' => $postId > 0 ? (string) get_permalink($postId) : '',
        ];
        $text = strtr($message, $values);
        // Sem referência, "imóvel  no site" vira "imóvel no site".
        return trim((string) preg_replace('/\s{2,}/', ' ', $text));
    }
}
