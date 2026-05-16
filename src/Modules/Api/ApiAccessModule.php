<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Api;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\InstallationGuard;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Api\MetaProvider;
use FlashSite\Core\Domain\Api\OutputResolver;
use FlashSite\Core\Domain\Api\PublicSerializer;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessValidator;

final class ApiAccessModule implements ModuleInterface
{
    private const REST_NAMESPACE = 'flashsite/v1';

    public function __construct(
        private BusinessData $businessData,
        private PublicSerializer $publicSerializer,
        private OutputResolver $outputResolver,
        private MetaProvider $metaProvider,
        private BusinessRepository $repository,
        private BusinessValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function boot(): void
    {
        $this->logger->info('API Access module booted.');
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'api-access';
    }

    public function registerRoutes(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/public/business-profile', [
            'methods' => 'GET',
            'callback' => [$this, 'getPublicProfile'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::REST_NAMESPACE, '/public/business-profile/(?P<section>[a-z_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getPublicSection'],
            'permission_callback' => '__return_true',
            'args' => [
                'section' => [
                    'sanitize_callback' => 'sanitize_key',
                    'required' => true,
                ],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/public/meta', [
            'methods' => 'GET',
            'callback' => [$this, 'getPublicMeta'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::REST_NAMESPACE, '/public/output', [
            'methods' => 'GET',
            'callback' => [$this, 'getPublicOutput'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::REST_NAMESPACE, '/business-profile', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getPrivateProfile'],
                'permission_callback' => [$this, 'canReadPrivateProfile'],
            ],
            [
                'methods' => 'POST,PUT,PATCH',
                'callback' => [$this, 'updatePrivateProfile'],
                'permission_callback' => [$this, 'canWritePrivateProfile'],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/business-profile/(?P<section>[a-z_-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getPrivateSection'],
                'permission_callback' => [$this, 'canReadPrivateProfile'],
                'args' => [
                    'section' => [
                        'sanitize_callback' => 'sanitize_key',
                        'required' => true,
                    ],
                ],
            ],
            [
                'methods' => 'POST,PUT,PATCH',
                'callback' => [$this, 'updatePrivateSection'],
                'permission_callback' => [$this, 'canWritePrivateProfile'],
                'args' => [
                    'section' => [
                        'sanitize_callback' => 'sanitize_key',
                        'required' => true,
                    ],
                ],
            ],
        ]);
    }

    public function canReadPrivateProfile(): bool
    {
        return function_exists('current_user_can') && current_user_can('flashsite_manage_business_data');
    }

    public function canWritePrivateProfile(): bool
    {
        return $this->canReadPrivateProfile() && InstallationGuard::canWriteSensitive();
    }

    public function getPublicProfile(mixed $request = null): mixed
    {
        $payload = $this->buildPublicPayload();
        return $this->respond($this->buildEnvelope('public', $payload));
    }

    public function getPrivateProfile(mixed $request = null): mixed
    {
        return $this->respond($this->buildEnvelope('private', $this->businessData->all()));
    }

    public function updatePrivateProfile(mixed $request = null): mixed
    {
        return $this->handleUpdate('', $request);
    }

    public function updatePrivateSection(mixed $request = null): mixed
    {
        $section = $this->extractSection($request);
        if ($section === '' || ! $this->isKnownSection($section)) {
            return $this->respondError('invalid_section', 'Requested private section is not available.', 404);
        }

        return $this->handleUpdate($section, $request);
    }

    public function getPublicSection(mixed $request = null): mixed
    {
        $section = $this->extractSection($request);
        $payload = $this->buildPublicPayload();
        if ($section === '' || !array_key_exists($section, $payload)) {
            return $this->respondError('invalid_section', 'Requested public section is not available.', 404);
        }

        return $this->respond($this->buildEnvelope('public', [$section => $payload[$section]], $section));
    }

    public function getPrivateSection(mixed $request = null): mixed
    {
        $section = $this->extractSection($request);
        $payload = $this->businessData->all();
        if ($section === '' || !array_key_exists($section, $payload)) {
            return $this->respondError('invalid_section', 'Requested private section is not available.', 404);
        }
        return $this->respond($this->buildEnvelope('private', [$section => $payload[$section]], $section));
    }

    public function getPublicMeta(mixed $request = null): mixed
    {
        return $this->respond($this->metaProvider->publicMeta());
    }

    public function getPublicOutput(mixed $request = null): mixed
    {
        return $this->respond([
            'meta' => [
                'api_version' => defined('FLASHSITE_CORE_VERSION') ? FLASHSITE_CORE_VERSION : 'dev',
                'generated_at' => gmdate('c'),
                'visibility' => 'public',
                'type' => 'resolved_output',
            ],
            'data' => $this->outputResolver->resolve($this->buildPublicPayload()),
        ]);
    }

    /** @return array<string, mixed> */
    private function buildPublicPayload(): array
    {
        return $this->publicSerializer->serialize($this->businessData->all());
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function buildEnvelope(string $visibility, array $payload, string $section = ''): array
    {
        $meta = [
            'api_version' => defined('FLASHSITE_CORE_VERSION') ? FLASHSITE_CORE_VERSION : 'dev',
            'data_version' => defined('FLASHSITE_DATA_VERSION') ? FLASHSITE_DATA_VERSION : 'dev',
            'generated_at' => gmdate('c'),
            'visibility' => $visibility,
            'section' => $section,
        ];

        return [
            'namespace' => self::REST_NAMESPACE,
            'visibility' => $visibility,
            'section' => $section,
            'version' => $meta['api_version'],
            'generated_at' => $meta['generated_at'],
            'meta' => $meta,
            'data' => $payload,
        ];
    }

    private function extractSection(mixed $request): string
    {
        if (is_object($request) && method_exists($request, 'get_param')) {
            return sanitize_key((string) $request->get_param('section'));
        }
        if (is_array($request) && isset($request['section'])) {
            return sanitize_key((string) $request['section']);
        }
        return '';
    }

    private function respond(mixed $payload): mixed
    {
        return function_exists('rest_ensure_response') ? rest_ensure_response($payload) : $payload;
    }

    private function respondError(string $code, string $message, int $status, array $extra = []): mixed
    {
        $data = ['status' => $status];
        if ($extra !== []) {
            $data = array_merge($data, $extra);
        }
        if (class_exists('WP_Error')) {
            return new \WP_Error($code, $message, $data);
        }
        return $this->respond([
            'error' => $code,
            'message' => $message,
            'status' => $status,
            'details' => $extra,
        ]);
    }

    private function handleUpdate(string $forcedSection, mixed $request): mixed
    {
        if (! InstallationGuard::canWriteSensitive()) {
            return $this->respondError('installation_protected', InstallationGuard::blockReason(), 409);
        }

        $existing = $this->repository->getRaw();
        $flatPayload = $this->flattenInput($this->extractPayload($request), $forcedSection);
        if ($flatPayload === []) {
            return $this->respondError('empty_payload', 'No writable data was provided.', 400);
        }

        $result = $this->validator->validate($flatPayload, $existing);
        if ($result['errors'] !== []) {
            $this->logger->warning('API business profile update validation failed.', [
                'section' => $forcedSection,
                'fields' => array_keys($result['errors']),
            ]);
            return $this->respondError('validation_failed', 'The provided payload is invalid.', 422, ['errors' => $result['errors']]);
        }

        $saved = $this->repository->saveProfile(new BusinessProfile($result['data']));
        if (! $saved) {
            $this->logger->error('API business profile update failed to persist.', ['section' => $forcedSection]);
            return $this->respondError('save_failed', 'Unable to persist business profile.', 500);
        }

        $this->logger->info('API business profile updated.', ['section' => $forcedSection === '' ? 'all' : $forcedSection]);
        $fresh = $this->repository->getProfile()->toArray();
        $payload = $forcedSection === '' ? $fresh : [$forcedSection => $fresh[$forcedSection] ?? []];

        return $this->respond([
            'success' => true,
            'updated_section' => $forcedSection,
            'meta' => [
                'api_version' => defined('FLASHSITE_CORE_VERSION') ? FLASHSITE_CORE_VERSION : 'dev',
                'data_version' => defined('FLASHSITE_DATA_VERSION') ? FLASHSITE_DATA_VERSION : 'dev',
                'generated_at' => gmdate('c'),
                'visibility' => 'private',
                'operation' => 'update',
            ],
            'data' => $payload,
        ]);
    }

    /** @return array<string,mixed> */
    private function extractPayload(mixed $request): array
    {
        if (is_object($request)) {
            if (method_exists($request, 'get_json_params')) {
                $payload = $request->get_json_params();
                if (is_array($payload)) {
                    return $payload;
                }
            }
            if (method_exists($request, 'get_params')) {
                $payload = $request->get_params();
                if (is_array($payload)) {
                    return $payload;
                }
            }
        }
        return is_array($request) ? $request : [];
    }

    private function isKnownSection(string $section): bool
    {
        return in_array($section, ['identity','contact','location','social','hours','professional','context','branding'], true);
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
