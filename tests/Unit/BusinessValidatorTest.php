<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Business\BusinessValidator;

final class BusinessValidatorTest extends TestCase
{
    public function run(): void
    {
        $validator = new BusinessValidator();

        $result = $validator->validate([
            'business_name' => ' Flash <b>Site</b> ',
            'phone' => '+351 912 345 678',
            'email' => 'info@example.com',
            'branding_primary_color' => 'ff00aa',
            'professional_services' => "Consulta\n Avaliação ",
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame('Flash Site', $result['data']['identity']['business_name']);
        $this->assertSame('351912345678', $result['data']['contact']['phone']);
        $this->assertSame('info@example.com', $result['data']['contact']['email_public']);
        $this->assertSame('#FF00AA', $result['data']['branding']['primary_color']);
        $this->assertSame(['Consulta', 'Avaliação'], $result['data']['professional']['services']);

        $merge = $validator->validate([
            'phone' => '999999999',
        ], $result['data']);

        $this->assertSame('Flash Site', $merge['data']['identity']['business_name']);
        $this->assertSame('999999999', $merge['data']['contact']['phone']);
        $this->assertSame('#FF00AA', $merge['data']['branding']['primary_color']);

        $invalid = $validator->validate([
            'business_name' => 'Empresa',
            'phone' => '123',
            'branding_primary_color' => 'not-a-color',
            'email' => 'bad@@example',
        ]);

        $this->assertArrayHasKey('branding_primary_color', $invalid['errors']);
        $this->assertArrayHasKey('email', $invalid['errors']);
    }
}
