<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class ProfTitleTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-prof-title'; }
    protected function getTagTitle(): string { return 'FlashSite: Título Profissional'; }
    protected function getTagPath(): string  { return 'professional.title'; }
}
