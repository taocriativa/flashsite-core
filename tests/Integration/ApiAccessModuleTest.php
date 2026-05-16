<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Api\MetaProvider;
use FlashSite\Core\Domain\Api\OutputResolver;
use FlashSite\Core\Domain\Api\PublicSerializer;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Api\ApiAccessModule;

final class ApiAccessModuleTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile([
            'identity' => [
                'business_name' => 'Clínica Base',
                'tagline' => 'Cuidado contínuo',
                'tax_id' => '123456789',
            ],
            'contact' => [
                'phone' => '351999111222',
                'whatsapp' => ['number' => '351999111222', 'link' => 'https://wa.me/351999111222'],
                'email_public' => 'geral@example.com',
                'email_admin' => 'admin@example.com',
            ],
            'professional' => [
                'display_name' => 'Dra. Exemplo',
                'title' => 'Médica',
                'specialty' => 'Dermatologia',
                'license' => 'OM1234',
                'secondary_id' => 'INT-77',
                'services' => ['Consulta'],
                'accepted_plans' => ['Plano A'],
            ],
            'branding' => [
                'primary_color' => '#123456',
            ],
        ]));

        $module = new ApiAccessModule(new BusinessData($repository), new PublicSerializer(), new OutputResolver(), new MetaProvider(new PublicSerializer()), $repository, new \FlashSite\Core\Domain\Business\BusinessValidator(), new Logger());
        $module->register();
        do_action('rest_api_init');

        $routes = $GLOBALS['flashsite_test_rest_routes'];
        $this->assertArrayHasKey('flashsite/v1/public/business-profile', $routes);
        $this->assertArrayHasKey('flashsite/v1/business-profile', $routes);

        $public = $module->getPublicProfile();
        $this->assertSame('public', $public['visibility']);
        $this->assertSame('Clínica Base', $public['data']['identity']['business_name']);
        $this->assertSame('123456789', $public['data']['identity']['tax_id']);
        $this->assertTrue(!isset($public['data']['contact']['email_admin']));
        $this->assertSame('OM1234', $public['data']['professional']['license']);
        $this->assertSame('INT-77', $public['data']['professional']['secondary_id']);

        $publicSection = $module->getPublicSection(new FlashSiteTestRequest(['section' => 'branding']));
        $this->assertSame('branding', $publicSection['section']);
        $this->assertSame('#123456', $publicSection['data']['branding']['primary_color']);

        $invalid = $module->getPublicSection(new FlashSiteTestRequest(['section' => 'secret']));
        $this->assertTrue($invalid instanceof WP_Error);
        $this->assertSame('invalid_section', $invalid->code);

        $GLOBALS['flashsite_test_current_user_caps']['flashsite_manage_business_data'] = true;
        $this->assertTrue($module->canReadPrivateProfile());

        $private = $module->getPrivateProfile();
        $this->assertSame('private', $private['visibility']);
        $this->assertSame('123456789', $private['data']['identity']['tax_id']);
        $this->assertSame('admin@example.com', $private['data']['contact']['email_admin']);
        $this->assertSame('OM1234', $private['data']['professional']['license']);
    }
}
