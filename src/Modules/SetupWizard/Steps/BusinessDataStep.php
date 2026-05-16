<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SetupWizard\Steps;

use FlashSite\Core\Core\Contracts\SetupStepInterface;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessValidator;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingState;

final class BusinessDataStep implements SetupStepInterface
{
    public function __construct(
        private BusinessRepository $businessRepository,
        private BusinessValidator $businessValidator,
        private OnboardingRepository $onboardingRepository
    ) {}

    public function getKey(): string { return 'business-data'; }
    public function getLabel(): string { return 'Dados do Negócio'; }
    public function getOrder(): int { return 2; }
    public function isCompleted(): bool { return (bool) ($this->onboardingRepository->getState()->getSteps()['business-data'] ?? false); }

    public function validate(array $data): array
    {
        return $this->businessValidator->validate($data, $this->businessRepository->getRaw())['errors'];
    }

    public function save(array $data): void
    {
        $validated = $this->businessValidator->validate($data, $this->businessRepository->getRaw());
        $this->businessRepository->saveProfile(new BusinessProfile($validated['data']));

        $current = $this->onboardingRepository->getState()->toArray();
        $steps = $current['steps'] ?? [];
        $steps['business-data'] = true;

        $this->onboardingRepository->saveState(new OnboardingState([
            'started' => true,
            'completed' => false,
            'current_step' => 'dependencies',
            'steps' => $steps,
        ]));
    }
}
