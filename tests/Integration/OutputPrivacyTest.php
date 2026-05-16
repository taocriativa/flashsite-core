<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\OutputFoundation\OutputFoundationModule;

final class OutputPrivacyTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile([
            'identity' => [
                'business_name' => 'Flash Site',
                'tax_id' => '123456789',
            ],
            'contact' => [
                'email_public' => 'ola@flashsite.pt',
                'email_admin' => 'suporte@flashsite.pt',
            ],
        ]));

        $module = new OutputFoundationModule(new BusinessData($repository), new Logger());
        $module->register();

        $this->assertFalse(isset($GLOBALS['flashsite_test_shortcodes']['flashsite_email_admin']));
        $this->assertSame('', $module->renderGenericShortcode(['field' => 'contact.email_admin']));
        $this->assertSame('123456789', $module->renderGenericShortcode(['field' => 'identity.tax_id']));
        $this->assertSame('ola@flashsite.pt', $module->renderGenericShortcode(['field' => 'contact.email_public']));
    }
}
