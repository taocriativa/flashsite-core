<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class AddressTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-address'; }
    protected function getTagTitle(): string { return 'FlashSite: Endereço'; }
    protected function getTagPath(): string { return 'location.address'; }
}
