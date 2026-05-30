<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class FullAddressTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-full-address'; }
    protected function getTagTitle(): string { return 'FlashSite: Endereço Completo'; }
    protected function getTagPath(): string { return '_flashsite.format.full_address'; }
}
