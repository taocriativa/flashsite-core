<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

abstract class AbstractBusinessTextTag extends BaseBusinessTag
{
    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function get_value(array $options = []): string
    {
        $value = $this->resolveValue('');

        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }

        return trim((string) $value);
    }

    public function render(): void
    {
        echo esc_html($this->get_value());
    }
}
