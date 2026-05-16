<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\OutputFoundation\OutputFoundationModule;

final class OutputFoundationShortcodeTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile([
            'identity' => [
                'business_name' => 'Flash Site',
                'business_type' => 'Studio',
                'tagline' => 'Sites rápidos',
                'tax_id' => '123456789',
            ],
            'contact' => [
                'phone' => '+351 915 436 088',
                'whatsapp' => ['number' => '351915436088', 'link' => ''],
                'email_public' => 'ola@flashsite.pt',
                'email_admin' => 'admin@flashsite.pt',
            ],
            'location' => [
                'address' => 'Rua A',
                'city_region' => 'Lisboa',
                'postal_code' => '1000-100',
                'country' => 'Portugal',
                'google_maps_url' => 'https://maps.google.com/?q=Lisboa',
            ],
            'social' => [
                'website_url' => 'https://flashsite.pt',
                'instagram' => 'https://instagram.com/flashsite',
                'facebook' => 'https://facebook.com/flashsite',
                'youtube' => 'https://youtube.com/@flashsite',
                'linkedin' => 'https://linkedin.com/company/flashsite',
                'tiktok' => 'https://tiktok.com/@flashsite',
                'doctoralia' => 'https://doctoralia.com/flashsite',
            ],
            'hours' => [
                'monday' => '09h às 18h',
                'notes' => 'Atendimento por marcação',
            ],
            'branding' => [
                'logo_light_id' => 11,
                'logo_dark_id' => 12,
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
                'accent_color' => '#778899',
            ],
        ]));

        $module = new OutputFoundationModule(new BusinessData($repository), new Logger());
        $module->register();

        $this->assertArrayHasKey('flashsite', $GLOBALS['flashsite_test_shortcodes']);
        $this->assertArrayHasKey('flashsite_whatsapp_link', $GLOBALS['flashsite_test_shortcodes']);
        $this->assertArrayHasKey('flashsite_instagram', $GLOBALS['flashsite_test_shortcodes']);

        $name = $module->renderGenericShortcode(['field' => 'business.name']);
        $this->assertSame('Flash Site', $name);

        $wa = $module->renderGenericShortcode(['field' => 'business.whatsapp_link']);
        $this->assertSame('https://wa.me/351915436088', $wa);

        $fullAddress = $module->renderGenericShortcode(['field' => 'business.address.full', 'multiline' => 'yes']);
        $this->assertSame('Rua A, Lisboa, 1000-100, Portugal', $fullAddress);

        $hours = $module->renderGenericShortcode(['field' => 'business.hours.formatted', 'multiline' => 'yes']);
        $this->assertTrue(str_contains($hours, 'Seg: 09h às 18h'));
        $this->assertTrue(str_contains($hours, 'Atendimento por marcação'));

        $website = $module->renderGenericShortcode(['field' => 'business.social.website']);
        $this->assertSame('https://flashsite.pt', $website);

        $primaryColor = $module->renderGenericShortcode(['field' => 'branding.primary_color']);
        $this->assertSame('#112233', $primaryColor);

        $logo = $module->renderGenericShortcode(['field' => 'branding.logo_light']);
        $this->assertTrue(str_contains($logo, 'https://cdn.flashsite.test/media/11-full.png'));

        $blocked = $module->renderGenericShortcode(['field' => 'business.admin_email']);
        $this->assertSame('', $blocked);
    }
}
