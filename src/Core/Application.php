<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

use FlashSite\Core\Domain\Access\RoleManager;
use FlashSite\Core\Domain\Api\MetaProvider;
use FlashSite\Core\Domain\Api\OutputResolver;
use FlashSite\Core\Domain\Api\PublicSerializer;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessValidator;
use FlashSite\Core\Domain\Dependencies\DependencyRegistry;
use FlashSite\Core\Domain\Safety\DataSafetyManager;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\AccessControl\AccessControlModule;
use FlashSite\Core\Modules\AdminUX\AdminUXModule;
use FlashSite\Core\Modules\Api\ApiAccessModule;
use FlashSite\Core\Modules\BusinessData\BusinessDataModule;
use FlashSite\Core\Modules\DependencyManager\DependencyManagerModule;
use FlashSite\Core\Modules\OutputFoundation\OutputFoundationModule;
use FlashSite\Core\Modules\SetupWizard\SetupWizardModule;

final class Application
{
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function initialize(): void
    {
        $this->registerCoreServices();
        $this->registerDomainServices();
        $this->registerModuleServices();

        $this->container->make(Assets::class)->register();
        $this->container->make(Notices::class)->boot();

        $schemaManager = $this->container->make(SchemaManager::class);
        $schemaManager->register('0.4.0', function (): void {
            $this->container->make(BusinessRepository::class)->migrateIfNeeded();
            $this->container->make(RoleManager::class)->ensureRole();
        });
        $schemaManager->register('0.5.3', function (): void {
            $this->container->make(BusinessRepository::class)->migrateIfNeeded();
            $this->container->make(RoleManager::class)->ensureRole();
        });
        $schemaManager->register('1.5.0', function (): void {
            $this->container->make(BusinessRepository::class)->migrateIfNeeded();
            $this->container->make(RoleManager::class)->ensureRole();
        });
        $schemaManager->register('1.5.1', function (): void {
            $this->container->make(BusinessRepository::class)->migrateIfNeeded();
            $this->container->make(RoleManager::class)->ensureRole();
            InstallationGuard::status();
        });
        $schemaManager->runIfNeeded();

        $moduleManager = $this->container->make(ModuleManager::class);
        $moduleManager->load($this->loadModuleConfig(), $this->container);
        $moduleManager->registerAll();
        $moduleManager->bootAll();
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function registerCoreServices(): void
    {
        $this->container->bind(Container::class, fn () => $this->container);
        $this->container->bind(Logger::class, fn () => new Logger());
        $this->container->bind(Notices::class, fn () => new Notices());
        $this->container->bind(Assets::class, fn () => new Assets());
        $this->container->bind(PluginChecker::class, fn () => new PluginChecker());
        $this->container->bind(SchemaManager::class, fn () => new SchemaManager());
        $this->container->bind(ModuleManager::class, fn () => new ModuleManager());
    }

    private function registerDomainServices(): void
    {
        $this->container->bind(OptionsStorage::class, fn () => new OptionsStorage());
        $this->container->bind(BusinessValidator::class, fn () => new BusinessValidator());
        $this->container->bind(DataSafetyManager::class, fn (Container $c) => new DataSafetyManager($c->make(OptionsStorage::class)));
        $this->container->bind(BusinessRepository::class, fn (Container $c) => new BusinessRepository($c->make(OptionsStorage::class), $c->make(DataSafetyManager::class)));
        $this->container->bind(BusinessData::class, fn (Container $c) => new BusinessData($c->make(BusinessRepository::class)));
        $this->container->bind(PublicSerializer::class, fn () => new PublicSerializer());
        $this->container->bind(OutputResolver::class, fn () => new OutputResolver());
        $this->container->bind(MetaProvider::class, fn (Container $c) => new MetaProvider($c->make(PublicSerializer::class)));
        $this->container->bind(OnboardingRepository::class, fn (Container $c) => new OnboardingRepository($c->make(OptionsStorage::class)));
        $this->container->bind(DependencyRegistry::class, fn () => DependencyRegistry::fromConfig(FLASHSITE_CORE_PATH . 'config/dependencies.php'));
        $this->container->bind(RoleManager::class, fn () => new RoleManager(FLASHSITE_CORE_PATH . 'config/capabilities.php'));
    }

    private function registerModuleServices(): void
    {
        $this->container->bind(BusinessDataModule::class, fn (Container $c) => new BusinessDataModule($c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(Logger::class)));
        $this->container->bind(AccessControlModule::class, fn (Container $c) => new AccessControlModule($c->make(RoleManager::class), $c->make(Logger::class)));
        $this->container->bind(AdminUXModule::class, fn (Container $c) => new AdminUXModule($c->make(BusinessRepository::class), $c->make(OnboardingRepository::class), $c->make(Assets::class), $c->make(DataSafetyManager::class)));
        $this->container->bind(ApiAccessModule::class, fn (Container $c) => new ApiAccessModule($c->make(BusinessData::class), $c->make(PublicSerializer::class), $c->make(OutputResolver::class), $c->make(MetaProvider::class), $c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(Logger::class)));
        $this->container->bind(DependencyManagerModule::class, fn (Container $c) => new DependencyManagerModule($c->make(DependencyRegistry::class), $c->make(PluginChecker::class), $c->make(Notices::class), $c->make(Logger::class)));
        $this->container->bind(SetupWizardModule::class, fn (Container $c) => new SetupWizardModule($c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(OnboardingRepository::class), $c->make(DependencyRegistry::class), $c->make(PluginChecker::class), $c->make(Assets::class), $c->make(Logger::class)));
        $this->container->bind(OutputFoundationModule::class, fn (Container $c) => new OutputFoundationModule($c->make(BusinessData::class), $c->make(Logger::class)));
    }

    private function loadModuleConfig(): array
    {
        $file = FLASHSITE_CORE_PATH . 'config/modules.php';
        $modules = file_exists($file) ? require $file : [];
        return is_array($modules) ? $modules : [];
    }
}
