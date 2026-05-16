<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class FacebookUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-facebook'; }
    protected function getTagTitle(): string { return 'FlashSite: Facebook'; }
    protected function getTagPath(): string { return 'social.facebook'; }
}
