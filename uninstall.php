<?php
declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) { exit; }

/**
 * Política conservadora de uninstall.
 *
 * Por padrão, o FlashSite Core NÃO apaga dados nem roles ao excluir o plugin.
 * Isso evita perda acidental de `wp_options` em ambientes onde o plugin foi removido
 * apenas para atualização manual ou substituição de pacote.
 *
 * Para purga total e deliberada, definir antes do uninstall:
 * define('FLASHSITE_CORE_PURGE_ON_UNINSTALL', true);
 * ou guardar a opção `flashsite_allow_data_deletion = true`.
 */
$allowPurge = defined('FLASHSITE_CORE_PURGE_ON_UNINSTALL') && FLASHSITE_CORE_PURGE_ON_UNINSTALL === true;
$allowPurge = $allowPurge || get_option('flashsite_allow_data_deletion', false) === true;
$installationStatus = get_option('flashsite_installation_status', []);
$hasConflict = is_array($installationStatus)
    && (((string) ($installationStatus['path_status'] ?? 'canonical')) !== 'canonical' || ! empty($installationStatus['duplicate_candidates']));
if (! $allowPurge || $hasConflict) {
    return;
}

$options = [
    'flashsite_core_version',
    'flashsite_core_activated_at',
    'flashsite_data_version',
    'flashsite_onboarding_state',
    'flashsite_business_profile',
    'flashsite_dependency_state',
    'flashsite_installation_status',
    'flashsite_business_profile_backup',
    'flashsite_business_profile_backup_meta',
    'flashsite_allow_data_deletion',
    'flashsite_test_save_ran',
    'flashsite_test_invalid_ran',
];

foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option);
}

remove_role('flashsite_site_manager');
