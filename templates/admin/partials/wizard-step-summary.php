<?php
declare(strict_types=1);

$isCompleted = $state->isCompleted();
?>
<div class="fsc-card">
    <h2>Resumo</h2>

    <?php if ($isCompleted) : ?>
        <div class="notice notice-success inline"><p><strong>Setup concluído.</strong> O onboarding base já foi gravado e o plugin está pronto para uso operacional.</p></div>
    <?php else : ?>
        <p>Revise os dados abaixo e conclua o setup para fechar o onboarding inicial.</p>
    <?php endif; ?>

    <ul class="fsc-summary-list">
        <li><strong>Negócio:</strong> <?php echo esc_html($businessProfile->getBusinessName() ?: 'Não definido'); ?></li>
        <li><strong>Etapa atual:</strong> <?php echo esc_html($state->getCurrentStep()); ?></li>
        <li><strong>Status do onboarding:</strong> <?php echo $isCompleted ? 'Concluído' : 'Pendente'; ?></li>
    </ul>

    <p class="fsc-summary-actions">
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flashsite-core')); ?>">Ir para o dashboard</a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flashsite-business-data')); ?>">Rever dados do negócio</a>
    </p>

    <?php if (! $isCompleted) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('flashsite_save_wizard_step'); ?>
            <input type="hidden" name="action" value="flashsite_save_wizard_step" />
            <input type="hidden" name="step" value="summary" />
            <p><button type="submit" class="button button-primary">Concluir setup</button></p>
        </form>
    <?php else : ?>
        <p><span class="button button-primary disabled" aria-disabled="true">Setup concluído</span></p>
    <?php endif; ?>
</div>
