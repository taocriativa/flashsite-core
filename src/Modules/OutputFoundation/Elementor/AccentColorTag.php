<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class AccentColorTag extends AbstractBusinessColorTag
{
    protected function getTagSlug(): string { return 'flashsite-business-accent-color'; }
    protected function getTagTitle(): string { return 'FlashSite: Accent Color'; }
    protected function getTagPath(): string { return 'branding.accent_color'; }
}
