<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class DoctoraliaUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-doctoralia'; }
    protected function getTagTitle(): string { return 'FlashSite: Doctoralia'; }
    protected function getTagPath(): string { return 'social.doctoralia'; }
}
