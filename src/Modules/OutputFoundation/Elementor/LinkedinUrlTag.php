<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class LinkedinUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-linkedin'; }
    protected function getTagTitle(): string { return 'FlashSite: LinkedIn'; }
    protected function getTagPath(): string { return 'social.linkedin'; }
}
