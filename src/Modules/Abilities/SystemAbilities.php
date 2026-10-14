<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;

/**
 * flashsite/get-context: contexto não sensível do site FlashSite.
 *
 * @since 3.5.0
 */
final class SystemAbilities implements AbilityProvider
{
    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private CollectionSettings $settings,
        private AbilityGate $gate,
    ) {}

    public function definitions(): array
    {
        return [
            'flashsite/get-context' => [
                'label' => 'Contexto do site FlashSite',
                'description' => 'Devolve versões, mercado, moeda, idioma e as coleções disponíveis e ativas. Só leitura; sem credenciais nem dados sensíveis.',
                'category' => AbilityMeta::CATEGORY,
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'core_version' => ['type' => 'string', 'description' => 'Versão do FlashSite Core.'],
                        'data_version' => ['type' => 'string', 'description' => 'Versão do esquema de dados.'],
                        'wordpress_version' => ['type' => 'string'],
                        'abilities_api' => ['type' => 'boolean', 'description' => 'A Abilities API do WordPress está disponível.'],
                        'market' => ['type' => 'string', 'description' => 'Mercado do site (PT ou BR).'],
                        'currency' => ['type' => 'string', 'description' => 'Código ISO da moeda dos preços.'],
                        'locale' => ['type' => 'string'],
                        'multilingual' => ['type' => 'boolean', 'description' => 'O site tem vários idiomas ativos.'],
                        'collections_available' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'collections_active' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'registry_errors' => ['type' => 'object', 'description' => 'Presets ignorados por serem inválidos: ficheiro => motivo.'],
                    ],
                    'required' => ['core_version', 'market', 'currency', 'collections_available', 'collections_active'],
                ],
                'execute_callback' => [$this, 'getContext'],
                'permission_callback' => fn (): bool => $this->gate->anyFlashSite(),
                'meta' => AbilityMeta::meta(true, true),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getContext(mixed $input = null): array
    {
        $known = $this->registry->keys();
        return [
            'core_version' => defined('FLASHSITE_CORE_VERSION') ? (string) FLASHSITE_CORE_VERSION : '',
            'data_version' => defined('FLASHSITE_DATA_VERSION') ? (string) FLASHSITE_DATA_VERSION : '',
            'wordpress_version' => function_exists('get_bloginfo') ? (string) get_bloginfo('version') : '',
            'abilities_api' => function_exists('wp_register_ability'),
            'market' => $this->settings->market(),
            'currency' => $this->settings->currency()->code,
            'locale' => function_exists('get_locale') ? (string) get_locale() : '',
            'multilingual' => (bool) apply_filters('flashsite/multilingual_active', false),
            'collections_available' => $known,
            'collections_active' => array_values(array_filter($this->activation->activeKeys(), static fn (string $k): bool => in_array($k, $known, true))),
            'registry_errors' => $this->registry->errors(),
        ];
    }
}
