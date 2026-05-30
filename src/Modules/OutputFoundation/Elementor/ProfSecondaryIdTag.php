<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class ProfSecondaryIdTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-prof-secondary-id'; }
    protected function getTagTitle(): string { return 'FlashSite: Registo Secundário (RQE)'; }
    protected function getTagPath(): string  { return 'professional.secondary_id'; }
}
