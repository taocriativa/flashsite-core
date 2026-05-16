<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursFormattedTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-hours-formatted'; }
    protected function getTagTitle(): string { return 'FlashSite: Opening Hours'; }
    protected function getTagPath(): string { return '_flashsite.format.hours_formatted'; }
}
