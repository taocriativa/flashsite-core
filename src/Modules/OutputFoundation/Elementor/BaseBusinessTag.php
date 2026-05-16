<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessRepository;

abstract class BaseBusinessTag extends \Elementor\Core\DynamicTags\Data_Tag
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    abstract protected function getTagSlug(): string;
    abstract protected function getTagTitle(): string;
    abstract protected function getTagPath(): string;

    public function get_name(): string
    {
        return $this->getTagSlug();
    }

    public function get_title(): string
    {
        return $this->getTagTitle();
    }

    public function get_group(): string
    {
        return 'flashsite';
    }

    protected function resolveValue(mixed $default = null): mixed
    {
        $application = Plugin::instance()->application();
        if ($application === null) {
            return $default;
        }

        $container = $application->container();
        if (! $container->has(BusinessData::class)) {
            return $default;
        }

        /** @var BusinessData $businessData */
        $businessData = $container->make(BusinessData::class);
        $path = $this->getTagPath();

        return match ($path) {
            '_flashsite.format.phone_link' => $this->formatPhoneLink($businessData),
            '_flashsite.format.email_link' => $this->formatEmailLink($businessData),
            '_flashsite.format.full_address' => $this->formatFullAddress($businessData),
            '_flashsite.format.hours_formatted' => $this->formatHours($businessData),
            default => $businessData->get($path, $default),
        };
    }

    private function formatPhoneLink(BusinessData $businessData): string
    {
        $phone = trim((string) $businessData->get('contact.phone', ''));
        if ($phone === '') {
            return '';
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phone) ?: '';
        return $normalized !== '' ? 'tel:' . $normalized : '';
    }

    private function formatEmailLink(BusinessData $businessData): string
    {
        $email = trim((string) $businessData->get('contact.email_public', ''));
        return $email !== '' ? 'mailto:' . $email : '';
    }

    private function formatFullAddress(BusinessData $businessData): string
    {
        $parts = array_filter([
            trim((string) $businessData->get('location.address', '')),
            trim((string) $businessData->get('location.city_region', '')),
            trim((string) $businessData->get('location.postal_code', '')),
            trim((string) $businessData->get('location.country', '')),
        ], static fn (string $value): bool => $value !== '');

        return implode(', ', $parts);
    }

    private function formatHours(BusinessData $businessData): string
    {
        $application = Plugin::instance()->application();
        if ($application !== null) {
            $container = $application->container();
            if ($container->has(BusinessRepository::class)) {
                /** @var BusinessRepository $repository */
                $repository = $container->make(BusinessRepository::class);
                return $repository->getProfile()->getOpeningHours();
            }
        }

        $ordered = [
            'Seg' => (string) $businessData->get('hours.monday', ''),
            'Ter' => (string) $businessData->get('hours.tuesday', ''),
            'Qua' => (string) $businessData->get('hours.wednesday', ''),
            'Qui' => (string) $businessData->get('hours.thursday', ''),
            'Sex' => (string) $businessData->get('hours.friday', ''),
            'Sáb' => (string) $businessData->get('hours.saturday', ''),
            'Dom' => (string) $businessData->get('hours.sunday', ''),
        ];

        $lines = [];
        foreach ($ordered as $label => $value) {
            $value = trim($value);
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }

        $notes = trim((string) $businessData->get('hours.notes', ''));
        if ($notes !== '') {
            $lines[] = $notes;
        }

        if ($lines === []) {
            return trim((string) $businessData->get('hours.legacy_text', ''));
        }

        return implode("\n", $lines);
    }
}
