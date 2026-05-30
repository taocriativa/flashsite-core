<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class TaxIdTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-tax-id'; }
    protected function getTagTitle(): string { return 'FlashSite: NIF / CNPJ'; }
    protected function getTagPath(): string { return 'identity.tax_id'; }
}
