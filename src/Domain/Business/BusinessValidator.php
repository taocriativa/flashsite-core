<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

use FlashSite\Core\Core\Support\Formatters;
use FlashSite\Core\Core\Support\Validators;

final class BusinessValidator
{
    /** @return array{data: array<string, mixed>, errors: array<string, string>} */
    public function validate(array $input, array $existing = []): array
    {
        $data = [
            'identity' => [
                'business_name' => sanitize_text_field((string) $this->value($input, 'business_name', $existing, ['identity', 'business_name'], '')),
                'business_type' => sanitize_key((string) $this->value($input, 'business_type', $existing, ['identity', 'business_type'], '')),
                'tagline' => sanitize_text_field((string) $this->value($input, 'tagline', $existing, ['identity', 'tagline'], '')),
                'tax_id' => sanitize_text_field((string) $this->value($input, 'nif', $existing, ['identity', 'tax_id'], '')),
            ],
            'contact' => [
                'phone' => Formatters::phoneDigits((string) $this->value($input, 'phone', $existing, ['contact', 'phone'], '')),
                'whatsapp' => [
                    'number' => Formatters::phoneDigits((string) $this->value($input, 'whatsapp', $existing, ['contact', 'whatsapp', 'number'], '')),
                    'link' => esc_url_raw((string) $this->value($input, 'whatsapp_link', $existing, ['contact', 'whatsapp', 'link'], '')),
                ],
                'email_public' => sanitize_email((string) $this->value($input, 'email', $existing, ['contact', 'email_public'], '')),
                'email_admin' => sanitize_email((string) $this->value($input, 'email_admin', $existing, ['contact', 'email_admin'], '')),
            ],
            'location' => [
                'address' => sanitize_textarea_field((string) $this->value($input, 'address', $existing, ['location', 'address'], '')),
                'city_region' => sanitize_text_field((string) $this->value($input, 'city_region', $existing, ['location', 'city_region'], '')),
                'postal_code' => sanitize_text_field((string) $this->value($input, 'postal_code', $existing, ['location', 'postal_code'], '')),
                'country' => sanitize_text_field((string) $this->value($input, 'country', $existing, ['location', 'country'], '')),
                'google_maps_url' => esc_url_raw((string) $this->value($input, 'google_maps_url', $existing, ['location', 'google_maps_url'], '')),
                'google_maps_embed' => wp_kses_post((string) $this->value($input, 'google_maps_embed', $existing, ['location', 'google_maps_embed'], '')),
            ],
            'social' => [
                'website_url' => esc_url_raw((string) $this->value($input, 'website_url', $existing, ['social', 'website_url'], '')),
                'instagram' => esc_url_raw((string) $this->value($input, 'social_instagram', $existing, ['social', 'instagram'], '')),
                'facebook' => esc_url_raw((string) $this->value($input, 'social_facebook', $existing, ['social', 'facebook'], '')),
                'youtube' => esc_url_raw((string) $this->value($input, 'social_youtube', $existing, ['social', 'youtube'], '')),
                'linkedin' => esc_url_raw((string) $this->value($input, 'social_linkedin', $existing, ['social', 'linkedin'], '')),
                'tiktok' => esc_url_raw((string) $this->value($input, 'social_tiktok', $existing, ['social', 'tiktok'], '')),
                'doctoralia' => esc_url_raw((string) $this->value($input, 'social_doctoralia', $existing, ['social', 'doctoralia'], '')),
            ],
            'hours' => [
                'monday' => sanitize_text_field((string) $this->value($input, 'hours_monday', $existing, ['hours', 'monday'], '')),
                'tuesday' => sanitize_text_field((string) $this->value($input, 'hours_tuesday', $existing, ['hours', 'tuesday'], '')),
                'wednesday' => sanitize_text_field((string) $this->value($input, 'hours_wednesday', $existing, ['hours', 'wednesday'], '')),
                'thursday' => sanitize_text_field((string) $this->value($input, 'hours_thursday', $existing, ['hours', 'thursday'], '')),
                'friday' => sanitize_text_field((string) $this->value($input, 'hours_friday', $existing, ['hours', 'friday'], '')),
                'saturday' => sanitize_text_field((string) $this->value($input, 'hours_saturday', $existing, ['hours', 'saturday'], '')),
                'sunday' => sanitize_text_field((string) $this->value($input, 'hours_sunday', $existing, ['hours', 'sunday'], '')),
                'notes' => sanitize_textarea_field((string) $this->value($input, 'hours_notes', $existing, ['hours', 'notes'], '')),
                'legacy_text' => sanitize_textarea_field((string) $this->value($input, 'opening_hours', $existing, ['hours', 'legacy_text'], '')),
            ],
            'professional' => [
                'display_name' => sanitize_text_field((string) $this->value($input, 'professional_display_name', $existing, ['professional', 'display_name'], '')),
                'title' => sanitize_text_field((string) $this->value($input, 'professional_title', $existing, ['professional', 'title'], '')),
                'specialty' => sanitize_text_field((string) $this->value($input, 'professional_specialty', $existing, ['professional', 'specialty'], '')),
                'license' => sanitize_text_field((string) $this->value($input, 'professional_license', $existing, ['professional', 'license'], '')),
                'secondary_id' => sanitize_text_field((string) $this->value($input, 'professional_secondary_id', $existing, ['professional', 'secondary_id'], '')),
                'services' => $this->sanitizeListValue($this->value($input, 'professional_services', $existing, ['professional', 'services'], [])),
                'accepted_plans' => $this->sanitizeListValue($this->value($input, 'professional_accepted_plans', $existing, ['professional', 'accepted_plans'], [])),
            ],
            'context' => [
                'segment' => sanitize_key((string) $this->value($input, 'business_type', $existing, ['context', 'segment'], '')),
            ],
            'branding' => [
                'logo_light_id' => absint($this->value($input, 'branding_logo_light_id', $existing, ['branding', 'logo_light_id'], 0)),
                'logo_dark_id' => absint($this->value($input, 'branding_logo_dark_id', $existing, ['branding', 'logo_dark_id'], 0)),
                'primary_color' => $this->sanitizeColor((string) $this->value($input, 'branding_primary_color', $existing, ['branding', 'primary_color'], '')),
                'secondary_color' => $this->sanitizeColor((string) $this->value($input, 'branding_secondary_color', $existing, ['branding', 'secondary_color'], '')),
                'accent_color' => $this->sanitizeColor((string) $this->value($input, 'branding_accent_color', $existing, ['branding', 'accent_color'], '')),
                'font_primary' => sanitize_text_field((string) $this->value($input, 'branding_font_primary', $existing, ['branding', 'font_primary'], '')),
                'font_secondary' => sanitize_text_field((string) $this->value($input, 'branding_font_secondary', $existing, ['branding', 'font_secondary'], '')),
            ],
        ];

        $errors = [];
        if ($data['identity']['business_name'] === '') {
            $errors['business_name'] = 'O nome do negócio é obrigatório.';
        }
        if ($data['contact']['phone'] === '') {
            $errors['phone'] = 'O telefone principal é obrigatório.';
        }
        if ($data['contact']['email_public'] !== '' && ! Validators::isEmail($data['contact']['email_public'])) {
            $errors['email'] = 'O email público informado é inválido.';
        }
        if ($data['contact']['email_admin'] !== '' && ! Validators::isEmail($data['contact']['email_admin'])) {
            $errors['email_admin'] = 'O email administrativo informado é inválido.';
        }

        foreach ([
            'whatsapp_link' => $data['contact']['whatsapp']['link'],
            'website_url' => $data['social']['website_url'],
            'google_maps_url' => $data['location']['google_maps_url'],
            'social_instagram' => $data['social']['instagram'],
            'social_facebook' => $data['social']['facebook'],
            'social_youtube' => $data['social']['youtube'],
            'social_linkedin' => $data['social']['linkedin'],
            'social_tiktok' => $data['social']['tiktok'],
            'social_doctoralia' => $data['social']['doctoralia'],
        ] as $field => $url) {
            if ($url !== '' && ! Validators::isUrl($url)) {
                $errors[$field] = 'A URL informada é inválida.';
            }
        }

        foreach ([
            'branding_primary_color' => $data['branding']['primary_color'],
            'branding_secondary_color' => $data['branding']['secondary_color'],
            'branding_accent_color' => $data['branding']['accent_color'],
        ] as $field => $color) {
            $raw = array_key_exists($field, $input)
                ? trim((string) $input[$field])
                : trim((string) $this->nestedValue($existing, $this->brandingPathForField($field), ''));
            if ($raw !== '' && $color === '') {
                $errors[$field] = 'A cor informada deve estar em formato HEX.';
            }
        }

        return ['data' => BusinessProfile::normalize($data), 'errors' => $errors];
    }

    /** @return mixed */
    private function value(array $input, string $inputKey, array $existing, array $existingPath, mixed $default = ''): mixed
    {
        if (array_key_exists($inputKey, $input)) {
            return $input[$inputKey];
        }

        return $this->nestedValue($existing, $existingPath, $default);
    }

    /** @param array<int, string> $path
     * @return mixed
     */
    private function nestedValue(array $source, array $path, mixed $default = ''): mixed
    {
        $value = $source;
        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @param mixed $value
     * @return list<string>
     */
    private function sanitizeListValue(mixed $value): array
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $item = sanitize_text_field((string) $item);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            return $items;
        }

        return $this->sanitizeList((string) $value);
    }

    /** @return list<string> */
    private function sanitizeList(string $value): array
    {
        $lines = preg_split('/
||
/', $value) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = sanitize_text_field($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }
        return $items;
    }

    private function sanitizeColor(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($value[0] !== '#') {
            $value = '#' . $value;
        }

        $color = sanitize_hex_color($value);

        return is_string($color) ? strtoupper($color) : '';
    }

    /** @return array<int, string> */
    private function brandingPathForField(string $field): array
    {
        return match ($field) {
            'branding_primary_color' => ['branding', 'primary_color'],
            'branding_secondary_color' => ['branding', 'secondary_color'],
            'branding_accent_color' => ['branding', 'accent_color'],
            default => ['branding'],
        };
    }
}
