<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursFridayTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-hours-friday'; }
    protected function getTagTitle(): string { return 'FlashSite: Horário — Sexta-feira'; }
    protected function getTagPath(): string  { return 'hours.friday'; }
}
