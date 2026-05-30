<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursSaturdayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-saturday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Sábado'; }
    protected function getTagPath(): string  { return 'hours.saturday'; }
}
