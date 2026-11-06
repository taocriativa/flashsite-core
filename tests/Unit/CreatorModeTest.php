<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../fixtures/creator_plugins/full-customer/Notice.php';
require_once __DIR__ . '/../fixtures/creator_plugins/outro-plugin/Notice.php';

use FlashSite\Core\Domain\Access\CreatorPolicy;
use FlashSite\Core\Modules\CreatorMode\CreatorModeModule;

if (! defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', dirname(__DIR__) . '/fixtures/creator_plugins');
}
if (! defined('FLASHSITE_CRIADOR')) {
    define('FLASHSITE_CRIADOR', 'Criador-FS, ola@flashsite.pt');
}
$GLOBALS['creator_test_users'] = [
    1 => (object) ['ID' => 1, 'user_login' => 'criador-fs', 'user_email' => 'ted@exemplo.pt'],
    2 => (object) ['ID' => 2, 'user_login' => 'dono', 'user_email' => 'dono@cliente.pt'],
    3 => (object) ['ID' => 3, 'user_login' => 'outro', 'user_email' => 'OLA@flashsite.pt'],
];
if (! function_exists('get_userdata')) {
    function get_userdata(int $id): object|false { return $GLOBALS['creator_test_users'][$id] ?? false; }
}
if (! function_exists('get_current_user_id')) {
    function get_current_user_id(): int { return (int) ($GLOBALS['flashsite_test_user_id'] ?? 1); }
}
if (! function_exists('get_user_by')) {
    function get_user_by(string $field, string $value): object|false
    {
        foreach ($GLOBALS['creator_test_users'] as $user) {
            $have = $field === 'email' ? $user->user_email : $user->user_login;
            if (strtolower($have) === strtolower($value)) {
                return $user;
            }
        }
        return false;
    }
}
if (! function_exists('remove_filter')) {
    function remove_filter(string $hook, mixed $callback, int $priority = 10): bool
    {
        global $wp_filter;
        foreach ($wp_filter[$hook]->callbacks[$priority] ?? [] as $key => $item) {
            if ($item['function'] === $callback) {
                unset($wp_filter[$hook]->callbacks[$priority][$key]);
                return true;
            }
        }
        return false;
    }
}

final class CreatorModeTest extends TestCase
{
    public function run(): void
    {
        // Lista da constante: separadores variados, minúsculas, sem repetidos.
        $this->assertSame(['ted', 'ola@flashsite.pt', 'outro'], CreatorPolicy::parseList(" Ted, ola@flashsite.pt ; outro,ted "));
        $this->assertSame([], CreatorPolicy::parseList('  '));

        // Criador por login ou por e-mail; sem lista ninguém é Criador.
        $list = ['ted', 'ola@flashsite.pt'];
        $this->assertTrue(CreatorPolicy::matchesUser('TED', 'x@y.pt', $list));
        $this->assertTrue(CreatorPolicy::matchesUser('outro', 'Ola@FlashSite.pt', $list));
        $this->assertFalse(CreatorPolicy::matchesUser('dono', 'dono@cliente.pt', $list));
        $this->assertFalse(CreatorPolicy::matchesUser('ted', '', []));

        // Pasta do plugin.
        $this->assertSame('full-customer', CreatorPolicy::slugFromBasename('full-customer/full-customer.php'));
        $this->assertSame('hello', CreatorPolicy::slugFromBasename('hello.php'));
        $this->assertSame('litespeed-cache', CreatorPolicy::slugFromFile('/www/wp-content/plugins/litespeed-cache/src/core.php', '/www/wp-content/plugins/'));
        $this->assertSame('', CreatorPolicy::slugFromFile('/www/wp-includes/plugin.php', '/www/wp-content/plugins'));
        $this->assertSame('', CreatorPolicy::slugFromFile('/www/wp-content/plugins-old/x/y.php', '/www/wp-content/plugins'));

        // Padrões.
        $this->assertTrue(CreatorPolicy::slugMatches('hostinger-auto-updates-plugin-bJc9', CreatorPolicy::HIDDEN_PLUGINS));
        $this->assertTrue(CreatorPolicy::slugMatches('claudiasousa-kit', CreatorPolicy::PROTECTED_PLUGINS));
        $this->assertTrue(CreatorPolicy::slugMatches('flashsite-core', CreatorPolicy::PROTECTED_PLUGINS));
        $this->assertFalse(CreatorPolicy::slugMatches('elementor-pro-addons', CreatorPolicy::PROTECTED_PLUGINS));
        $this->assertFalse(CreatorPolicy::slugMatches('woocommerce', array_merge(CreatorPolicy::HIDDEN_PLUGINS, CreatorPolicy::PROTECTED_PLUGINS)));

        // Lista de plugins: saem ocultos e protegidos, fica o resto.
        $plugins = [
            'full-customer/full-customer.php' => [], 'litespeed-cache/litespeed-cache.php' => [], 'flashsite-core/flashsite-core.php' => [],
            'claudiasousa-kit/claudiasousa-kit.php' => [], 'elementor/elementor.php' => [], 'woocommerce/woocommerce.php' => [],
            'cookie-law-info/cookie-law-info.php' => [], 'hostinger-preview-domain-PnqhC/hostinger-preview-domain.php' => [],
        ];
        $left = CreatorPolicy::filterPluginList($plugins, CreatorPolicy::HIDDEN_PLUGINS, CreatorPolicy::PROTECTED_PLUGINS);
        $this->assertSame(['woocommerce/woocommerce.php', 'cookie-law-info/cookie-law-info.php'], array_keys($left));

        // Permissões.
        $deny = fn (string $cap, array $args = []) => CreatorPolicy::shouldDeny($cap, $args, [1], CreatorPolicy::BLOCKED_CAPS, CreatorPolicy::HIDDEN_PLUGINS, CreatorPolicy::PROTECTED_PLUGINS);
        $this->assertTrue($deny('install_plugins'));
        $this->assertTrue($deny('update_core'));
        $this->assertTrue($deny('switch_themes'));
        $this->assertFalse($deny('edit_posts'));
        $this->assertFalse($deny('manage_options'));
        $this->assertTrue($deny('edit_user', [1]));
        $this->assertTrue($deny('delete_user', ['1']));
        $this->assertFalse($deny('edit_user', [2]));
        $this->assertTrue($deny('deactivate_plugin', ['flashsite-core/flashsite-core.php']));
        $this->assertTrue($deny('deactivate_plugin', ['full-customer/full-customer.php']));
        $this->assertFalse($deny('deactivate_plugin', ['woocommerce/woocommerce.php']));
        $this->assertFalse($deny('activate_plugin', []));

        $previousUser = $GLOBALS['flashsite_test_user_id'] ?? null;
        $GLOBALS['flashsite_test_user_id'] = 2;

        // Módulo: dono (id 2) é limitado; Criador por login (1) e por e-mail (3) não.
        $module = new CreatorModeModule();
        $this->assertTrue($module->isCreator(1));
        $this->assertTrue($module->isCreator(3));
        $this->assertFalse($module->isCreator(2));
        $this->assertFalse($module->isCreator(0));
        $this->assertSame(['do_not_allow'], $module->mapMetaCap(['install_plugins'], 'install_plugins', 2, []));
        $this->assertSame(['install_plugins'], $module->mapMetaCap(['install_plugins'], 'install_plugins', 1, []));
        $this->assertSame(['do_not_allow'], $module->mapMetaCap(['edit_users'], 'edit_user', 2, [3]));
        $this->assertSame(['edit_users'], $module->mapMetaCap(['edit_users'], 'edit_user', 2, [2]));
        $this->assertSame(['install_plugins'], $module->mapMetaCap(['install_plugins'], 'install_plugins', 0, []), 'Cron e visitantes não são afetados.');

        $this->assertSame(['woocommerce/woocommerce.php', 'cookie-law-info/cookie-law-info.php'], array_keys($module->filterPlugins($plugins)));
        $updates = (object) ['response' => ['full-customer/full-customer.php' => 1, 'woocommerce/woocommerce.php' => 2]];
        $this->assertSame(['woocommerce/woocommerce.php'], array_keys($module->filterUpdates($updates)->response));
        $this->assertCount(2, $updates->response, 'O transient original não é alterado.');

        // Avisos: sai o que vem do FULL, fica o do outro plugin e o do próprio Core.
        global $wp_filter;
        $full = new CreatorFixtureFullNotice();
        $other = new CreatorFixtureOtherNotice();
        $core = static function (): void {};
        $wp_filter['admin_notices'] = (object) ['callbacks' => [10 => [
            'a' => ['function' => [$full, 'notices']], 'b' => ['function' => [$other, 'notices']], 'c' => ['function' => $core],
        ], 20 => ['d' => ['function' => 'creator_fixture_full_widget'], 'e' => ['function' => 'CreatorFixtureFullNotice::bar']]]];
        $module->stripNotices();
        $this->assertSame(['b', 'c'], array_keys($wp_filter['admin_notices']->callbacks[10]));
        $this->assertSame([], $wp_filter['admin_notices']->callbacks[20]);

        // Avisos de dependências do Core: o dono não vê; mcp-adapter é oculto.
        $this->assertFalse($module->dependencyNotices(true));
        $this->assertTrue(CreatorPolicy::slugMatches('mcp-adapter', CreatorPolicy::HIDDEN_PLUGINS));

        // O Criador vê tudo.
        $GLOBALS['flashsite_test_user_id'] = 1;
        $this->assertTrue($module->dependencyNotices(true));
        $this->assertFalse($module->dependencyNotices(false));
        $this->assertCount(8, $module->filterPlugins($plugins));
        $wp_filter['admin_notices'] = (object) ['callbacks' => [10 => ['a' => ['function' => [$full, 'notices']]]]];
        $module->stripNotices();
        $this->assertCount(1, $wp_filter['admin_notices']->callbacks[10]);
        $GLOBALS['flashsite_test_user_id'] = $previousUser;
        unset($wp_filter['admin_notices']);
    }
}
