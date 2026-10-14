<?php
declare(strict_types=1);

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Access\RoleManager;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessUpdateService;
use FlashSite\Core\Domain\Business\BusinessValidator;
use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Abilities\AbilitiesModule;
use FlashSite\Core\Modules\Abilities\AbilityGate;
use FlashSite\Core\Modules\Abilities\BusinessAbilities;
use FlashSite\Core\Modules\Abilities\CollectionAbilities;
use FlashSite\Core\Modules\Abilities\ItemAbilities;
use FlashSite\Core\Modules\Abilities\SystemAbilities;
use FlashSite\Core\Modules\Collections\Admin\ItemEditor;
use FlashSite\Core\Modules\Collections\Admin\ListColumns;
use FlashSite\Core\Modules\Collections\CollectionItemService;
use FlashSite\Core\Modules\Collections\CollectionsModule;
use FlashSite\Core\Modules\Collections\CollectionStateService;
use FlashSite\Core\Modules\Collections\ItemPersistence;

/** Monta o grafo de serviços das Abilities como o Application, sobre o WordPress simulado. */
final class AbilitiesFixture
{
    public CollectionRegistry $registry;
    public ActivationRepository $activation;
    public CollectionSettings $settings;
    public CollectionStateService $state;
    public CollectionsModule $collections;
    public BusinessRepository $business;
    public AbilitiesModule $module;
    /** @var array<string, array<string, mixed>> */
    public array $abilities = [];

    /** @param list<string> $externalDirs */
    public function __construct(string $market = 'PT', array $externalDirs = [])
    {
        flashsite_reset_test_state();
        flashsite_reset_post_stubs();
        flashsite_enable_abilities_api_stub();
        (new RoleManager(dirname(__DIR__, 2) . '/config/capabilities.php'))->ensureRole();

        $storage = new OptionsStorage();
        $this->settings = new CollectionSettings($storage);
        $this->settings->setMarket($market);
        $core = dirname(__DIR__, 2) . '/config/collections';
        $external = $externalDirs;
        $this->registry = CollectionRegistry::fromDirectories($core, $external, $this->settings->market());
        $this->activation = new ActivationRepository($storage);
        $this->state = new CollectionStateService($this->registry, $this->activation);

        $sanitizer = new ItemSanitizer($this->settings);
        $reader = new ItemReader();
        $persistence = new ItemPersistence($sanitizer, new ItemValidator(), $reader, $this->settings);
        $this->collections = new CollectionsModule(
            $this->registry, $this->activation, new CollectionCapabilities($storage), $sanitizer, $storage, new Logger(),
            $persistence, new ItemEditor($persistence, $reader, $this->settings), new ListColumns($reader)
        );
        $this->collections->register();

        $this->business = new BusinessRepository($storage);
        $logger = new Logger();
        $gate = new AbilityGate($this->registry);
        $items = new CollectionItemService($persistence, $sanitizer, new ItemValidator(), $reader);
        $this->module = new AbilitiesModule([
            new SystemAbilities($this->registry, $this->activation, $this->settings, $gate),
            new CollectionAbilities($this->registry, $this->activation, $this->settings, $this->state, $gate),
            new ItemAbilities($this->registry, $this->activation, $items, $gate),
            new BusinessAbilities(new BusinessData($this->business), new BusinessUpdateService($this->business, new BusinessValidator(), $logger), $gate),
        ], $logger);
        $this->module->registerAbilities();
        $this->abilities = $GLOBALS['flashsite_test_registered_abilities'];
    }

    /** Ativa a coleção como o painel e regista o CPT neste pedido. */
    public function activate(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->state->activate($key);
        }
        $this->collections->registerContent();
        $this->collections->syncCapabilities();
        // Como o pedido seguinte ao painel: semeia os termos fixos e as faixas.
        $this->collections->maybeFlushRewriteRules();
    }

    /** @param list<string> $caps */
    public function asUser(array $caps): void
    {
        $GLOBALS['flashsite_test_current_user_caps'] = array_fill_keys($caps, true);
    }

    /** @return list<string> */
    public static function managerCaps(string $plural): array
    {
        return ["edit_$plural", "edit_others_$plural", "edit_published_$plural", "publish_$plural", "delete_$plural", "delete_published_$plural", 'edit_post', 'delete_post'];
    }

    /** Executa permission_callback + execute_callback, como o WordPress faz. */
    public function call(string $name, ?array $input = null): mixed
    {
        $ability = $this->abilities[$name] ?? null;
        if ($ability === null) {
            throw new RuntimeException("Ability não registada: $name");
        }
        if (! ($ability['permission_callback'])($input)) {
            return new WP_Error('ability_invalid_permissions', 'Sem permissão.', ['status' => 403]);
        }
        return ($ability['execute_callback'])($input);
    }

    public function allowed(string $name, ?array $input = null): bool
    {
        return (bool) ($this->abilities[$name]['permission_callback'])($input);
    }
}
