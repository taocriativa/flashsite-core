<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursThursdayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-thursday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Quinta-feira'; }
    protected function getTagPath(): string  { return 'hours.thursday'; }
}
