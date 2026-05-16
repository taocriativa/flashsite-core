<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Onboarding;

final class OnboardingState
{
    public function __construct(private array $data) {}

    public function isStarted(): bool { return (bool) ($this->data['started'] ?? false); }
    public function isCompleted(): bool { return (bool) ($this->data['completed'] ?? false); }
    public function getCurrentStep(): string { return (string) ($this->data['current_step'] ?? 'welcome'); }

    public function getSteps(): array
    {
        $steps = $this->data['steps'] ?? [];
        return is_array($steps) ? array_map(static fn ($v) => (bool) $v, $steps) : [];
    }

    public function toArray(): array
    {
        return [
            'started' => $this->isStarted(),
            'completed' => $this->isCompleted(),
            'current_step' => $this->getCurrentStep(),
            'steps' => $this->getSteps(),
        ];
    }
}
