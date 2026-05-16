<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class PostalCodeTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-postal-code'; }
    protected function getTagTitle(): string { return 'FlashSite: Postal Code'; }
    protected function getTagPath(): string { return 'location.postal_code'; }
}
