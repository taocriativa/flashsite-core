<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class BusinessNameTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-name'; }
    protected function getTagTitle(): string { return 'FlashSite: Business Name'; }
    protected function getTagPath(): string { return 'identity.business_name'; }
}
