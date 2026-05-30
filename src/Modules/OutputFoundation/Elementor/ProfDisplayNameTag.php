<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class ProfDisplayNameTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-prof-display-name'; }
    protected function getTagTitle(): string { return 'FlashSite: Nome de Exibição'; }
    protected function getTagPath(): string  { return 'professional.display_name'; }
}
