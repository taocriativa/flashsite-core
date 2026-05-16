<?php
declare(strict_types=1);
$dependencyRows = is_array($dependencyState) ? $dependencyState : [];
?>
<div class="fsc-card">
    <h2>Dependências</h2>
    <p>Revise o estado atual dos plugins relevantes para o stack.</p>
    <table class="widefat striped">
        <thead><tr><th>Plugin</th><th>Nível</th><th>Instalado</th><th>Ativo</th></tr></thead>
        <tbody>
        <?php foreach ($dependencyRows as $row) : ?>
            <tr>
                <td><?php echo esc_html((string) ($row['label'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($row['level'] ?? '')); ?></td>
                <td><?php echo ! empty($row['installed']) ? 'Sim' : 'Não'; ?></td>
                <td><?php echo ! empty($row['active']) ? 'Sim' : 'Não'; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('flashsite_save_wizard_step'); ?>
        <input type="hidden" name="action" value="flashsite_save_wizard_step" />
        <input type="hidden" name="step" value="dependencies" />
        <p><button type="submit" class="button button-primary">Continuar</button></p>
    </form>
</div>
