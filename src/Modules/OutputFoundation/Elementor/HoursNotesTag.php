<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursNotesTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-notes'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Observação'; }
    protected function getTagPath(): string  { return 'hours.notes'; }
}
