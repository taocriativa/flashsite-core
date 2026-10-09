<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

/**
 * FlashSite › Textos do site (@since 3.1.0).
 *
 * @var list<array{title: string, anchor: string, url: string, sections: array<string, list<array<string, mixed>>>}> $groups
 * @var array<string, string> $values
 * @var bool $canRescan
 */

$saved   = isset($_GET['saved']) ? (int) $_GET['saved'] : null; // phpcs:ignore WordPress.Security.NonceVerification
$scanned = isset($_GET['scanned']) ? (int) $_GET['scanned'] : null; // phpcs:ignore WordPress.Security.NonceVerification

$headerTitle    = 'Textos do site';
$headerSubtitle = 'Os textos estão na ordem em que aparecem em cada página. Use "Mostrar no site" para ver onde fica cada um. Campo vazio volta ao texto original.';
$headerActions  = [
    ['label' => 'Ver o site', 'url' => home_url('/'), 'variant' => 'secondary'],
];
?>
<div class="wrap flashsite-core-wrap fsc-site-texts-page">
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
        <?php if (count($groups) > 1) : ?>
            <nav class="fsc-texts-nav" aria-label="Páginas">
                <?php foreach ($groups as $group) : ?>
                    <a href="#<?php echo esc_attr($group['anchor']); ?>"><?php echo esc_html($group['title']); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fsc-site-texts" id="fsc-site-texts">
            <?php wp_nonce_field('flashsite_site_texts_save'); ?>
            <input type="hidden" name="action" value="flashsite_site_texts_save">

            <?php foreach ($groups as $group) : ?>
                <section class="fsc-card fsc-texts-page" id="<?php echo esc_attr($group['anchor']); ?>">
                    <div class="fsc-texts-page__head">
                        <h2><?php echo esc_html($group['title']); ?></h2>
                        <?php if ($group['url'] !== '') : ?>
                            <a href="<?php echo esc_url($group['url']); ?>" target="_blank" rel="noopener">Abrir página ↗</a>
                        <?php endif; ?>
                    </div>
                    <?php foreach ($group['sections'] as $sectionTitle => $fields) : ?>
                        <?php if (count($group['sections']) > 1 || $sectionTitle !== 'Textos') : ?>
                            <h3 class="fsc-texts-section"><?php echo esc_html($sectionTitle); ?></h3>
                        <?php endif; ?>
                        <?php foreach ($fields as $field) :
                            $key = (string) $field['key'];
                            $id = 'fs-text-' . $key;
                            $value = $values[$key] ?? '';
                            $default = (string) $field['default'];
                            $current = $value !== '' ? $value : $default;
                            ?>
                            <div class="fsc-texts-field">
                                <div class="fsc-texts-field__label">
                                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html((string) $field['label']); ?></label>
                                    <?php if (($field['show_url'] ?? '') !== '') : ?>
                                        <a class="fsc-texts-field__show" href="<?php echo esc_url((string) $field['show_url']); ?>" target="fs-site-preview">Mostrar no site ↗</a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($field['type'] === 'paragraph') : ?>
                                    <textarea id="<?php echo esc_attr($id); ?>" name="fs_text[<?php echo esc_attr($key); ?>]" rows="<?php echo esc_attr((string) max(2, min(6, (int) ceil(mb_strlen($current) / 110)))); ?>" placeholder="<?php echo esc_attr($default); ?>" data-original="<?php echo esc_attr($current); ?>"><?php echo esc_textarea($current); ?></textarea>
                                <?php else : ?>
                                    <input type="text" id="<?php echo esc_attr($id); ?>" name="fs_text[<?php echo esc_attr($key); ?>]" placeholder="<?php echo esc_attr($default); ?>" value="<?php echo esc_attr($current); ?>" data-original="<?php echo esc_attr($current); ?>">
                                <?php endif; ?>
                                <?php if ($value !== '') : ?>
                                    <p class="description" style="margin-top:4px;">Texto original: <?php echo esc_html($default); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <div class="fsc-texts-savebar">
                <span class="fsc-texts-savebar__status"><span class="fsc-texts-savebar__clean">As alterações aparecem no site assim que guardar.</span><span class="fsc-texts-savebar__dirty" hidden>Há alterações por guardar.</span></span>
                <?php submit_button('Guardar textos', 'primary fsc-btn', 'submit', false); ?>
            </div>
        </form>
        <script>
        (function () {
            var form = document.getElementById('fsc-site-texts');
            if (!form) { return; }
            var bar = form.querySelector('.fsc-texts-savebar');
            var clean = bar.querySelector('.fsc-texts-savebar__clean');
            var changed = bar.querySelector('.fsc-texts-savebar__dirty');
            var dirty = false;
            form.addEventListener('input', function (event) {
                var field = event.target;
                if (!field.hasAttribute('data-original')) { return; }
                field.closest('.fsc-texts-field').classList.toggle('is-changed', field.value !== field.getAttribute('data-original'));
                dirty = form.querySelectorAll('.fsc-texts-field.is-changed').length > 0;
                bar.classList.toggle('is-dirty', dirty);
                clean.hidden = dirty;
                changed.hidden = !dirty;
            });
            form.addEventListener('submit', function () { dirty = false; });
            window.addEventListener('beforeunload', function (event) {
                if (dirty) { event.preventDefault(); event.returnValue = ''; }
            });
        })();
        </script>
    <?php endif; ?>

    <?php if ($canRescan) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:24px;">
            <?php wp_nonce_field('flashsite_site_texts_rescan'); ?>
            <input type="hidden" name="action" value="flashsite_site_texts_rescan">
            <p class="description">Só administradores: a lista é lida das páginas do Elementor quando são salvas. Se mexeu nas páginas por outro caminho, <button type="submit" class="button-link">atualize a lista</button>.</p>
        </form>
    <?php endif; ?>
</div>
