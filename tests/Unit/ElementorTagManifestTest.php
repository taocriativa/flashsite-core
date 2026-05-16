<?php
declare(strict_types=1);

use FlashSite\Core\Modules\OutputFoundation\OutputFoundationModule;

final class ElementorTagManifestTest extends TestCase
{
    public function run(): void
    {
        $classes = OutputFoundationModule::getElementorTagClasses();

        $this->assertTrue(in_array('FlashSite\\Core\\Modules\\OutputFoundation\\Elementor\\BusinessNameTag', $classes, true), 'BusinessNameTag deve existir no manifesto.');
        $this->assertTrue(in_array('FlashSite\\Core\\Modules\\OutputFoundation\\Elementor\\AdminEmailTag', $classes, true), 'AdminEmailTag deve existir no manifesto.');
        $this->assertTrue(in_array('FlashSite\\Core\\Modules\\OutputFoundation\\Elementor\\WhatsAppLinkTag', $classes, true), 'WhatsAppLinkTag deve existir no manifesto.');
        $this->assertTrue(in_array('FlashSite\\Core\\Modules\\OutputFoundation\\Elementor\\CityRegionTag', $classes, true), 'CityRegionTag deve existir no manifesto.');
        $this->assertSame(count($classes), count(array_unique($classes)), 'Manifesto de tags não pode conter duplicados.');
        $this->assertTrue(count($classes) >= 15, 'Integração Elementor deve expor o conjunto esperado de tags.');
    }
}
