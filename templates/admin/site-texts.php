<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

/**
 * FlashSite › Textos do site (@since 3.1.0).
 *
 * @var array<string, array{url: string, sections: array<string, list<array<string, mixed>>>}> $groups
 * @var array<string, string> $values
 * @var bool $canRescan
 */

$saved   = isset($_GET['saved']) ? (int) $_GET['saved'] : null; // phpcs:ignore WordPress.Security.NonceVerification
$scanned = isset($_GET['scanned']) ? (int) $_GET['scanned'] : null; // phpcs:ignore WordPress.Security.NonceVerification

$headerTitle    = 'Textos do site';
$headerSubtitle = 'Mude os textos das páginas aqui. O layout não muda. Campo vazio volta ao texto original.';
$headerActions  = [
    ['label' => 'Ver o site', 'url' => home_url('/'), 'variant' => 'secondary'],
];
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <?php if ($saved !== null) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo $saved > 0 ? esc_html(sprintf('Textos atualizados (%d). Já estão no site.', $saved)) : 'Nada mudou.'; ?></p>
        </div>
    <?php endif; ?>
    <?php if ($scanned !== null) : ?>
        <div class="notice notice-info is-dismissible"><p><?php echo esc_html(sprintf('Lista atualizada a partir de %d páginas.', $scanned)); ?></p></div>
    <?php endif; ?>

    <?php if ($groups === []) : ?>
        <div class="fsc-card">
            <p>Ainda não há textos editáveis neste site.</p>
            <?php if ($canRescan) : ?>
                <p class="description">Para criar um: no Elementor, num título ou parágrafo, use a tag dinâmica <strong>Texto do site (editável pelo cliente)</strong> e preencha a chave, o nome do campo e o texto padrão.</p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fsc-site-texts">
            <?php wp_nonce_field('flashsite_site_texts_save'); ?>
            <input type="hidden" name="action" value="flashsite_site_texts_save">

            <?php foreach ($groups as $pageTitle => $group) : ?>
                <div class="fsc-card" style="margin-bottom:20px;">
                    <h2 style="margin-top:0;display:flex;justify-content:space-between;align-items:baseline;gap:12px;">
                        <span><?php echo esc_html($pageTitle); ?></span>
                        <?php if ($group['url'] !== '') : ?>
                            <a href="<?php echo esc_url($group['url']); ?>" target="_blank" rel="noopener" style="font-size:13px;font-weight:400;">Ver página ↗</a>
                        <?php endif; ?>
                    </h2>
                    <?php foreach ($group['sections'] as $sectionTitle => $fields) : ?>
                        <?php if (count($group['sections']) > 1 || $sectionTitle !== 'Textos') : ?>
                            <h3 style="margin:20px 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#646970;"><?php echo esc_html($sectionTitle); ?></h3>
                        <?php endif; ?>
                        <?php foreach ($fields as $field) :
                            $key = (string) $field['key'];
                            $id = 'fs-text-' . $key;
                            $value = $values[$key] ?? '';
                            $default = (string) $field['default'];
                            ?>
                            <div class="fsc-site-texts__field" style="margin-bottom:16px;">
                                <label for="<?php echo esc_attr($id); ?>" style="display:block;font-weight:600;margin-bottom:6px;"><?php echo esc_html((string) $field['label']); ?></label>
                                <?php if ($field['type'] === 'paragraph') : ?>
                                    <textarea id="<?php echo esc_attr($id); ?>" name="fs_text[<?php echo esc_attr($key); ?>]" rows="4" class="large-text" placeholder="<?php echo esc_attr($default); ?>"><?php echo esc_textarea($value !== '' ? $value : $default); ?></textarea>
                                <?php else : ?>
                                    <input type="text" id="<?php echo esc_attr($id); ?>" name="fs_text[<?php echo esc_attr($key); ?>]" class="large-text" placeholder="<?php echo esc_attr($default); ?>" value="<?php echo esc_attr($value !== '' ? $value : $default); ?>">
                                <?php endif; ?>
                                <?php if ($value !== '') : ?>
                                    <p class="description" style="margin-top:4px;">Texto original: <?php echo esc_html($default); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="fsc-card-actions">
                <?php submit_button('Guardar textos', 'primary fsc-btn', 'submit', false); ?>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($canRescan) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:24px;">
            <?php wp_nonce_field('flashsite_site_texts_rescan'); ?>
            <input type="hidden" name="action" value="flashsite_site_texts_rescan">
            <p class="description">Só administradores: a lista é lida das páginas do Elementor quando são salvas. Se mexeu nas páginas por outro caminho, <button type="submit" class="button-link">atualize a lista</button>.</p>
        </form>
    <?php endif; ?>
</div>
