<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class BusinessTypeTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-type'; }
    protected function getTagTitle(): string { return 'FlashSite: Tipo de Negócio'; }
    protected function getTagPath(): string { return 'identity.business_type'; }
}
