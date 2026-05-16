<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Business\BusinessProfile;

final class BusinessProfileTest extends TestCase
{
    public function run(): void
    {
        $profile = new BusinessProfile([
            'identity' => ['business_name' => 'Clinic X'],
            'contact' => ['phone' => '123', 'whatsapp' => ['number' => '456']],
            'professional' => ['services' => "Consulta\nCirurgia", 'accepted_plans' => ['Plan A', '']],
            'branding' => ['primary_color' => 'aabbcc', 'logo_light_id' => '12'],
        ]);

        $data = $profile->toArray();
        $this->assertSame('Clinic X', $data['identity']['business_name']);
        $this->assertSame('123', $data['contact']['phone']);
        $this->assertSame('456', $data['contact']['whatsapp']['number']);
        $this->assertSame(['Consulta', 'Cirurgia'], $data['professional']['services']);
        $this->assertSame(['Plan A'], $data['professional']['accepted_plans']);
        $this->assertSame('#AABBCC', $data['branding']['primary_color']);
        $this->assertSame(12, $data['branding']['logo_light_id']);
        $this->assertArrayHasKey('hours', $data);
        $this->assertArrayHasKey('context', $data);
    }
}
