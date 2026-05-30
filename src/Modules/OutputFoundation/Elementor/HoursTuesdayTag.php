<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursTuesdayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-tuesday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Terça-feira'; }
    protected function getTagPath(): string  { return 'hours.tuesday'; }
}
