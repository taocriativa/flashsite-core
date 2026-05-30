<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class PrimaryColorTag extends AbstractBusinessColorTag
{
    protected function getTagSlug(): string { return 'flashsite-business-primary-color'; }
    protected function getTagTitle(): string { return 'FlashSite: Cor Principal'; }
    protected function getTagPath(): string { return 'branding.primary_color'; }
}
