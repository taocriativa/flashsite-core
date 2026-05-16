<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class BusinessRepositoryTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $repo = new BusinessRepository(new OptionsStorage());

        $saved = $repo->saveProfile(new BusinessProfile([
            'identity' => ['business_name' => 'Studio'],
            'contact' => ['phone' => '111'],
            'branding' => ['primary_color' => '#123456'],
        ]));

        $this->assertTrue($saved);
        $raw = $repo->getRaw();
        $this->assertSame('Studio', $raw['identity']['business_name']);
        $this->assertSame('111', $raw['contact']['phone']);
        $this->assertSame('#123456', $raw['branding']['primary_color']);

        update_option('flashsite_business_profile', [
            'business_name' => 'Legacy Co',
            'nif' => '123456789',
            'phone' => '222',
            'social_links' => ['instagram' => 'https://instagram.com/x'],
        ]);

        $migrated = $repo->getRaw();
        $this->assertSame('Legacy Co', $migrated['identity']['business_name']);
        $this->assertSame('123456789', $migrated['identity']['tax_id']);
        $this->assertSame('222', $migrated['contact']['phone']);
        $this->assertSame('https://instagram.com/x', $migrated['social']['instagram']);

        $this->assertTrue($repo->reset());
        $this->assertSame([], get_option('flashsite_business_profile', []));
    }
}
