<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

use FlashSite\Core\Modules\PrivacyPolicy\PrivacyPolicyModule;

$content     = PrivacyPolicyModule::getContent();
$updatedDate = PrivacyPolicyModule::getUpdatedDate();
$updated     = isset($_GET['updated']) && $_GET['updated'] === '1';

$headerTitle    = 'Política de Privacidade';
$headerSubtitle = 'Edite o conteúdo da Política de Privacidade. A data de atualização é registada automaticamente a cada gravação.';
$headerActions  = [
    [
        'label'   => 'Voltar ao dashboard',
        'url'     => admin_url('admin.php?page=flashsite-core'),
        'variant' => 'secondary',
    ],
];
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <?php if ($updated) : ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ Política de Privacidade guardada com sucesso. Data de atualização registada automaticamente.</p>
        </div>
    <?php endif; ?>

    <div class="fsc-card-grid" style="grid-template-columns:1fr;">

        <div class="fsc-card">
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

                <div class="fsc-card-actions">
                    <?php submit_button('Guardar Política de Privacidade', 'primary fsc-btn', 'submit', false); ?>
                </div>
            </form>
        </div>

        <div class="fsc-card">
            <h3>Shortcodes disponíveis</h3>
            <div class="fsc-shortcode-list">
                <div class="fsc-shortcode-item">
                    <code>[flashsite_privacy_policy]</code>
                    <span class="description">Conteúdo completo da Política de Privacidade</span>
                    <button type="button" class="button button-secondary fsc-btn fsc-copy-btn" data-copy-text="[flashsite_privacy_policy]">Copiar</button>
                </div>
                <div class="fsc-shortcode-item">
                    <code>[flashsite_privacy_date]</code>
                    <span class="description">Data da última atualização (formato padrão: d/m/Y)</span>
                    <button type="button" class="button button-secondary fsc-btn fsc-copy-btn" data-copy-text="[flashsite_privacy_date]">Copiar</button>
                </div>
                <div class="fsc-shortcode-item">
                    <code>[flashsite_privacy_date format="Y-m-d"]</code>
                    <span class="description">Data com formato personalizado (usa sintaxe PHP date())</span>
                    <button type="button" class="button button-secondary fsc-btn fsc-copy-btn" data-copy-text='[flashsite_privacy_date format="Y-m-d"]'>Copiar</button>
                </div>
            </div>
        </div>

    </div>
</div>
