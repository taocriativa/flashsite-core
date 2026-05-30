<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursMondayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-monday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Segunda-feira'; }
    protected function getTagPath(): string  { return 'hours.monday'; }
}
