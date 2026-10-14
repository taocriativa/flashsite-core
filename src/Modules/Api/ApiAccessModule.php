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
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessUpdateService;
use FlashSite\Core\Domain\Business\BusinessValidator;

final class ApiAccessModule implements ModuleInterface
{
    private const REST_NAMESPACE = 'flashsite/v1';

    private BusinessUpdateService $updater;

    public function __construct(
        private BusinessData $businessData,
        private PublicSerializer $publicSerializer,
        private OutputResolver $outputResolver,
        private MetaProvider $metaProvider,
        private BusinessRepository $repository,
        private BusinessValidator $validator,
        private LoggerInterface $logger,
        ?BusinessUpdateService $updater = null
    ) {
        $this->updater = $updater ?? new BusinessUpdateService($repository, $validator, $logger);
    }

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
        $result = $this->updater->update($this->extractPayload($request), $forcedSection);
        if ($result['ok'] !== true) {
            $extra = isset($result['errors']) ? ['errors' => $result['errors']] : [];
            return $this->respondError((string) $result['code'], (string) $result['message'], (int) $result['status'], $extra);
        }

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
            'data' => $result['data'],
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
        return $this->updater->isKnownSection($section);
    }
}
