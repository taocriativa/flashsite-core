<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class GoogleMapsUrlTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-google-maps-url'; }
    protected function getTagTitle(): string { return 'FlashSite: Google Maps (Link)'; }
    protected function getTagPath(): string { return 'location.google_maps_url'; }
}
