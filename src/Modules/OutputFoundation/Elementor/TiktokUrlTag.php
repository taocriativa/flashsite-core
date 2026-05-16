<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class TiktokUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-tiktok'; }
    protected function getTagTitle(): string { return 'FlashSite: TikTok'; }
    protected function getTagPath(): string { return 'social.tiktok'; }
}
