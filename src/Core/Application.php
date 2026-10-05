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
use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionPublicSerializer;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\SchemaOrgBuilder;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Domain\Dependencies\DependencyRegistry;
use FlashSite\Core\Domain\Safety\DataSafetyManager;
use FlashSite\Core\Domain\Onboarding\OnboardingRepository;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\AccessControl\AccessControlModule;
use FlashSite\Core\Modules\AdminUX\AdminUXModule;
use FlashSite\Core\Modules\Api\ApiAccessModule;
use FlashSite\Core\Modules\BusinessData\BusinessDataModule;
use FlashSite\Core\Modules\Collections\Admin\CollectionsPage;
use FlashSite\Core\Modules\Collections\Admin\ItemEditor;
use FlashSite\Core\Modules\Collections\Admin\ListColumns;
use FlashSite\Core\Modules\Collections\CollectionsModule;
use FlashSite\Core\Modules\Collections\DemoKitImporter;
use FlashSite\Core\Modules\Collections\ItemPersistence;
use FlashSite\Core\Modules\Collections\Output\CollectionsOutput;
use FlashSite\Core\Modules\Collections\Output\PublicRestController;
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
        // 2.6.0 — Coleções: nenhum preset ativo por defeito; caps sincronizadas.
        $schemaManager->register('2.6.0', function (): void {
            $storage = $this->container->make(OptionsStorage::class);
            if (! $storage->exists(ActivationRepository::OPTION_KEY)) {
                $storage->update(ActivationRepository::OPTION_KEY, [], true);
            }
            $this->container->make(RoleManager::class)->ensureRole();
            $this->container->make(CollectionCapabilities::class)->sync(
                $this->container->make(CollectionRegistry::class),
                $this->container->make(ActivationRepository::class)->activeKeys(),
                true
            );
            update_option('flashsite_data_version', FLASHSITE_DATA_VERSION, true);
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
        $this->container->bind(CollectionRegistry::class, fn () => CollectionRegistry::fromDirectory(FLASHSITE_CORE_PATH . 'config/collections'));
        $this->container->bind(ActivationRepository::class, fn (Container $c) => new ActivationRepository($c->make(OptionsStorage::class)));
        $this->container->bind(CollectionCapabilities::class, fn (Container $c) => new CollectionCapabilities($c->make(OptionsStorage::class)));
        $this->container->bind(CollectionSettings::class, fn (Container $c) => new CollectionSettings($c->make(OptionsStorage::class)));
        $this->container->bind(ItemSanitizer::class, fn (Container $c) => new ItemSanitizer($c->make(CollectionSettings::class)));
        $this->container->bind(ItemValidator::class, fn () => new ItemValidator());
        $this->container->bind(ItemReader::class, fn () => new ItemReader());
        $this->container->bind(FieldFormatter::class, fn (Container $c) => new FieldFormatter($c->make(ItemReader::class), $c->make(CollectionSettings::class)));
        $this->container->bind(CollectionPublicSerializer::class, fn (Container $c) => new CollectionPublicSerializer($c->make(ItemReader::class), $c->make(FieldFormatter::class)));
        $this->container->bind(SchemaOrgBuilder::class, fn (Container $c) => new SchemaOrgBuilder($c->make(CollectionPublicSerializer::class), $c->make(CollectionSettings::class)));
    }

    private function registerModuleServices(): void
    {
        $this->container->bind(BusinessDataModule::class, fn (Container $c) => new BusinessDataModule($c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(Logger::class)));
        $this->container->bind(AccessControlModule::class, fn (Container $c) => new AccessControlModule($c->make(RoleManager::class), $c->make(Logger::class)));
        $this->container->bind(AdminUXModule::class, fn (Container $c) => new AdminUXModule($c->make(BusinessRepository::class), $c->make(OnboardingRepository::class), $c->make(Assets::class), $c->make(DataSafetyManager::class)));
        $this->container->bind(ApiAccessModule::class, fn (Container $c) => new ApiAccessModule($c->make(BusinessData::class), $c->make(PublicSerializer::class), $c->make(OutputResolver::class), $c->make(MetaProvider::class), $c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(Logger::class)));
        $this->container->bind(DependencyManagerModule::class, fn (Container $c) => new DependencyManagerModule($c->make(DependencyRegistry::class), $c->make(PluginChecker::class), $c->make(Notices::class), $c->make(Logger::class)));
        $this->container->bind(SetupWizardModule::class, fn (Container $c) => new SetupWizardModule($c->make(BusinessRepository::class), $c->make(BusinessValidator::class), $c->make(OnboardingRepository::class), $c->make(DependencyRegistry::class), $c->make(PluginChecker::class), $c->make(Assets::class), $c->make(Logger::class)));
        $this->container->bind(ItemPersistence::class, fn (Container $c) => new ItemPersistence($c->make(ItemSanitizer::class), $c->make(ItemValidator::class), $c->make(ItemReader::class), $c->make(CollectionSettings::class)));
        $this->container->bind(ItemEditor::class, fn (Container $c) => new ItemEditor($c->make(ItemPersistence::class), $c->make(ItemReader::class), $c->make(CollectionSettings::class)));
        $this->container->bind(ListColumns::class, fn (Container $c) => new ListColumns($c->make(ItemReader::class), $c->make(FieldFormatter::class)));
        $this->container->bind(CollectionsOutput::class, fn (Container $c) => new CollectionsOutput($c->make(CollectionRegistry::class), $c->make(FieldFormatter::class), $c->make(SchemaOrgBuilder::class)));
        $this->container->bind(PublicRestController::class, fn (Container $c) => new PublicRestController($c->make(CollectionPublicSerializer::class)));
        $this->container->bind(DemoKitImporter::class, fn (Container $c) => new DemoKitImporter($c->make(ItemPersistence::class), FLASHSITE_CORE_PATH . 'config/collections/demo-kit'));
        $this->container->bind(CollectionsPage::class, fn (Container $c) => new CollectionsPage($c->make(CollectionRegistry::class), $c->make(ActivationRepository::class), $c->make(DemoKitImporter::class), $c->make(CollectionSettings::class)));
        $this->container->bind(CollectionsModule::class, fn (Container $c) => new CollectionsModule($c->make(CollectionRegistry::class), $c->make(ActivationRepository::class), $c->make(CollectionCapabilities::class), $c->make(ItemSanitizer::class), $c->make(OptionsStorage::class), $c->make(Logger::class), $c->make(ItemPersistence::class), $c->make(ItemEditor::class), $c->make(ListColumns::class), $c->make(CollectionsOutput::class), $c->make(PublicRestController::class), $c->make(CollectionsPage::class)));
        $this->container->bind(OutputFoundationModule::class, fn (Container $c) => new OutputFoundationModule($c->make(BusinessData::class), $c->make(Logger::class)));
    }

    private function loadModuleConfig(): array
    {
        $file = FLASHSITE_CORE_PATH . 'config/modules.php';
        $modules = file_exists($file) ? require $file : [];
        return is_array($modules) ? $modules : [];
    }
}
