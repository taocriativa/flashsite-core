<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class PhoneLinkTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-phone-link'; }
    protected function getTagTitle(): string { return 'FlashSite: Phone Link'; }
    protected function getTagPath(): string { return '_flashsite.format.phone_link'; }
}
