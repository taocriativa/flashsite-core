<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Onboarding;

use FlashSite\Core\Core\Contracts\RepositoryInterface;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

final class OnboardingRepository implements RepositoryInterface
{
    private const OPTION_KEY = 'flashsite_onboarding_state';

    public function __construct(private OptionsStorage $storage) {}

    public function getState(): OnboardingState
    {
        $raw = $this->storage->get(self::OPTION_KEY, [
            'started' => false,
            'completed' => false,
            'current_step' => 'welcome',
            'steps' => [],
        ]);
        return new OnboardingState(is_array($raw) ? $raw : []);
    }

    public function saveState(OnboardingState $state): bool
    {
        return $this->storage->update(self::OPTION_KEY, $state->toArray(), true);
    }

    public function reset(): bool
    {
        return $this->storage->update(self::OPTION_KEY, [
            'started' => false,
            'completed' => false,
            'current_step' => 'welcome',
            'steps' => [],
        ], true);
    }
}
