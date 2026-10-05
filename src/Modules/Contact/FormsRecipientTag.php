<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Contact;

/**
 * "FlashSite: E-mail para pedidos" — destinatário da ação "E-mail" dos formulários.
 *
 * Devolve o e-mail interno (contact.email_admin) apenas durante o envio de um formulário
 * do Elementor (pedido AJAX *_forms_send_form). Em qualquer outro contexto devolve vazio:
 * colocada por engano num título ou texto, não mostra nada no site.
 *
 * @since 2.6.0
 */
final class FormsRecipientTag extends \Elementor\Core\DynamicTags\Tag
{
    /** @var (callable(): string)|null */
    private static $resolver = null;

    public static function setResolver(callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    public function get_name(): string { return 'flashsite-forms-recipient'; }
    public function get_title(): string { return 'FlashSite: E-mail para pedidos (só formulários)'; }
    public function get_group(): string { return 'flashsite'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render(): void
    {
        if (! ContactModule::isFormSubmission() || self::$resolver === null) {
            return;
        }
        echo esc_html((string) (self::$resolver)());
    }
}
