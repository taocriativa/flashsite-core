<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

use FlashSite\Core\Modules\PrivacyPolicy\PrivacyPolicyModule;

$content     = PrivacyPolicyModule::getContent();
$updatedDate = PrivacyPolicyModule::getUpdatedDate();
$updated     = isset($_GET['updated']) && $_GET['updated'] === '1';
?>
<div class="wrap fsc-wrap">
    <?php include __DIR__ . '/partials/admin-header.php'; ?>

    <div class="fsc-page-content">

        <?php if ($updated) : ?>
            <div class="notice notice-success is-dismissible">
                <p>✅ Política de Privacidade guardada com sucesso. Data de atualização registada automaticamente.</p>
            </div>
        <?php endif; ?>

        <div class="fsc-card">
            <h2>📄 Política de Privacidade</h2>
            <p class="description">
                Escreva ou cole aqui o texto da Política de Privacidade do site.
                A data de atualização é registada automaticamente a cada save.<br>
                Use <code>[flashsite_privacy_policy]</code> na página de Política de Privacidade para exibir o conteúdo.<br>
                Use <code>[flashsite_privacy_date]</code> para exibir a data da última atualização.
            </p>

            <?php if ($updatedDate !== '') : ?>
                <p class="description"><strong>Última atualização:</strong> <?php echo esc_html($updatedDate); ?></p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(PrivacyPolicyModule::getNonceAction()); ?>
                <input type="hidden" name="action" value="flashsite_save_privacy_policy">

                <div style="margin-top:16px;">
                    <?php
                    wp_editor($content, 'flashsite_privacy_content', [
                        'textarea_name' => 'flashsite_privacy_content',
                        'media_buttons' => false,
                        'textarea_rows' => 25,
                        'teeny'         => false,
                        'quicktags'     => true,
                        'tinymce'       => [
                            'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,undo,redo,|,formatselect',
                            'toolbar2' => '',
                        ],
                    ]);
                    ?>
                </div>

                <div style="margin-top:16px;">
                    <?php submit_button('Guardar Política de Privacidade', 'primary fsc-btn', 'submit', false); ?>
                </div>
            </form>
        </div>

        <div class="fsc-card" style="margin-top:16px;">
            <h3>Shortcodes disponíveis</h3>
            <table class="widefat striped" style="max-width:600px;">
                <thead>
                    <tr><th>Shortcode</th><th>Descrição</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[flashsite_privacy_policy]</code></td>
                        <td>Conteúdo completo da Política de Privacidade</td>
                    </tr>
                    <tr>
                        <td><code>[flashsite_privacy_date]</code></td>
                        <td>Data da última atualização (formato padrão: d/m/Y)</td>
                    </tr>
                    <tr>
                        <td><code>[flashsite_privacy_date format="Y-m-d"]</code></td>
                        <td>Data com formato personalizado (usa sintaxe PHP date())</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>
