<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

final class BusinessProfile
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(array $data)
    {
        $this->data = self::normalize($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $identity = is_array($data['identity'] ?? null) ? $data['identity'] : [];
        $contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        $social = is_array($data['social'] ?? null) ? $data['social'] : [];
        $hours = is_array($data['hours'] ?? null) ? $data['hours'] : [];
        $professional = is_array($data['professional'] ?? null) ? $data['professional'] : [];
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        $branding = is_array($data['branding'] ?? null) ? $data['branding'] : [];

        return [
            'identity' => [
                'business_name' => (string) ($identity['business_name'] ?? ''),
                'business_type' => (string) ($identity['business_type'] ?? ''),
                'tagline' => (string) ($identity['tagline'] ?? ''),
                'tax_id' => (string) ($identity['tax_id'] ?? ''),
            ],
            'contact' => [
                'phone' => (string) ($contact['phone'] ?? ''),
                'whatsapp' => [
                    'number' => (string) (($contact['whatsapp']['number'] ?? $contact['whatsapp_number'] ?? '')),
                    'link' => (string) (($contact['whatsapp']['link'] ?? $contact['whatsapp_link'] ?? '')),
                ],
                'email_public' => (string) ($contact['email_public'] ?? ''),
                'email_admin' => (string) ($contact['email_admin'] ?? ''),
            ],
            'location' => [
                'address' => (string) ($location['address'] ?? ''),
                'city_region' => (string) ($location['city_region'] ?? ''),
                'postal_code' => (string) ($location['postal_code'] ?? ''),
                'country' => (string) ($location['country'] ?? ''),
                'google_maps_url' => (string) ($location['google_maps_url'] ?? ''),
                'google_maps_embed' => (string) ($location['google_maps_embed'] ?? ''),
            ],
            'social' => [
                'website_url' => (string) ($social['website_url'] ?? ''),
                'instagram' => (string) ($social['instagram'] ?? ''),
                'facebook' => (string) ($social['facebook'] ?? ''),
                'youtube' => (string) ($social['youtube'] ?? ''),
                'linkedin' => (string) ($social['linkedin'] ?? ''),
                'tiktok' => (string) ($social['tiktok'] ?? ''),
                'doctoralia' => (string) ($social['doctoralia'] ?? ''),
            ],
            'hours' => [
                'monday' => (string) ($hours['monday'] ?? ''),
                'tuesday' => (string) ($hours['tuesday'] ?? ''),
                'wednesday' => (string) ($hours['wednesday'] ?? ''),
                'thursday' => (string) ($hours['thursday'] ?? ''),
                'friday' => (string) ($hours['friday'] ?? ''),
                'saturday' => (string) ($hours['saturday'] ?? ''),
                'sunday' => (string) ($hours['sunday'] ?? ''),
                'notes' => (string) ($hours['notes'] ?? ''),
                'legacy_text' => (string) ($hours['legacy_text'] ?? ''),
            ],
            'professional' => [
                'display_name' => (string) ($professional['display_name'] ?? ''),
                'title' => (string) ($professional['title'] ?? ''),
                'specialty' => (string) ($professional['specialty'] ?? ''),
                'license' => (string) ($professional['license'] ?? ''),
                'secondary_id' => (string) ($professional['secondary_id'] ?? ''),
                'services' => self::normalizeList($professional['services'] ?? []),
                'accepted_plans' => self::normalizeList($professional['accepted_plans'] ?? []),
            ],
            'context' => [
                'segment' => (string) ($context['segment'] ?? ''),
            ],
            'branding' => [
                'logo_light_id' => absint($branding['logo_light_id'] ?? 0),
                'logo_dark_id' => absint($branding['logo_dark_id'] ?? 0),
                'primary_color' => self::normalizeColor($branding['primary_color'] ?? ''),
                'secondary_color' => self::normalizeColor($branding['secondary_color'] ?? ''),
                'accent_color' => self::normalizeColor($branding['accent_color'] ?? ''),
                'font_primary' => (string) ($branding['font_primary'] ?? ''),
                'font_secondary' => (string) ($branding['font_secondary'] ?? ''),
            ],
        ];
    }

    /** @return list<string> */
    private static function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }
        if (! is_array($value)) {
            return [];
        }
        $items = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }
        return array_values($items);
    }

    private static function normalizeColor(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if ($value[0] !== '#') {
            $value = '#' . $value;
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value) === 1 ? strtoupper($value) : '';
    }

    public function getBusinessName(): string { return $this->data['identity']['business_name']; }
    public function getBusinessType(): string { return $this->data['identity']['business_type']; }
    public function getTagline(): string { return $this->data['identity']['tagline']; }
    public function getNif(): string { return $this->data['identity']['tax_id']; }
    public function getPhone(): string { return $this->data['contact']['phone']; }
    public function getWhatsapp(): string { return $this->data['contact']['whatsapp']['number']; }
    public function getWhatsappLink(): string { return $this->data['contact']['whatsapp']['link']; }
    public function getEmail(): string { return $this->data['contact']['email_public']; }
    public function getAdminEmail(): string { return $this->data['contact']['email_admin']; }
    public function getAddress(): string { return $this->data['location']['address']; }
    public function getCityRegion(): string { return $this->data['location']['city_region']; }
    public function getPostalCode(): string { return $this->data['location']['postal_code']; }
    public function getCountry(): string { return $this->data['location']['country']; }
    public function getGoogleMapsUrl(): string { return $this->data['location']['google_maps_url']; }
    public function getGoogleMapsEmbed(): string { return $this->data['location']['google_maps_embed']; }

    public function getOpeningHours(): string
    {
        $hours = $this->getHours();
        $ordered = [
            'Seg' => $hours['monday'] ?? '',
            'Ter' => $hours['tuesday'] ?? '',
            'Qua' => $hours['wednesday'] ?? '',
            'Qui' => $hours['thursday'] ?? '',
            'Sex' => $hours['friday'] ?? '',
            'Sáb' => $hours['saturday'] ?? '',
            'Dom' => $hours['sunday'] ?? '',
        ];
        $lines = [];
        foreach ($ordered as $label => $value) {
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }
        if (($hours['notes'] ?? '') !== '') {
            $lines[] = (string) $hours['notes'];
        }
        if ($lines === [] && ($hours['legacy_text'] ?? '') !== '') {
            return (string) $hours['legacy_text'];
        }
        return implode("\n", $lines);
    }

    public function getWebsiteUrl(): string { return $this->data['social']['website_url']; }

    /** @return array<string, string> */
    public function getSocialLinks(): array { return $this->data['social']; }

    /** @return array<string, string> */
    public function getHours(): array { return $this->data['hours']; }

    /** @return array<string, mixed> */
    public function getProfessional(): array { return $this->data['professional']; }

    /** @return array<string, mixed> */
    public function getContext(): array { return $this->data['context']; }

    /** @return array<string, mixed> */
    public function getIdentity(): array { return $this->data['identity']; }

    /** @return array<string, mixed> */
    public function getContact(): array { return $this->data['contact']; }

    /** @return array<string, mixed> */
    public function getLocation(): array { return $this->data['location']; }

    /** @return array<string, mixed> */
    public function getBranding(): array { return $this->data['branding']; }

    public function getLogoLightId(): int { return (int) $this->data['branding']['logo_light_id']; }
    public function getLogoDarkId(): int { return (int) $this->data['branding']['logo_dark_id']; }
    public function getPrimaryColor(): string { return (string) $this->data['branding']['primary_color']; }
    public function getSecondaryColor(): string { return (string) $this->data['branding']['secondary_color']; }
    public function getAccentColor(): string { return (string) $this->data['branding']['accent_color']; }
    public function getFontPrimary(): string { return (string) $this->data['branding']['font_primary']; }
    public function getFontSecondary(): string { return (string) $this->data['branding']['font_secondary']; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** @return array<string, mixed> */
    public function toLegacyArray(): array
    {
        return [
            'business_name' => $this->getBusinessName(),
            'nif' => $this->getNif(),
            'phone' => $this->getPhone(),
            'whatsapp' => $this->getWhatsapp(),
            'email' => $this->getEmail(),
            'address' => $this->getAddress(),
            'social_links' => [
                'instagram' => (string) ($this->data['social']['instagram'] ?? ''),
                'facebook' => (string) ($this->data['social']['facebook'] ?? ''),
            ],
            'opening_hours' => $this->getOpeningHours(),
            'website_url' => $this->getWebsiteUrl(),
        ];
    }
}
