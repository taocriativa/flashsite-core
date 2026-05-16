<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class LogoDarkTag extends AbstractBusinessImageTag
{
    protected function getTagSlug(): string { return 'flashsite-business-logo-dark'; }
    protected function getTagTitle(): string { return 'FlashSite: Logo Dark'; }
    protected function getTagPath(): string { return 'branding.logo_dark_id'; }
}
