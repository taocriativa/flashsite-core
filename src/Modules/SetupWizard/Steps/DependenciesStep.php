<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SetupWizard\Steps;

use FlashSite\Core\Core\Contracts\SetupStepInterface;
use FlashSite\Core\Core\PluginChecker;
use FlashSite\Core\Domain\Dependencies\DependencyRegistry;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Domain\Onboarding\OnboardingState;

final class DependenciesStep implements SetupStepInterface
{
    public function __construct(
        private DependencyRegistry $dependencyRegistry,
        private PluginChecker $pluginChecker,
        private OnboardingRepository $onboardingRepository
    ) {}

    public function getKey(): string { return 'dependencies'; }
    public function getLabel(): string { return 'Dependências'; }
    public function getOrder(): int { return 3; }
    public function isCompleted(): bool { return (bool) ($this->onboardingRepository->getState()->getSteps()['dependencies'] ?? false); }
    public function validate(array $data): array { return []; }

    public function save(array $data): void
    {
        $current = $this->onboardingRepository->getState()->toArray();
        $steps = $current['steps'] ?? [];
        $steps['dependencies'] = true;

        $this->onboardingRepository->saveState(new OnboardingState([
            'started' => true,
            'completed' => false,
            'current_step' => 'summary',
            'steps' => $steps,
        ]));
    }
}
