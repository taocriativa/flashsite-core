<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessTranslations;
use FlashSite\Core\Domain\Business\OpeningHours;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

// Garante que ElementorTagBase está disponível antes de ser usada como base desta classe.
require_once __DIR__ . '/ElementorTagBase.php';

abstract class BaseBusinessTag extends ElementorTagBase
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
            // Campos de texto com versão por idioma (@since 3.2.0-beta.5).
            default => (new BusinessTranslations(new OptionsStorage()))->resolve($path, $businessData->get($path, $default)),
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
        $language = Strings::language();
        $resolver = (new BusinessTranslations(new OptionsStorage()))->resolver($language);

        $application = Plugin::instance()->application();
        if ($application !== null) {
            $container = $application->container();
            if ($container->has(BusinessRepository::class)) {
                /** @var BusinessRepository $repository */
                $repository = $container->make(BusinessRepository::class);
                // Numa linha de texto do Elementor as quebras desaparecem: separar os grupos com " · ".
                return str_replace("\n", ' · ', $repository->getProfile()->getOpeningHours($language, $resolver));
            }
        }

        return OpeningHours::format((array) $businessData->get('hours', []), $language, $resolver, false);
    }
}
