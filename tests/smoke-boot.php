<?php
declare(strict_types=1);

/**
 * Teste de carga (@since 3.2.0): carrega o flashsite-core.php e inicializa o plugin num WordPress simulado.
 * Pega erros fatais que os testes unitários não veem (ex.: módulo que não cumpre o contrato).
 * Uso: php tests/smoke-boot.php   (rodar antes de gerar qualquer ZIP)
 */
require __DIR__ . '/bootstrap.php';

$stubs = ['is_admin' => false, 'wp_doing_ajax' => false, 'is_multisite' => false, 'get_bloginfo' => '6.8', 'did_action' => 0,
    'wp_next_scheduled' => false, 'get_locale' => 'pt_PT', 'register_taxonomy' => null, 'remove_action' => true, 'has_action' => false,
    'wp_get_current_user' => null, 'is_user_logged_in' => false, 'get_current_user_id' => 0, 'wp_cache_get' => false, 'wp_cache_set' => true,
    'register_setting' => true, 'add_menu_page' => true, 'add_rewrite_rule' => true, 'add_rewrite_tag' => true, 'get_post_types' => [],
    'remove_filter' => true, 'register_activation_hook' => true, 'register_deactivation_hook' => true];
foreach ($stubs as $name => $value) {
    if (! function_exists($name)) {
        eval('function ' . $name . '(...$a) { return ' . var_export($value, true) . '; }');
    }
}
if (! function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) { return $value; }
}
if (! defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}
try {
    @require dirname(__DIR__) . '/flashsite-core.php';
    FlashSite\Core\Core\Plugin::instance()->boot();
    echo "BOOT OK\n";
    exit(0);
} catch (Throwable $e) {
    echo 'ERRO: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
