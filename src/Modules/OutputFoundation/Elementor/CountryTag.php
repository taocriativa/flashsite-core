<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class CountryTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-country'; }
    protected function getTagTitle(): string { return 'FlashSite: País'; }
    protected function getTagPath(): string { return 'location.country'; }
}
