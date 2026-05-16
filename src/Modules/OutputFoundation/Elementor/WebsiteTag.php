<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class WebsiteTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-website'; }
    protected function getTagTitle(): string { return 'FlashSite: Website'; }
    protected function getTagPath(): string { return 'social.website_url'; }
}
