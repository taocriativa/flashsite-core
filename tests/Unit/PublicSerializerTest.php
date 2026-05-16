<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Api\PublicSerializer;

final class PublicSerializerTest extends TestCase
{
    public function run(): void
    {
        $serializer = new PublicSerializer();
        $payload = $serializer->serialize([
            'identity' => ['business_name' => 'Flash Site', 'tax_id' => '123'],
            'contact' => ['email_public' => 'ola@flashsite.pt', 'email_admin' => 'suporte@flashsite.pt'],
            'professional' => ['license' => 'ABC', 'secondary_id' => '99'],
        ]);

        $this->assertSame('Flash Site', $payload['identity']['business_name']);
        $this->assertSame('123', $payload['identity']['tax_id']);
        $this->assertTrue(!isset($payload['contact']['email_admin']));
        $this->assertSame('ABC', $payload['professional']['license']);
        $this->assertSame('99', $payload['professional']['secondary_id']);

        $rules = $serializer->visibilityRules();
        $this->assertSame(['contact.email_admin'], $rules['internal']);
        $this->assertTrue(in_array('identity.tax_id', $rules['contextual'], true));
        $this->assertTrue(in_array('branding', $serializer->sections(), true));
    }
}
