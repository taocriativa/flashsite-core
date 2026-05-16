<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Api;

final class OutputResolver
{
    /** @param array<string, mixed> $publicProfile
     *  @return array<string, mixed>
     */
    public function resolve(array $publicProfile): array
    {
        $phone = (string) ($publicProfile['contact']['phone'] ?? '');
        $whatsappNumber = (string) (($publicProfile['contact']['whatsapp']['number'] ?? ''));
        $whatsappLink = (string) (($publicProfile['contact']['whatsapp']['link'] ?? ''));
        $branding = is_array($publicProfile['branding'] ?? null) ? $publicProfile['branding'] : [];

        return [
            'business_name' => (string) ($publicProfile['identity']['business_name'] ?? ''),
            'tagline' => (string) ($publicProfile['identity']['tagline'] ?? ''),
            'phone' => [
                'raw' => $phone,
                'formatted' => $this->formatPhone($phone),
            ],
            'whatsapp' => [
                'number' => $whatsappNumber,
                'formatted' => $this->formatPhone($whatsappNumber),
                'link' => $whatsappLink !== '' ? $whatsappLink : $this->buildWaLink($whatsappNumber),
            ],
            'email' => (string) ($publicProfile['contact']['email_public'] ?? ''),
            'branding' => [
                'primary_color' => (string) ($branding['primary_color'] ?? ''),
                'secondary_color' => (string) ($branding['secondary_color'] ?? ''),
                'accent_color' => (string) ($branding['accent_color'] ?? ''),
                'font_primary' => (string) ($branding['font_primary'] ?? ''),
                'font_secondary' => (string) ($branding['font_secondary'] ?? ''),
                'logo_light_id' => (int) ($branding['logo_light_id'] ?? 0),
                'logo_dark_id' => (int) ($branding['logo_dark_id'] ?? 0),
                'logo_light_url' => $this->resolveAttachmentUrl((int) ($branding['logo_light_id'] ?? 0)),
                'logo_dark_url' => $this->resolveAttachmentUrl((int) ($branding['logo_dark_id'] ?? 0)),
            ],
            'location' => [
                'address' => (string) (($publicProfile['location']['address'] ?? '')),
                'city_region' => (string) (($publicProfile['location']['city_region'] ?? '')),
                'postal_code' => (string) (($publicProfile['location']['postal_code'] ?? '')),
                'country' => (string) (($publicProfile['location']['country'] ?? '')),
                'google_maps_url' => (string) (($publicProfile['location']['google_maps_url'] ?? '')),
            ],
            'social' => is_array($publicProfile['social'] ?? null) ? $publicProfile['social'] : [],
        ];
    }

    private function formatPhone(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '351')) {
            return '+' . substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3) . ' ' . substr($digits, 9, 3);
        }
        if (strlen($digits) === 9) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
        }
        return $number;
    }

    private function buildWaLink(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        return $digits !== '' ? 'https://wa.me/' . $digits : '';
    }

    private function resolveAttachmentUrl(int $attachmentId): string
    {
        if ($attachmentId <= 0) {
            return '';
        }
        if (function_exists('wp_get_attachment_image_url')) {
            $url = wp_get_attachment_image_url($attachmentId, 'full');
            return is_string($url) ? $url : '';
        }
        return '';
    }
}
