<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Admin;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Modules\Collections\DemoKitImporter;

/**
 * FlashSite › Coleções: ligar/desligar presets e (em demos) importar os exemplos.
 * Só para quem tem flashsite_manage_collections (Administrador).
 *
 * @since 2.6.0
 */
final class CollectionsPage
{
    public const SLUG = 'flashsite-collections';
    private const NONCE = 'flashsite_collections_save';

    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private DemoKitImporter $importer,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu'], 20);
        add_action('admin_post_flashsite_collections_save', [$this, 'handleSave']);
        add_action('admin_post_flashsite_collections_demo', [$this, 'handleDemo']);
    }

    public function addMenu(): void
    {
        add_submenu_page('flashsite-core', 'Coleções', 'Coleções', CollectionCapabilities::MANAGE_CAP, self::SLUG, [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        $notice = isset($_GET['fs_notice']) ? sanitize_text_field(wp_unslash((string) $_GET['fs_notice'])) : '';
        echo '<div class="wrap flashsite-admin"><h1>Coleções</h1>';
        echo '<p>Conteúdos que o cliente cadastra no painel e o site mostra sozinho. Desligar uma coleção esconde-a, mas nunca apaga os registos.</p>';
        if ($notice !== '') {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($notice));
        }
        foreach ($this->registry->errors() as $source => $message) {
            printf('<div class="notice notice-error"><p>Preset <code>%s</code> ignorado: %s</p></div>', esc_html((string) $source), esc_html($message));
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="flashsite_collections_save">';
        wp_nonce_field(self::NONCE);
        echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th style="width:60px">Ativa</th><th>Coleção</th><th>Endereço no site</th><th>Campos</th></tr></thead><tbody>';
        foreach ($this->registry->all() as $key => $preset) {
            printf(
                '<tr><td><input type="checkbox" name="active[]" value="%1$s" id="fs-col-%1$s"%2$s></td><td><label for="fs-col-%1$s"><strong>%3$s</strong></label></td><td><code>/%4$s/</code></td><td>%5$d</td></tr>',
                esc_attr((string) $key),
                $this->activation->isActive((string) $key) ? ' checked' : '',
                esc_html($preset->labels()['plural']),
                esc_html($preset->slug()),
                count($preset->fields())
            );
        }
        echo '</tbody></table>';
        submit_button('Guardar coleções');
        echo '</form>';

        $withKit = array_filter($this->registry->all(), fn ($preset) => $this->activation->isActive($preset->key()) && $this->importer->hasKit($preset));
        if ($withKit !== []) {
            echo '<h2>Exemplos para demonstração</h2><p>Cria registos fictícios para sites de demonstração. Não usar em sites de clientes.</p>';
            foreach ($withKit as $preset) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 8px 8px 0">';
                echo '<input type="hidden" name="action" value="flashsite_collections_demo">';
                printf('<input type="hidden" name="collection" value="%s">', esc_attr($preset->key()));
                wp_nonce_field(self::NONCE);
                printf('<button class="button button-secondary" name="op" value="import">Importar exemplos · %s</button> ', esc_html($preset->labels()['plural']));
                printf('<button class="button-link-delete" name="op" value="remove" onclick="return confirm(\'Mover os exemplos para o lixo?\')">Remover exemplos</button>');
                echo '</form>';
            }
        }
        echo '</div>';
    }

    public function handleSave(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        $active = isset($_POST['active']) && is_array($_POST['active']) ? array_map('sanitize_key', wp_unslash($_POST['active'])) : [];
        $this->activation->replace(array_values($active), $this->registry->keys());
        do_action('flashsite_collections_changed');
        $this->redirect('Coleções guardadas. Os menus aparecem no painel a seguir.');
    }

    public function handleDemo(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        $preset = $this->registry->get(sanitize_key(wp_unslash((string) ($_POST['collection'] ?? ''))));
        if ($preset === null || ! $this->activation->isActive($preset->key())) {
            $this->redirect('Coleção inativa.');
        }
        $op = sanitize_key(wp_unslash((string) ($_POST['op'] ?? '')));
        if ($op === 'remove') {
            $count = $this->importer->remove($preset);
            $this->redirect(sprintf('%d exemplo(s) movido(s) para o lixo.', $count));
        }
        $result = $this->importer->import($preset);
        $message = sprintf('%d exemplo(s) criado(s), %d já existia(m).', $result['created'], $result['skipped']);
        if ($result['missing_images'] !== []) {
            $message .= ' Imagens não encontradas na Biblioteca: ' . implode(', ', $result['missing_images']) . '.';
        }
        $this->redirect($message);
    }

    private function redirect(string $notice): never
    {
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'fs_notice' => rawurlencode($notice)], admin_url('admin.php')));
        exit;
    }
}
