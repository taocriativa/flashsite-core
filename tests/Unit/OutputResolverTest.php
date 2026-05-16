<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Api\OutputResolver;

final class OutputResolverTest extends TestCase
{
    public function run(): void
    {
        $resolver = new OutputResolver();
        $payload = $resolver->resolve([
            'identity' => ['business_name' => 'Flash Site', 'tagline' => 'Sites rápidos'],
            'contact' => [
                'phone' => '351915436088',
                'whatsapp' => ['number' => '351915436088', 'link' => ''],
                'email_public' => 'ola@flashsite.pt',
            ],
            'branding' => [
                'primary_color' => '#000000',
                'logo_light_id' => 12,
                'logo_dark_id' => 14,
            ],
            'location' => ['address' => 'Rua X'],
            'social' => ['website_url' => 'https://flashsite.pt'],
        ]);

        $this->assertSame('+351 915 436 088', $payload['phone']['formatted']);
        $this->assertSame('https://wa.me/351915436088', $payload['whatsapp']['link']);
        $this->assertSame('https://cdn.flashsite.test/media/12-full.png', $payload['branding']['logo_light_url']);
        $this->assertSame('ola@flashsite.pt', $payload['email']);
    }
}
