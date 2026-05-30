<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class WhatsAppTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-whatsapp'; }
    protected function getTagTitle(): string { return 'FlashSite: WhatsApp (Número)'; }
    protected function getTagPath(): string { return 'contact.whatsapp.number'; }
}
