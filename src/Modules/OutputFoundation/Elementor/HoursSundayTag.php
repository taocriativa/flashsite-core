<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursSundayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-sunday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Domingo'; }
    protected function getTagPath(): string  { return 'hours.sunday'; }
}
