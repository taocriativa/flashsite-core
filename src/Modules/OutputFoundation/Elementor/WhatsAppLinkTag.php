<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class WhatsAppLinkTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-whatsapp-link'; }
    protected function getTagTitle(): string { return 'FlashSite: WhatsApp Link'; }
    protected function getTagPath(): string { return 'contact.whatsapp.link'; }
}
