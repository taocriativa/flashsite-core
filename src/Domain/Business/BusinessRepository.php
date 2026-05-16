<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

use FlashSite\Core\Core\Contracts\RepositoryInterface;
use FlashSite\Core\Domain\Safety\DataSafetyManager;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class BusinessRepository implements RepositoryInterface
{
    private const OPTION_KEY = 'flashsite_business_profile';

    public function __construct(private OptionsStorage $storage, private ?DataSafetyManager $dataSafety = null) {}

    public function getProfile(): BusinessProfile
    {
        return new BusinessProfile($this->getRaw());
    }

    public function saveProfile(BusinessProfile $profile): bool
    {
        $current = $this->getRaw();
        if ($current !== [] && $this->dataSafety !== null) {
            $this->dataSafety->backupProfile($current, 'before_save');
        }

        return $this->storage->update(self::OPTION_KEY, $profile->toArray(), false);
    }

    /** @return array<string, mixed> */
    public function getRaw(): array
    {
        $raw = $this->storage->get(self::OPTION_KEY, []);
        $raw = is_array($raw) ? $raw : [];

        if ($this->isLegacyFormat($raw)) {
            $raw = $this->migrateLegacy($raw);
            $this->storage->update(self::OPTION_KEY, $raw, false);
        }

        return $raw;
    }

    public function migrateIfNeeded(): bool
    {
        $raw = $this->storage->get(self::OPTION_KEY, []);
        $raw = is_array($raw) ? $raw : [];
        if (! $this->isLegacyFormat($raw)) {
            return true;
        }
        return $this->storage->update(self::OPTION_KEY, $this->migrateLegacy($raw), false);
    }

    public function reset(): bool
    {
        $current = $this->getRaw();
        if ($current !== [] && $this->dataSafety !== null) {
            $this->dataSafety->backupProfile($current, 'before_reset');
        }

        return $this->storage->update(self::OPTION_KEY, [], false);
    }

    public function restoreLatestBackup(): bool
    {
        if ($this->dataSafety === null) {
            return false;
        }

        $backup = $this->dataSafety->latestBackup();
        if ($backup === []) {
            return false;
        }

        return $this->storage->update(self::OPTION_KEY, BusinessProfile::normalize($backup), false);
    }

    /** @param array<string, mixed> $data */
    public function isLegacyFormat(array $data): bool
    {
        return isset($data['business_name']) || isset($data['nif']) || isset($data['phone']) || isset($data['social_links']);
    }

    /** @param array<string, mixed> $legacy
     * @return array<string, mixed>
     */
    private function migrateLegacy(array $legacy): array
    {
        $socialLinks = is_array($legacy['social_links'] ?? null) ? $legacy['social_links'] : [];
        $openingHours = (string) ($legacy['opening_hours'] ?? '');

        return BusinessProfile::normalize([
            'identity' => [
                'business_name' => (string) ($legacy['business_name'] ?? ''),
                'business_type' => '',
                'tagline' => '',
                'tax_id' => (string) ($legacy['nif'] ?? ''),
            ],
            'contact' => [
                'phone' => (string) ($legacy['phone'] ?? ''),
                'whatsapp' => [
                    'number' => (string) ($legacy['whatsapp'] ?? ''),
                    'link' => '',
                ],
                'email_public' => (string) ($legacy['email'] ?? ''),
                'email_admin' => '',
            ],
            'location' => [
                'address' => (string) ($legacy['address'] ?? ''),
                'city_region' => '',
                'postal_code' => '',
                'country' => '',
                'google_maps_url' => '',
                'google_maps_embed' => '',
            ],
            'social' => [
                'website_url' => (string) ($legacy['website_url'] ?? ''),
                'instagram' => (string) ($socialLinks['instagram'] ?? ''),
                'facebook' => (string) ($socialLinks['facebook'] ?? ''),
                'youtube' => '',
                'linkedin' => '',
                'tiktok' => '',
                'doctoralia' => '',
            ],
            'hours' => [
                'monday' => '',
                'tuesday' => '',
                'wednesday' => '',
                'thursday' => '',
                'friday' => '',
                'saturday' => '',
                'sunday' => '',
                'notes' => '',
                'legacy_text' => $openingHours,
            ],
            'professional' => [
                'display_name' => '',
                'title' => '',
                'specialty' => '',
                'license' => '',
                'secondary_id' => '',
                'services' => [],
                'accepted_plans' => [],
            ],
            'context' => [
                'segment' => '',
            ],
        ]);
    }
}
