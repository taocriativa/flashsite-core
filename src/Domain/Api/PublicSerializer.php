<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Api;

final class PublicSerializer
{
    /** @var array<string, string> */
    private const VISIBILITY_RULES = [
        'contact.email_admin' => 'internal',
        'identity.tax_id' => 'contextual',
        'professional.license' => 'contextual',
        'professional.secondary_id' => 'contextual',
    ];

    /** @param array<string, mixed> $profile
     *  @return array<string, mixed>
     */
    public function serialize(array $profile): array
    {
        return [
            'identity' => [
                'business_name' => $this->string($profile, 'identity.business_name'),
                'business_type' => $this->string($profile, 'identity.business_type'),
                'tagline' => $this->string($profile, 'identity.tagline'),
                'tax_id' => $this->string($profile, 'identity.tax_id'),
            ],
            'contact' => [
                'phone' => $this->string($profile, 'contact.phone'),
                'whatsapp' => [
                    'number' => $this->string($profile, 'contact.whatsapp.number'),
                    'link' => $this->string($profile, 'contact.whatsapp.link'),
                ],
                'email_public' => $this->string($profile, 'contact.email_public'),
            ],
            'location' => [
                'address' => $this->string($profile, 'location.address'),
                'city_region' => $this->string($profile, 'location.city_region'),
                'postal_code' => $this->string($profile, 'location.postal_code'),
                'country' => $this->string($profile, 'location.country'),
                'google_maps_url' => $this->string($profile, 'location.google_maps_url'),
                'google_maps_embed' => $this->string($profile, 'location.google_maps_embed'),
            ],
            'social' => $this->arraySection($profile, 'social'),
            'hours' => $this->arraySection($profile, 'hours'),
            'professional' => [
                'display_name' => $this->string($profile, 'professional.display_name'),
                'title' => $this->string($profile, 'professional.title'),
                'specialty' => $this->string($profile, 'professional.specialty'),
                'license' => $this->string($profile, 'professional.license'),
                'secondary_id' => $this->string($profile, 'professional.secondary_id'),
                'services' => $this->listSection($profile, 'professional.services'),
                'accepted_plans' => $this->listSection($profile, 'professional.accepted_plans'),
            ],
            'context' => [
                'segment' => $this->string($profile, 'context.segment'),
            ],
            'branding' => [
                'logo_light_id' => $this->int($profile, 'branding.logo_light_id'),
                'logo_dark_id' => $this->int($profile, 'branding.logo_dark_id'),
                'primary_color' => $this->string($profile, 'branding.primary_color'),
                'secondary_color' => $this->string($profile, 'branding.secondary_color'),
                'accent_color' => $this->string($profile, 'branding.accent_color'),
                'font_primary' => $this->string($profile, 'branding.font_primary'),
                'font_secondary' => $this->string($profile, 'branding.font_secondary'),
            ],
        ];
    }

    /** @return list<string> */
    public function sections(): array
    {
        return ['identity', 'contact', 'location', 'social', 'hours', 'professional', 'context', 'branding'];
    }

    /** @return array<string, list<string>> */
    public function visibilityRules(): array
    {
        return [
            'public' => [
                'identity.business_name',
                'identity.business_type',
                'identity.tagline',
                'contact.phone',
                'contact.whatsapp.number',
                'contact.whatsapp.link',
                'contact.email_public',
                'location.*',
                'social.*',
                'hours.*',
                'branding.*',
            ],
            'internal' => ['contact.email_admin'],
            'contextual' => ['identity.tax_id', 'professional.license', 'professional.secondary_id'],
        ];
    }

    private function string(array $profile, string $path): string
    {
        return (string) $this->get($profile, $path, '');
    }

    private function int(array $profile, string $path): int
    {
        return (int) $this->get($profile, $path, 0);
    }

    /** @return array<string, mixed> */
    private function arraySection(array $profile, string $path): array
    {
        $value = $this->get($profile, $path, []);
        return is_array($value) ? $value : [];
    }

    /** @return list<string> */
    private function listSection(array $profile, string $path): array
    {
        $value = $this->get($profile, $path, []);
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_map(static fn ($item): string => (string) $item, $value));
    }

    private function get(array $profile, string $path, mixed $default): mixed
    {
        $current = $profile;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }
        return $current;
    }
}
