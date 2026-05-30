<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class PhoneTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-phone'; }
    protected function getTagTitle(): string { return 'FlashSite: Telefone'; }
    protected function getTagPath(): string { return 'contact.phone'; }
}
