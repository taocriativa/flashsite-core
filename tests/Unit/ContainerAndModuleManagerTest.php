<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Container;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Core\ModuleManager;

final class ContainerAndModuleManagerTest extends TestCase
{
    public function run(): void
    {
        $container = new Container();
        $container->bind('demo', static fn () => (object) ['value' => 42]);

        $first = $container->make('demo');
        $second = $container->make('demo');
        $this->assertTrue($first === $second, 'Container should memoize instances.');
        $this->assertSame(42, $first->value);

        $manager = new ModuleManager();
        $container->bind(DemoModule::class, static fn () => new DemoModule());
        $manager->load([DemoModule::class], $container);
        $manager->registerAll();
        $manager->bootAll();

        $this->assertSame(['register', 'boot'], DemoModule::$events);
    }
}

final class DemoModule implements ModuleInterface
{
    public static array $events = [];

    public function register(): void
    {
        self::$events[] = 'register';
    }

    public function boot(): void
    {
        self::$events[] = 'boot';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'demo';
    }
}
