<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class LogoLightTag extends AbstractBusinessImageTag
{
    protected function getTagSlug(): string { return 'flashsite-business-logo-light'; }
    protected function getTagTitle(): string { return 'FlashSite: Logo Light'; }
    protected function getTagPath(): string { return 'branding.logo_light_id'; }
}
