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

final class ApiConsolidationTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile([
            'identity' => [
                'business_name' => 'Flash Site',
                'tax_id' => '316041980',
                'tagline' => 'Sites rápidos',
            ],
            'contact' => [
                'phone' => '351915436088',
                'whatsapp' => ['number' => '351915436088', 'link' => ''],
                'email_public' => 'ola@flashsite.pt',
                'email_admin' => 'suporte@flashsite.pt',
            ],
            'branding' => [
                'logo_light_id' => 9,
                'logo_dark_id' => 10,
                'primary_color' => '#000000',
            ],
        ]));

        $module = new ApiAccessModule(
            new BusinessData($repository),
            new PublicSerializer(),
            new OutputResolver(),
            new MetaProvider(new PublicSerializer()),
            $repository,
            new \FlashSite\Core\Domain\Business\BusinessValidator(),
            new Logger()
        );
        $module->register();
        do_action('rest_api_init');

        $routes = $GLOBALS['flashsite_test_rest_routes'];
        $this->assertArrayHasKey('flashsite/v1/public/meta', $routes);
        $this->assertArrayHasKey('flashsite/v1/public/output', $routes);

        $meta = $module->getPublicMeta();
        $this->assertSame('1.4.0', $meta['api_version']);
        $this->assertSame(['contact.email_admin'], $meta['visibility_rules']['internal']);

        $output = $module->getPublicOutput();
        $this->assertSame('resolved_output', $output['meta']['type']);
        $this->assertSame('+351 915 436 088', $output['data']['phone']['formatted']);
        $this->assertSame('https://wa.me/351915436088', $output['data']['whatsapp']['link']);
        $this->assertSame('https://cdn.flashsite.test/media/9-full.png', $output['data']['branding']['logo_light_url']);

        $public = $module->getPublicProfile();
        $this->assertSame('1.4.0', $public['meta']['api_version']);
        $this->assertSame('public', $public['meta']['visibility']);
        $this->assertTrue(!isset($public['data']['contact']['email_admin']));
    }
}
