<?php
declare(strict_types=1);

$headerTitle = 'Segurança de Dados';
$headerSubtitle = 'Proteções do ciclo de vida do FlashSite Core. Use esta página para gerir backup, restauro e política de remoção.';
$headerActions = [
    [
        'label' => 'Voltar ao dashboard',
        'url' => admin_url('admin.php?page=flashsite-core'),
        'variant' => 'secondary',
    ],
    [
        'label' => 'Dados do negócio',
        'url' => admin_url('admin.php?page=flashsite-business-data'),
        'variant' => 'secondary',
    ],
];
$statusMap = [
    'delete-flag' => 'Política de remoção atualizada.',
    'backup-saved' => 'Backup manual criado com sucesso.',
    'backup-skipped' => 'Nenhum dado disponível para criar backup.',
    'backup-restored' => 'Último backup restaurado com sucesso.',
    'backup-missing' => 'Nenhum backup disponível para restauro.',
    'installation-protected' => 'Operação bloqueada: existe conflito de instalação ou slug legado. Resolva a coexistência antes de alterar políticas destrutivas.',
];
$updated = isset($_GET['updated']) ? sanitize_key((string) $_GET['updated']) : '';
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <?php if ($updated !== '' && isset($statusMap[$updated])) : ?>
        <div class="notice <?php echo $updated === 'installation-protected' ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html($statusMap[$updated]); ?></p></div>
    <?php endif; ?>

    <?php if (($installationStatus['has_conflict'] ?? false) === true) : ?>
        <div class="fsc-notice-inline"><p><strong>Modo de proteção activo.</strong> <?php echo esc_html((string) ($installationStatus['path_status'] ?? 'canonical') !== 'canonical' ? 'Esta instalação não está na pasta canónica flashsite-core.' : 'Foi detectada outra instalação candidata do FlashSite Core.'); ?></p>
        <?php if (($installationStatus['duplicate_candidates'] ?? []) !== []) : ?>
            <ul class="fsc-conflict-list">
                <?php foreach (($installationStatus['duplicate_candidates'] ?? []) as $candidate) : ?>
                    <li><code><?php echo esc_html((string) ($candidate['file'] ?? 'desconhecida')); ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="fsc-card-grid">
        <div class="fsc-card fsc-card--highlight">
            <h2>Política de remoção</h2>
            <p><strong>Apagar dados ao desinstalar:</strong> <span class="<?php echo $allowDeletion ? 'fsc-pill fsc-pill--danger' : 'fsc-pill'; ?>"><?php echo $allowDeletion ? 'Ativo' : 'Desativado'; ?></span></p>
            <p><strong>Modo de proteção:</strong> <span class="<?php echo (($installationStatus['safe_mode'] ?? false) === true) ? 'fsc-pill fsc-pill--warning' : 'fsc-pill fsc-pill--success'; ?>"><?php echo (($installationStatus['safe_mode'] ?? false) === true) ? 'Ativo' : 'Desativado'; ?></span></p>
            <p class="description fsc-policy-description">Por padrão, o plugin não remove dados nem backups ao ser excluído. Só ative esta opção para purga deliberada.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('flashsite_toggle_delete_flag'); ?>
                <input type="hidden" name="action" value="flashsite_toggle_delete_flag">
                <label class="fsc-policy-checkbox">
                    <input type="checkbox" name="allow_data_deletion" value="1" <?php checked($allowDeletion); ?> <?php disabled(($installationStatus['safe_mode'] ?? false) === true); ?>>
                    Permitir que o uninstall apague os dados do FlashSite Core.
                </label>
                <p><button type="submit" class="button button-primary fsc-btn" <?php disabled(($installationStatus['safe_mode'] ?? false) === true); ?>>Guardar política</button></p>
            </form>
        </div>

        <div class="fsc-card">
            <h2>Backup operacional</h2>
            <p><strong>Backup disponível:</strong> <?php echo $hasBackup ? 'Sim' : 'Não'; ?></p>
            <p><strong>Último backup:</strong> <?php echo esc_html((string) ($backupMeta['created_at'] ?? '—')); ?></p>
            <p><strong>Motivo:</strong> <?php echo esc_html((string) ($backupMeta['reason'] ?? '—')); ?></p>
            <p><strong>Schema:</strong> <?php echo esc_html((string) ($backupMeta['schema'] ?? '—')); ?></p>
            <?php if (($installationStatus['duplicate_candidates'] ?? []) !== []) : ?>
                <p><strong>Instalação concorrente detectada:</strong> <?php echo esc_html((string) ($installationStatus['duplicate_candidates'][0]['file'] ?? 'desconhecida')); ?></p>
            <?php endif; ?>
            <div class="fsc-action-stack">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('flashsite_backup_now'); ?>
                    <input type="hidden" name="action" value="flashsite_backup_now">
                    <button type="submit" class="button fsc-btn">Criar backup agora</button>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('flashsite_restore_backup'); ?>
                    <input type="hidden" name="action" value="flashsite_restore_backup">
                    <button type="submit" class="button button-secondary fsc-btn" <?php disabled(! $hasBackup); ?> onclick="return confirm('Restaurar o último backup vai sobrescrever os dados atuais. Continuar?');">Restaurar último backup</button>
                </form>
            </div>
        </div>

        <div class="fsc-card">
            <h2>Estado atual</h2>
            <div class="fsc-state-stack">
                <p><strong>Nome:</strong> <?php echo esc_html($profile->getBusinessName() !== '' ? $profile->getBusinessName() : 'Não definido'); ?></p>
                <p><strong>Telefone:</strong> <?php echo esc_html($profile->getPhone() !== '' ? $profile->getPhone() : 'Não definido'); ?></p>
                <p><strong>Email público:</strong> <?php echo esc_html($profile->getEmail() !== '' ? $profile->getEmail() : 'Não definido'); ?></p>
                <p class="description">Cada gravação do perfil cria um backup leve do estado anterior, permitindo restauro rápido em caso de perda acidental.</p>
            </div>
        </div>
    </div>
</div>
