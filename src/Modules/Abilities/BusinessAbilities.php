<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessUpdateService;

/**
 * Dados do Negócio. Leitura sobre o mesmo BusinessData da REST privada; escrita sobre o
 * BusinessUpdateService, o mesmo que a REST usa (InstallationGuard, validação e log incluídos).
 *
 * @since 3.5.0
 */
final class BusinessAbilities implements AbilityProvider
{
    public function __construct(
        private BusinessData $business,
        private BusinessUpdateService $updater,
        private AbilityGate $gate,
    ) {}

    public function definitions(): array
    {
        $section = ['type' => 'string', 'enum' => BusinessUpdateService::SECTIONS, 'description' => 'Secção dos Dados do Negócio.'];
        return [
            'flashsite/get-business-data' => [
                'label' => 'Ler Dados do Negócio',
                'description' => 'Dados do Negócio completos (visão de gestão, não a pública), ou só uma secção.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => ['type' => 'object', 'properties' => ['section' => $section]],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'section' => ['type' => 'string'],
                        'data' => ['type' => 'object'],
                        'meta' => ['type' => 'object'],
                    ],
                    'required' => ['data'],
                ],
                'execute_callback' => [$this, 'get'],
                'permission_callback' => fn (): bool => $this->gate->manageBusiness(),
                'meta' => AbilityMeta::meta(true, true),
            ],
            'flashsite/update-business-data' => [
                'label' => 'Atualizar Dados do Negócio',
                'description' => 'Atualiza Dados do Negócio (patch). Com "section", "data" são os campos dessa secção; sem ela, um objeto com várias secções. Mesmas regras de validação e proteção de instalação da REST.',
                'category' => AbilityMeta::CATEGORY,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'section' => $section,
                        'data' => ['type' => 'object', 'description' => 'Campos a alterar, no formato da REST /business-profile.'],
                    ],
                    'required' => ['data'],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean'],
                        'section' => ['type' => 'string'],
                        'data' => ['type' => 'object'],
                    ],
                    'required' => ['success', 'data'],
                ],
                'execute_callback' => [$this, 'update'],
                'permission_callback' => fn (): bool => $this->gate->manageBusiness(),
                'meta' => AbilityMeta::meta(false, true),
            ],
        ];
    }

    /** @return array<string, mixed>|\WP_Error */
    public function get(mixed $input = null): array|\WP_Error
    {
        $section = is_array($input) ? (string) ($input['section'] ?? '') : '';
        $all = $this->business->all();
        $meta = ['api_version' => defined('FLASHSITE_CORE_VERSION') ? (string) FLASHSITE_CORE_VERSION : 'dev', 'data_version' => defined('FLASHSITE_DATA_VERSION') ? (string) FLASHSITE_DATA_VERSION : 'dev'];
        if ($section === '') {
            return ['section' => '', 'data' => $all, 'meta' => $meta];
        }
        if (! array_key_exists($section, $all)) {
            return AbilityMeta::error('invalid_section', 'Requested private section is not available.', 404);
        }
        return ['section' => $section, 'data' => [$section => $all[$section]], 'meta' => $meta];
    }

    /** @return array<string, mixed>|\WP_Error */
    public function update(mixed $input = null): array|\WP_Error
    {
        $input = is_array($input) ? $input : [];
        $section = (string) ($input['section'] ?? '');
        if ($section !== '' && ! $this->updater->isKnownSection($section)) {
            return AbilityMeta::error('invalid_section', 'Requested private section is not available.', 404);
        }
        $result = $this->updater->update(is_array($input['data'] ?? null) ? $input['data'] : [], $section);
        if ($result['ok'] !== true) {
            return AbilityMeta::fromFailure($result);
        }
        return ['success' => true, 'section' => $section, 'data' => $result['data']];
    }
}
