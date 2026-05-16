<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SetupWizard\Steps;

use FlashSite\Core\Core\Contracts\SetupStepInterface;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingState;

final class SummaryStep implements SetupStepInterface
{
    public function __construct(private OnboardingRepository $onboardingRepository) {}

    public function getKey(): string { return 'summary'; }
    public function getLabel(): string { return 'Resumo'; }
    public function getOrder(): int { return 4; }
    public function isCompleted(): bool { return $this->onboardingRepository->getState()->isCompleted(); }
    public function validate(array $data): array { return []; }

    public function save(array $data): void
    {
        $current = $this->onboardingRepository->getState()->toArray();
        $steps = $current['steps'] ?? [];
        $steps['summary'] = true;

        $this->onboardingRepository->saveState(new OnboardingState([
            'started' => true,
            'completed' => true,
            'current_step' => 'summary',
            'steps' => $steps,
        ]));
    }
}
