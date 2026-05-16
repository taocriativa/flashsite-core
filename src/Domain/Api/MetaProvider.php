<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Api;

final class MetaProvider
{
    public function __construct(private PublicSerializer $publicSerializer) {}

    /** @return array<string, mixed> */
    public function publicMeta(): array
    {
        return [
            'api_version' => defined('FLASHSITE_CORE_VERSION') ? FLASHSITE_CORE_VERSION : 'dev',
            'data_version' => defined('FLASHSITE_DATA_VERSION') ? FLASHSITE_DATA_VERSION : 'dev',
            'generated_at' => gmdate('c'),
            'available_sections' => $this->publicSerializer->sections(),
            'visibility_rules' => $this->publicSerializer->visibilityRules(),
        ];
    }
}
