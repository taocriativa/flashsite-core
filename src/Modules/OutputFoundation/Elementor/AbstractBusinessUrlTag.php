<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

abstract class AbstractBusinessUrlTag extends BaseBusinessTag
{
    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::URL_CATEGORY];
    }

    public function get_value(array $options = []): string
    {
        return trim((string) $this->resolveValue(''));
    }

    public function render(): void
    {
        echo esc_url($this->get_value());
    }
}
