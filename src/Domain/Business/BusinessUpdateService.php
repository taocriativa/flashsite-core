<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\InstallationGuard;

/**
 * Regra única de gravação dos Dados do Negócio (REST privada e Abilities).
 *
 * Extraída do ApiAccessModule na 3.5.0 sem mudar comportamento: bloqueio da InstallationGuard,
 * achatamento de payloads (aliases por secção), validação, persistência e log.
 *
 * Resultado: ['ok' => true, 'section' => string, 'data' => array]
 *         ou ['ok' => false, 'code' => string, 'message' => string, 'status' => int, 'errors' => array]
 *
 * @since 3.5.0
 */
final class BusinessUpdateService
{
    public const SECTIONS = ['identity', 'contact', 'location', 'social', 'hours', 'professional', 'context', 'branding'];

    public function __construct(
        private BusinessRepository $repository,
        private BusinessValidator $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function update(array $payload, string $forcedSection = ''): array
    {
        if (! InstallationGuard::canWriteSensitive()) {
            return $this->fail('installation_protected', InstallationGuard::blockReason(), 409);
        }

        $existing = $this->repository->getRaw();
        $flatPayload = $this->flattenInput($payload, $forcedSection);
        if ($flatPayload === []) {
            return $this->fail('empty_payload', 'No writable data was provided.', 400);
        }

        $result = $this->validator->validate($flatPayload, $existing);
        if ($result['errors'] !== []) {
            $this->logger->warning('API business profile update validation failed.', [
                'section' => $forcedSection,
                'fields' => array_keys($result['errors']),
            ]);
            return $this->fail('validation_failed', 'The provided payload is invalid.', 422, ['errors' => $result['errors']]);
        }

        $saved = $this->repository->saveProfile(new BusinessProfile($result['data']));
        if (! $saved) {
            $this->logger->error('API business profile update failed to persist.', ['section' => $forcedSection]);
            return $this->fail('save_failed', 'Unable to persist business profile.', 500);
        }

        $this->logger->info('API business profile updated.', ['section' => $forcedSection === '' ? 'all' : $forcedSection]);
        $fresh = $this->repository->getProfile()->toArray();

        return [
            'ok' => true,
            'section' => $forcedSection,
            'data' => $forcedSection === '' ? $fresh : [$forcedSection => $fresh[$forcedSection] ?? []],
        ];
    }

    /** @return array<string,mixed> */
    private function fail(string $code, string $message, int $status, array $extra = []): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'status' => $status] + $extra;
    }

    public function isKnownSection(string $section): bool
    {
        return in_array($section, self::SECTIONS, true);
    }

    /** @param array<string,mixed> $payload
     *  @return array<string,mixed>
     */
    private function flattenInput(array $payload, string $forcedSection = ''): array
    {
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
        if ($forcedSection !== '') {
            if (isset($data[$forcedSection]) && is_array($data[$forcedSection])) {
                $data = [$forcedSection => $data[$forcedSection]];
            } elseif (! $this->looksLikeFlatPayload($data)) {
                $data = [$forcedSection => $data];
            }
        }

        if ($this->looksLikeFlatPayload($data)) {
            return $this->normalizeFlatAliases($data, $forcedSection);
        }

        $flat = [];
        $identity = is_array($data['identity'] ?? null) ? $data['identity'] : [];
        foreach (['business_name' => 'business_name', 'business_type' => 'business_type', 'tagline' => 'tagline', 'tax_id' => 'nif'] as $src => $dst) {
            if (array_key_exists($src, $identity)) { $flat[$dst] = $identity[$src]; }
        }

        $contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
        if (array_key_exists('phone', $contact)) { $flat['phone'] = $contact['phone']; }
        $whatsapp = is_array($contact['whatsapp'] ?? null) ? $contact['whatsapp'] : [];
        if (array_key_exists('number', $whatsapp)) { $flat['whatsapp'] = $whatsapp['number']; }
        if (array_key_exists('link', $whatsapp)) { $flat['whatsapp_link'] = $whatsapp['link']; }
        if (array_key_exists('email_public', $contact)) { $flat['email'] = $contact['email_public']; }
        if (array_key_exists('email_admin', $contact)) { $flat['email_admin'] = $contact['email_admin']; }

        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        foreach (['address','city_region','postal_code','country','google_maps_url','google_maps_embed'] as $field) {
            if (array_key_exists($field, $location)) { $flat[$field] = $location[$field]; }
        }

        $social = is_array($data['social'] ?? null) ? $data['social'] : [];
        foreach (['website_url','instagram','facebook','youtube','linkedin','tiktok','doctoralia'] as $field) {
            if (array_key_exists($field, $social)) {
                $flat[$field === 'website_url' ? 'website_url' : 'social_' . $field] = $social[$field];
            }
        }

        $hours = is_array($data['hours'] ?? null) ? $data['hours'] : [];
        foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday','notes'] as $field) {
            if (array_key_exists($field, $hours)) { $flat['hours_' . $field] = $hours[$field]; }
        }

        $professional = is_array($data['professional'] ?? null) ? $data['professional'] : [];
        foreach (['display_name','title','specialty','license','secondary_id'] as $field) {
            if (array_key_exists($field, $professional)) { $flat['professional_' . $field] = $professional[$field]; }
        }
        if (array_key_exists('services', $professional)) { $flat['professional_services'] = $professional['services']; }
        if (array_key_exists('accepted_plans', $professional)) { $flat['professional_accepted_plans'] = $professional['accepted_plans']; }

        $branding = is_array($data['branding'] ?? null) ? $data['branding'] : [];
        foreach (['logo_light_id','logo_dark_id','primary_color','secondary_color','accent_color','font_primary','font_secondary'] as $field) {
            if (array_key_exists($field, $branding)) { $flat['branding_' . $field] = $branding[$field]; }
        }

        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        if (array_key_exists('segment', $context) && !array_key_exists('business_type', $flat)) {
            $flat['business_type'] = $context['segment'];
        }

        return $flat;
    }


    /** @param array<string,mixed> $data
     *  @return array<string,mixed>
     */
    private function normalizeFlatAliases(array $data, string $forcedSection = ''): array
    {
        if ($forcedSection === 'contact') {
            if (array_key_exists('email_public', $data) && !array_key_exists('email', $data)) {
                $data['email'] = $data['email_public'];
                unset($data['email_public']);
            }
            if (is_array($data['whatsapp'] ?? null)) {
                $whatsapp = $data['whatsapp'];
                if (array_key_exists('number', $whatsapp) && !array_key_exists('whatsapp', $data)) {
                    $data['whatsapp'] = $whatsapp['number'];
                }
                if (array_key_exists('link', $whatsapp) && !array_key_exists('whatsapp_link', $data)) {
                    $data['whatsapp_link'] = $whatsapp['link'];
                }
            }
        }

        if ($forcedSection === 'identity' && array_key_exists('tax_id', $data) && !array_key_exists('nif', $data)) {
            $data['nif'] = $data['tax_id'];
            unset($data['tax_id']);
        }

        if ($forcedSection === 'branding') {
            foreach (['logo_light_id','logo_dark_id','primary_color','secondary_color','accent_color','font_primary','font_secondary'] as $field) {
                if (array_key_exists($field, $data) && !array_key_exists('branding_' . $field, $data)) {
                    $data['branding_' . $field] = $data[$field];
                    unset($data[$field]);
                }
            }
        }

        if ($forcedSection === 'professional') {
            foreach (['display_name','title','specialty','license','secondary_id','services','accepted_plans'] as $field) {
                if (array_key_exists($field, $data) && !array_key_exists('professional_' . $field, $data)) {
                    $data['professional_' . $field] = $data[$field];
                    unset($data[$field]);
                }
            }
        }

        if ($forcedSection === 'location') {
            // location field names already match validator keys.
            return $data;
        }

        if ($forcedSection === 'social') {
            foreach (['instagram','facebook','youtube','linkedin','tiktok','doctoralia'] as $field) {
                if (array_key_exists($field, $data) && !array_key_exists('social_' . $field, $data)) {
                    $data['social_' . $field] = $data[$field];
                    unset($data[$field]);
                }
            }
        }

        if ($forcedSection === 'hours') {
            foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday','notes'] as $field) {
                if (array_key_exists($field, $data) && !array_key_exists('hours_' . $field, $data)) {
                    $data['hours_' . $field] = $data[$field];
                    unset($data[$field]);
                }
            }
        }

        if ($forcedSection === 'context' && array_key_exists('segment', $data) && !array_key_exists('business_type', $data)) {
            $data['business_type'] = $data['segment'];
            unset($data['segment']);
        }

        return $data;
    }

    /** @param array<string,mixed> $data */
    private function looksLikeFlatPayload(array $data): bool
    {
        foreach (['business_name','phone','email','email_admin','nif','branding_primary_color','professional_license'] as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }
        return false;
    }
}
