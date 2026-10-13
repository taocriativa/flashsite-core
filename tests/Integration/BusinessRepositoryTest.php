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

        $this->idempotentSave();
    }

    /** 3.3.0-beta.4: salvar sem alteração não é falha; falha real continua falha. */
    private function idempotentSave(): void
    {
        flashsite_reset_test_state();
        $storage = new OptionsStorage();
        $repo = new BusinessRepository($storage);
        $profile = fn (string $name): BusinessProfile => new BusinessProfile(['identity' => ['business_name' => $name]]);

        $this->assertTrue($repo->saveProfile($profile('Studio')), 'Primeira gravação.');
        // O update_option do WordPress devolve false para valor igual; a abstração diz true.
        $this->assertFalse(update_option('flashsite_business_profile', $repo->getRaw()), 'Stub deve imitar o WordPress.');
        $this->assertTrue($repo->saveProfile($profile('Studio')), 'Mesma gravação de novo.');
        $this->assertTrue($repo->saveProfile($profile('Studio')), 'E de novo.');
        $this->assertTrue($repo->saveProfile($profile('Outro')), 'Alteração real.');
        $this->assertSame('Outro', $repo->getRaw()['identity']['business_name']);
        $this->assertTrue($repo->saveProfile($profile('Studio')), 'Voltar ao valor original.');

        // Falha real: nada persiste, o valor desejado difere do gravado.
        $GLOBALS['flashsite_test_update_option_fail'] = true;
        $this->assertFalse($repo->saveProfile($profile('Nunca gravado')), 'Falha real não pode virar sucesso.');
        $this->assertSame('Studio', $repo->getRaw()['identity']['business_name']);
        // Mesmo com falha de escrita, se o valor desejado já é o gravado, o estado está correto.
        $this->assertTrue($storage->update('flashsite_business_profile', $repo->getRaw()));
        $GLOBALS['flashsite_test_update_option_fail'] = false;
    }
}
