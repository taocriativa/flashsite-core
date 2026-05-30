<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class ProfLicenseTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string  { return 'flashsite-prof-license'; }
    protected function getTagTitle(): string { return 'FlashSite: Registo Profissional (CRM/OAB/CAU)'; }
    protected function getTagPath(): string  { return 'professional.license'; }
}
