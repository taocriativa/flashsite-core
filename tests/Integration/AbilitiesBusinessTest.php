<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../Support/WpPostStubs.php';
require_once __DIR__ . '/../Support/AbilitiesFixture.php';

use FlashSite\Core\Core\InstallationGuard;
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

/** Dados do Negócio por Abilities: mesma regra de domínio da REST (@since 3.5.0). */
final class AbilitiesBusinessTest extends TestCase
{
    public function run(): void
    {
        $fx = new AbilitiesFixture();
        $fx->business->saveProfile(new BusinessProfile([
            'identity' => ['business_name' => 'Flash Site', 'business_type' => 'negocios', 'tagline' => 'Sites Rápidos', 'tax_id' => '316041980'],
            'contact' => ['phone' => '351915436088', 'email_public' => 'ola@flashsite.pt', 'email_admin' => 'suporte@flashsite.pt'],
            'branding' => ['primary_color' => '#000000'],
        ]));

        // Leitura: só com a capability.
        $fx->asUser([]);
        $this->assertSame('ability_invalid_permissions', $fx->call('flashsite/get-business-data')->get_error_code());
        $fx->asUser(['flashsite_manage_business_data']);
        $all = $fx->call('flashsite/get-business-data');
        $this->assertSame('Flash Site', $all['data']['identity']['business_name']);
        $this->assertSame((new BusinessData($fx->business))->all(), $all['data'], 'Mesma visão da REST privada.');
        $section = $fx->call('flashsite/get-business-data', ['section' => 'contact']);
        $this->assertSame(['contact'], array_keys($section['data']));
        $this->assertSame('invalid_section', $fx->call('flashsite/get-business-data', ['section' => 'segredo'])->get_error_code());

        // Escrita: patch válido por secção e sem secção.
        $fx->asUser([]);
        $this->assertSame('ability_invalid_permissions', $fx->call('flashsite/update-business-data', ['data' => ['phone' => '1']])->get_error_code());
        $fx->asUser(['flashsite_manage_business_data']);
        $ok = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => ['phone' => '+351 966 111 222', 'email_public' => 'novo@flashsite.pt']]);
        $this->assertTrue($ok['success']);
        $saved = $fx->business->getProfile()->toArray();
        $this->assertSame('+351 966 111 222', $saved['contact']['phone']);
        $this->assertSame('#000000', $saved['branding']['primary_color'], 'Patch preserva o resto.');
        $multi = $fx->call('flashsite/update-business-data', ['data' => ['branding' => ['primary_color' => '#112233']]]);
        $this->assertSame('#112233', $multi['data']['branding']['primary_color']);
        $this->assertSame('+351 966 111 222', $fx->business->getProfile()->toArray()['contact']['phone']);

        // Validação e entradas inválidas.
        $invalid = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => ['email_public' => 'nao-e-email']]);
        $this->assertSame('validation_failed', $invalid->get_error_code());
        $this->assertSame(422, $invalid->get_error_data()['status']);
        $this->assertTrue(isset($invalid->get_error_data()['errors']));
        $this->assertSame('novo@flashsite.pt', $fx->business->getProfile()->toArray()['contact']['email_public']);
        $this->assertSame('empty_payload', $fx->call('flashsite/update-business-data', ['data' => []])->get_error_code());
        $this->assertSame('invalid_section', $fx->call('flashsite/update-business-data', ['section' => 'segredo', 'data' => ['x' => 1]])->get_error_code());

        $this->installationGuard($fx);
        $this->sameAsRest();
    }

    private function installationGuard(AbilitiesFixture $fx): void
    {
        // Instalação duplicada do Core: a escrita sensível é bloqueada, como na REST.
        $GLOBALS['flashsite_test_plugins'] = ['flashsite-core-copia/flashsite-core.php' => ['Name' => 'FlashSite Core', 'TextDomain' => 'flashsite-core', 'Version' => '3.4.0']];
        if (! function_exists('get_plugins')) {
            eval('function get_plugins() { return $GLOBALS["flashsite_test_plugins"] ?? []; }');
        }
        InstallationGuard::status();
        $this->assertFalse(InstallationGuard::canWriteSensitive());
        $before = $fx->business->getProfile()->toArray();
        $blocked = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => ['phone' => '999']]);
        $this->assertSame('installation_protected', $blocked->get_error_code());
        $this->assertSame(409, $blocked->get_error_data()['status']);
        $this->assertSame($before, $fx->business->getProfile()->toArray(), 'Nada gravado com a guarda ativa.');
        // Leitura continua possível.
        $this->assertTrue(is_array($fx->call('flashsite/get-business-data')));

        $GLOBALS['flashsite_test_plugins'] = [];
        InstallationGuard::status();
        $this->assertTrue(InstallationGuard::canWriteSensitive());
    }

    private function sameAsRest(): void
    {
        $payload = ['phone' => '+351 900 000 000', 'email_public' => 'igual@flashsite.pt'];
        $seed = [
            'identity' => ['business_name' => 'Flash Site', 'business_type' => 'negocios', 'tagline' => 'x', 'tax_id' => '316041980'],
            'contact' => ['phone' => '351915436088', 'email_public' => 'ola@flashsite.pt', 'email_admin' => 'suporte@flashsite.pt'],
        ];
        $viaRest = $this->freshRepository($seed);
        $module = new ApiAccessModule(new BusinessData($viaRest), new PublicSerializer(), new OutputResolver(), new MetaProvider(new PublicSerializer()), $viaRest, new BusinessValidator(), new Logger());
        $GLOBALS['flashsite_test_current_user_caps']['flashsite_manage_business_data'] = true;
        $rest = $module->updatePrivateSection(new FlashSiteTestRequest(['section' => 'contact'], $payload));
        $restResult = $viaRest->getProfile()->toArray();

        $fx = new AbilitiesFixture();
        $fx->business->saveProfile(new BusinessProfile($seed));
        $fx->asUser(['flashsite_manage_business_data']);
        $ability = $fx->call('flashsite/update-business-data', ['section' => 'contact', 'data' => $payload]);

        $this->assertTrue($rest['success'] && $ability['success']);
        $this->assertSame($restResult, $fx->business->getProfile()->toArray(), 'REST e Ability terminam na mesma regra de domínio.');
        $this->assertSame($rest['data'], $ability['data']);
    }

    /** @param array<string, mixed> $seed */
    private function freshRepository(array $seed): BusinessRepository
    {
        flashsite_reset_test_state();
        $repository = new BusinessRepository(new OptionsStorage());
        $repository->saveProfile(new BusinessProfile($seed));
        return $repository;
    }
}
