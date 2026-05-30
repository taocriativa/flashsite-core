<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class CityRegionTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-city-region'; }
    protected function getTagTitle(): string { return 'FlashSite: Cidade / Região'; }
    protected function getTagPath(): string { return 'location.city_region'; }
}
