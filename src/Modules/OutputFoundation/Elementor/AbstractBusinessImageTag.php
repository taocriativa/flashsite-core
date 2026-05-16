<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

abstract class AbstractBusinessImageTag extends BaseBusinessTag
{
    public function get_categories(): array
    {
        return [
            \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::MEDIA_CATEGORY,
        ];
    }

    public function get_value(array $options = []): array
    {
        $attachmentId = absint($this->resolveValue(0));
        $url = $attachmentId > 0 ? (string) wp_get_attachment_image_url($attachmentId, 'full') : '';

        if ($attachmentId <= 0 || $url === '') {
            return [
                'id' => 0,
                'url' => '',
            ];
        }

        return [
            'id' => $attachmentId,
            'url' => $url,
        ];
    }

    public function render(): void
    {
        $value = $this->get_value();
        echo esc_url((string) ($value['url'] ?? ''));
    }
}
