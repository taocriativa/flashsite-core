<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Safety;

use FlashSite\Core\Core\InstallationGuard;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class DataSafetyManager
{
    private const BACKUP_KEY = 'flashsite_business_profile_backup';
    private const BACKUP_META_KEY = 'flashsite_business_profile_backup_meta';
    private const DELETE_FLAG_KEY = 'flashsite_allow_data_deletion';

    public function __construct(private OptionsStorage $storage) {}

    /** @param array<string,mixed> $profile */
    public function backupProfile(array $profile, string $reason = 'manual'): bool
    {
        if ($profile === []) {
            return false;
        }

        $meta = [
            'created_at' => gmdate('c'),
            'reason' => $reason,
            'version' => defined('FLASHSITE_DATA_VERSION') ? FLASHSITE_DATA_VERSION : 'dev',
            'schema' => 'business_profile_v1',
            'checksum' => md5(wp_json_encode($profile) ?: ''),
        ];

        $saved = $this->storage->update(self::BACKUP_KEY, $profile, false);
        $saved = $this->storage->update(self::BACKUP_META_KEY, $meta, false) && $saved;

        return $saved;
    }

    /** @return array<string,mixed> */
    public function latestBackup(): array
    {
        $backup = $this->storage->get(self::BACKUP_KEY, []);
        return is_array($backup) ? $backup : [];
    }

    /** @return array<string,mixed> */
    public function latestBackupMeta(): array
    {
        $meta = $this->storage->get(self::BACKUP_META_KEY, []);
        return is_array($meta) ? $meta : [];
    }

    public function hasBackup(): bool
    {
        return $this->latestBackup() !== [];
    }

    public function setDeletionAllowed(bool $allowed): bool
    {
        if ($allowed && ! InstallationGuard::canWriteSensitive()) {
            return false;
        }
        return $this->storage->update(self::DELETE_FLAG_KEY, $allowed, true);
    }

    public function isDeletionAllowed(): bool
    {
        return $this->storage->get(self::DELETE_FLAG_KEY, false) === true;
    }
}
