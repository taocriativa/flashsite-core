<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../Support/WpPostStubs.php';
require_once __DIR__ . '/../Support/AbilitiesFixture.php';

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

/**
 * E-mail inválido nunca vira "campo vazio" (@since 3.5.0-beta.1, achado no QA real).
 * Só "" enviado de propósito limpa; omitido preserva; inválido não vazio rejeita sem gravar.
 */
final class BusinessEmailValidationTest extends TestCase
{
    private const SEED = [
        'identity' => ['business_name' => 'Flash Site', 'business_type' => 'negocios', 'tagline' => 't', 'tax_id' => '316041980'],
        'contact' => ['phone' => '351915436088', 'email_public' => 'ola@flashsite.pt', 'email_admin' => 'suporte@flashsite.pt'],
    ];

    public function run(): void
    {
        $this->assertTrue(sanitize_email('nao-e-email') === '', 'O stub tem de imitar o WordPress real.');
        $this->assertSame('a@b.pt', sanitize_email(' a@b.pt '));

        $validator = new BusinessValidator();
        $existing = (new BusinessProfile(self::SEED))->toArray();
        foreach (['email' => ['email_public', 'ola@flashsite.pt'], 'email_admin' => ['email_admin', 'suporte@flashsite.pt']] as $inputKey => [$field, $current]) {
            $base = ['business_name' => 'Flash Site', 'phone' => '351915436088'];
            // 1. válido
            $ok = $validator->validate($base + [$inputKey => 'novo@flashsite.pt'], $existing);
            $this->assertSame([], $ok['errors']);
            $this->assertSame('novo@flashsite.pt', $ok['data']['contact'][$field]);
            // 2. vazio explícito limpa
            $cleared = $validator->validate($base + [$inputKey => ''], $existing);
            $this->assertSame([], $cleared['errors']);
            $this->assertSame('', $cleared['data']['contact'][$field]);
            // 3. inválido não vazio rejeita
            foreach (['nao-e-email', 'a@', '  x  '] as $bad) {
                $rejected = $validator->validate($base + [$inputKey => $bad], $existing);
                $this->assertTrue(isset($rejected['errors'][$inputKey]), "$inputKey=\"$bad\" deve ser rejeitado");
            }
            $this->assertSame($inputKey === 'email' ? 'O email público informado é inválido.' : 'O email administrativo informado é inválido.', $validator->validate($base + [$inputKey => 'nao-e-email'], $existing)['errors'][$inputKey]);
            // 5. omitido preserva
            $omitted = $validator->validate($base, $existing);
            $this->assertSame([], $omitted['errors']);
            $this->assertSame($current, $omitted['data']['contact'][$field]);
        }

        $this->restAndAbility();
    }

    private function restAndAbility(): void
    {
        foreach (['email_public' => 'email_public', 'email_admin' => 'email_admin'] as $field => $key) {
            $current = self::SEED['contact'][$field];

            // REST privada.
            flashsite_reset_test_state();
            $repo = new BusinessRepository(new OptionsStorage());
            $repo->saveProfile(new BusinessProfile(self::SEED));
            $module = new ApiAccessModule(new BusinessData($repo), new PublicSerializer(), new OutputResolver(), new MetaProvider(new PublicSerializer()), $repo, new BusinessValidator(), new Logger());
            $GLOBALS['flashsite_test_current_user_caps']['flashsite_manage_business_data'] = true;
            $rest = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'contact'], [$key => 'nao-e-email', 'phone' => '+351 900 000 000']));
            $this->assertTrue($rest instanceof WP_Error, "REST $field inválido");
            $this->assertSame('validation_failed', $rest->code);
            $this->assertSame($current, $repo->getProfile()->toArray()['contact'][$field], 'REST preserva o valor');
            $this->assertSame('351915436088', $repo->getProfile()->toArray()['contact']['phone'], 'Nada do payload é gravado');
            $restClear = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'contact'], [$key => '']));
            $this->assertSame(true, $restClear['success']);
            $this->assertSame('', $repo->getProfile()->toArray()['contact'][$field]);

            // Ability.
            $fx = new AbilitiesFixture();
            $fx->business->saveProfile(new BusinessProfile(self::SEED));
            $fx->asUser(['flashsite_manage_business_data']);
            $ability = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => [$key => 'nao-e-email', 'phone' => '+351 900 000 000']]);
            $this->assertSame('validation_failed', $ability->get_error_code(), "Ability $field inválido");
            $this->assertSame($rest->data['errors'], $ability->get_error_data()['errors'], 'Mesmo erro que a REST');
            $this->assertSame($current, $fx->business->getProfile()->toArray()['contact'][$field], 'Ability preserva o valor');
            $this->assertSame('351915436088', $fx->business->getProfile()->toArray()['contact']['phone']);
            $omitted = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => ['phone' => '+351 911 111 111']]);
            $this->assertTrue($omitted['success']);
            $this->assertSame($current, $fx->business->getProfile()->toArray()['contact'][$field], 'Omitido preserva');
            $clear = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => [$key => '']]);
            $this->assertTrue($clear['success']);
            $this->assertSame('', $fx->business->getProfile()->toArray()['contact'][$field], 'Vazio explícito limpa');
        }
    }
}
