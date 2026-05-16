<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class InstagramUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-instagram'; }
    protected function getTagTitle(): string { return 'FlashSite: Instagram'; }
    protected function getTagPath(): string { return 'social.instagram'; }
}
