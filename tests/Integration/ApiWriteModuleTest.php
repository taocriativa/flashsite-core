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
use FlashSite\Core\Domain\Business\BusinessValidator;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Api\ApiAccessModule;

final class ApiWriteModuleTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile([
            'identity' => [
                'business_name' => 'Flash Site',
                'business_type' => 'negocios',
                'tagline' => 'Sites Rápidos',
                'tax_id' => '316041980',
            ],
            'contact' => [
                'phone' => '351915436088',
                'whatsapp' => ['number' => '351915436088', 'link' => 'https://wa.me/351915436088'],
                'email_public' => 'ola@flashsite.pt',
                'email_admin' => 'suporte@flashsite.pt',
            ],
            'branding' => [
                'primary_color' => '#000000',
                'logo_light_id' => 45,
            ],
        ]));

        $module = new ApiAccessModule(
            new BusinessData($repository),
            new PublicSerializer(),
            new OutputResolver(),
            new MetaProvider(new PublicSerializer()),
            $repository,
            new BusinessValidator(),
            new Logger()
        );

        $GLOBALS['flashsite_test_current_user_caps']['flashsite_manage_business_data'] = true;

        $updatedContact = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'contact'], [
            'phone' => '+351 966 111 222',
            'email_public' => 'novo@flashsite.pt',
        ]));
        $this->assertSame(true, $updatedContact['success']);
        $this->assertSame('contact', $updatedContact['updated_section']);

        $saved = $repository->getProfile()->toArray();
        $this->assertSame('351966111222', $saved['contact']['phone']);
        $this->assertSame('novo@flashsite.pt', $saved['contact']['email_public']);
        $this->assertSame('#000000', $saved['branding']['primary_color']);
        $this->assertSame('Flash Site', $saved['identity']['business_name']);

        $updatedBranding = $module->updatePrivateProfile(new FlashSiteTestRequest([], [
            'data' => [
                'branding' => [
                    'primary_color' => '#112233',
                    'secondary_color' => '#445566',
                ],
            ],
        ]));
        $this->assertSame(true, $updatedBranding['success']);
        $saved = $repository->getProfile()->toArray();
        $this->assertSame('#112233', $saved['branding']['primary_color']);
        $this->assertSame('#445566', $saved['branding']['secondary_color']);
        $this->assertSame('351966111222', $saved['contact']['phone']);

        $invalid = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'contact'], [
            'email_public' => 'not-an-email',
        ]));
        $this->assertTrue($invalid instanceof WP_Error);
        $this->assertSame('validation_failed', $invalid->code);
        $this->assertSame(422, $invalid->data['status']);

        $empty = $module->updatePrivateProfile(new FlashSiteTestRequest([], []));
        $this->assertTrue($empty instanceof WP_Error);
        $this->assertSame('empty_payload', $empty->code);

        $invalidSection = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'secret'], ['token' => 'x']));
        $this->assertTrue($invalidSection instanceof WP_Error);
        $this->assertSame('invalid_section', $invalidSection->code);
    }
}
