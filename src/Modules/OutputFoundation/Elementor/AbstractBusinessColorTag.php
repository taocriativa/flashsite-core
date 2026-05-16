<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

abstract class AbstractBusinessColorTag extends BaseBusinessTag
{
    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::COLOR_CATEGORY];
    }

    public function get_value(array $options = []): string
    {
        return trim((string) $this->resolveValue(''));
    }

    public function render(): void
    {
        echo esc_html($this->get_value());
    }
}
