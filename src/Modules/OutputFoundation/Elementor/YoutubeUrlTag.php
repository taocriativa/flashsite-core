<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class YoutubeUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-youtube'; }
    protected function getTagTitle(): string { return 'FlashSite: YouTube'; }
    protected function getTagPath(): string { return 'social.youtube'; }
}
