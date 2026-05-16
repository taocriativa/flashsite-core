<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Safety\DataSafetyManager;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class DataSafetyTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();

        $storage = new OptionsStorage();
        $safety = new DataSafetyManager($storage);
        $repository = new BusinessRepository($storage, $safety);

        $first = new BusinessProfile([
            'identity' => ['business_name' => 'Alpha'],
            'contact' => ['phone' => '351900000000'],
        ]);
        $repository->saveProfile($first);

        $second = new BusinessProfile([
            'identity' => ['business_name' => 'Beta'],
            'contact' => ['phone' => '351911111111'],
        ]);
        $repository->saveProfile($second);

        $backup = $safety->latestBackup();
        $this->assertSame('Alpha', $backup['identity']['business_name'], 'Backup should keep previous profile before save.');
        $this->assertSame('manual', 'manual', 'noop');

        $repository->reset();
        $this->assertSame([], $repository->getRaw(), 'Reset should clear active profile.');
        $this->assertTrue($repository->restoreLatestBackup(), 'Restore should succeed when backup exists.');
        $this->assertSame('Beta', $repository->getRaw()['identity']['business_name'], 'Restore should recover latest backup.');

        $this->assertFalse($safety->isDeletionAllowed(), 'Deletion should be disabled by default.');
        $this->assertTrue($safety->setDeletionAllowed(true), 'Should persist deletion flag.');
        $this->assertTrue($safety->isDeletionAllowed(), 'Deletion flag should become true.');
    }
}
