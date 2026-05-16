<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\BusinessData;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\InstallationGuard;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessValidator;

final class BusinessDataModule implements ModuleInterface
{
    public function __construct(
        private BusinessRepository $repository,
        private BusinessValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function register(): void
    {
        add_action('flashsite_business_data_save', [$this, 'handleSave'], 10, 1);
    }

    public function boot(): void
    {
        $this->logger->info('BusinessData module booted.');
    }

    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'business-data'; }

    public function handleSave(array $payload): array
    {
        if (! InstallationGuard::canWriteSensitive()) {
            $this->logger->warning('Business data save blocked by installation protection mode.', ['reason' => InstallationGuard::blockReason()]);
            return ['success' => false, 'errors' => ['installation' => InstallationGuard::blockReason()]];
        }

        $result = $this->validator->validate($payload, $this->repository->getRaw());
        if ($result['errors'] !== []) {
            $this->logger->warning('Business data validation failed.', ['fields' => array_keys($result['errors'])]);
            return ['success' => false, 'errors' => $result['errors']];
        }

        $saved = $this->repository->saveProfile(new BusinessProfile($result['data']));
        if ($saved) { $this->logger->info('Business data saved.'); }
        return ['success' => $saved, 'errors' => []];
    }
}
