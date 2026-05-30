<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class SecondaryColorTag extends AbstractBusinessColorTag
{
    protected function getTagSlug(): string { return 'flashsite-business-secondary-color'; }
    protected function getTagTitle(): string { return 'FlashSite: Cor Secundária'; }
    protected function getTagPath(): string { return 'branding.secondary_color'; }
}
