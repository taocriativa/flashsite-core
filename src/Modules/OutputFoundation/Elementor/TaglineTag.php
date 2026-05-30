<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class TaglineTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-tagline'; }
    protected function getTagTitle(): string { return 'FlashSite: Tagline / Slogan'; }
    protected function getTagPath(): string { return 'identity.tagline'; }
}
