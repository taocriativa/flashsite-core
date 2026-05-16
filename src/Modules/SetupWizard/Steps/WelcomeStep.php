<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SetupWizard\Steps;

use FlashSite\Core\Core\Contracts\SetupStepInterface;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingState;

final class WelcomeStep implements SetupStepInterface
{
    public function __construct(private OnboardingRepository $onboardingRepository) {}
    public function getKey(): string { return 'welcome'; }
    public function getLabel(): string { return 'Boas-vindas'; }
    public function getOrder(): int { return 1; }
    public function isCompleted(): bool { return $this->onboardingRepository->getState()->getCurrentStep() !== 'welcome'; }
    public function validate(array $data): array { return []; }

    public function save(array $data): void
    {
        $current = $this->onboardingRepository->getState()->toArray();
        $steps = $current['steps'] ?? [];
        $steps['welcome'] = true;

        $this->onboardingRepository->saveState(new OnboardingState([
            'started' => true,
            'completed' => false,
            'current_step' => 'business-data',
            'steps' => $steps,
        ]));
    }
}
