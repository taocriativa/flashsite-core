<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class ProfSpecialtyTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-prof-specialty'; }
    protected function getTagTitle(): string { return 'FlashSite: Especialidade'; }
    protected function getTagPath(): string  { return 'professional.specialty'; }
}
