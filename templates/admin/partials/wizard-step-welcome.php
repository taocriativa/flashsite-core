<?php
declare(strict_types=1);
?>
<div class="fsc-card">
    <h2>Boas-vindas</h2>
    <p>Este assistente vai ajudar a configurar os dados básicos do negócio e revisar dependências do sistema.</p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('flashsite_save_wizard_step'); ?>
        <input type="hidden" name="action" value="flashsite_save_wizard_step" />
        <input type="hidden" name="step" value="welcome" />
        <p><button type="submit" class="button button-primary">Iniciar setup</button></p>
    </form>
</div>
